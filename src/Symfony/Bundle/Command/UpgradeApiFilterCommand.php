<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ApiPlatform\Symfony\Bundle\Command;

use ApiPlatform\Metadata\FilterInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Util\AttributeFilterExtractorTrait;
use ApiPlatform\Symfony\Bundle\Command\Upgrade\UpgradeApiFilterResolver;
use ApiPlatform\Symfony\Bundle\Command\Upgrade\UpgradeApiFilterSkipException;
use ApiPlatform\Symfony\Bundle\Command\Upgrade\UpgradeApiFilterVisitor;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\CloningVisitor;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use Psr\Container\ContainerInterface;
use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

/**
 * Rewrites legacy `#[ApiFilter]` declarations to `QueryParameter` entries on the resource.
 *
 * Only `#[ApiFilter]`-generated filters (service ids prefixed `annotated_`) are migrated.
 * Resources whose filters cannot be expressed as distinct QueryParameters (e.g. an exact and a
 * range filter on the same property) are reported and skipped.
 *
 * This command is a one-shot upgrade helper for the 4.4 → 5.0 filter migration and will be removed in 6.0.
 */
#[AsCommand(name: 'api:upgrade-filter', description: 'Upgrades legacy #[ApiFilter] declarations to QueryParameter')]
final class UpgradeApiFilterCommand extends Command
{
    use AttributeFilterExtractorTrait;

    public function __construct(
        private readonly ResourceNameCollectionFactoryInterface $resourceNameCollectionFactory,
        private readonly ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory,
        private readonly ContainerInterface $filterLocator,
        private readonly UpgradeApiFilterResolver $resolver,
        private readonly ?string $csFixerBinary = null,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('class', InputArgument::OPTIONAL, 'Restrict the upgrade to a single resource class')
            ->addOption('dry-run', 'd', InputOption::VALUE_NEGATABLE, 'Output a diff instead of writing files', true)
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Write the files in place');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = !$input->getOption('force') && false !== $input->getOption('dry-run');

        $classes = ($class = $input->getArgument('class')) ? [$class] : iterator_to_array($this->resourceNameCollectionFactory->create());
        $skipped = [];
        $changed = 0;

        foreach ($classes as $resourceClass) {
            // Legacy/ fixtures intentionally keep #[ApiFilter] as the regression suite.
            if (str_contains($resourceClass, '\\Legacy\\')) {
                continue;
            }

            try {
                $reflection = new \ReflectionClass($resourceClass);
            } catch (\ReflectionException) {
                continue;
            }

            $filters = $this->annotatedFilters($reflection);
            if (!$filters) {
                continue;
            }

            try {
                $parameters = $this->resolver->resolve($resourceClass, $filters, $this->reservedFilters($resourceClass));
            } catch (UpgradeApiFilterSkipException $e) {
                $skipped[$resourceClass] = $e->getMessage();
                continue;
            }

            if (!$parameters || !($file = $reflection->getFileName())) {
                continue;
            }

            $original = file_get_contents($file);
            $updated = $this->transform($original, $resourceClass, $parameters);

            if ($updated === $original) {
                continue;
            }

            ++$changed;

            if ($dryRun) {
                $io->section($resourceClass);
                $output->write($this->diff($original, $updated));
                continue;
            }

            file_put_contents($file, $updated);
            $this->fix($file);
            $io->writeln(\sprintf('<info>upgraded</info> %s', $resourceClass));
        }

        foreach ($skipped as $class => $reason) {
            $io->warning(\sprintf('Skipped %s: %s', $class, $reason));
        }

        $io->success(\sprintf('%s resource(s) %s.', $changed, $dryRun ? 'would be upgraded (dry-run)' : 'upgraded'));

        return Command::SUCCESS;
    }

    /**
     * Reads every `#[ApiFilter]` declaration on the resource (keyed by its generated service id so two
     * instances of the same filter class stay distinct), pairing each with the configured filter instance
     * and its constructor arguments. The `properties` field map is dropped: properties are resolved through
     * the runtime description, not re-emitted as a filter constructor argument.
     *
     * @return list<array{filter: FilterInterface, filterClass: class-string, arguments: array<string, mixed>}>
     */
    private function annotatedFilters(\ReflectionClass $reflectionClass): array
    {
        $filters = [];

        foreach ($this->readFilterAttributes($reflectionClass) as $id => [$arguments, $filterClass]) {
            if (!$this->filterLocator->has($id)) {
                continue;
            }

            $filter = $this->filterLocator->get($id);
            if (!$filter instanceof FilterInterface) {
                continue;
            }

            unset($arguments['properties']);

            $filters[] = ['filter' => $filter, 'filterClass' => $filterClass, 'arguments' => $arguments];
        }

        return $filters;
    }

    /**
     * In-place service filters declared on the resource through the `filters:` array (i.e. not generated
     * by `#[ApiFilter]`). Their query keys are reserved: migrating an #[ApiFilter] onto one of them would
     * silently shadow the service filter, so such a resource is skipped instead.
     *
     * @return list<FilterInterface>
     */
    private function reservedFilters(string $resourceClass): array
    {
        $filters = [];
        $seenIds = [];

        foreach ($this->resourceMetadataFactory->create($resourceClass) as $resource) {
            foreach ($resource->getOperations() ?? [] as $operation) {
                foreach ($operation->getFilters() ?? [] as $filterId) {
                    if (str_starts_with($filterId, 'annotated_') || isset($seenIds[$filterId]) || !$this->filterLocator->has($filterId)) {
                        continue;
                    }

                    $seenIds[$filterId] = true;
                    $filter = $this->filterLocator->get($filterId);
                    if ($filter instanceof FilterInterface) {
                        $filters[] = $filter;
                    }
                }
            }
        }

        return $filters;
    }

    /**
     * @param list<Upgrade\UpgradeApiFilterParameter> $parameters
     */
    private function transform(string $code, string $resourceClass, array $parameters): string
    {
        $parser = (new ParserFactory())->createForHostVersion();
        $oldStmts = $parser->parse($code);
        $oldTokens = $parser->getTokens();

        $newStmts = (new NodeTraverser(new CloningVisitor()))->traverse($oldStmts);
        $newStmts = (new NodeTraverser(new UpgradeApiFilterVisitor($resourceClass, $parameters)))->traverse($newStmts);

        return (new Standard())->printFormatPreserving($newStmts, $oldStmts, $oldTokens);
    }

    private function diff(string $from, string $to): string
    {
        return (new Differ(new UnifiedDiffOutputBuilder("--- original\n+++ upgraded\n")))->diff($from, $to);
    }

    private function fix(string $file): void
    {
        if (!$this->csFixerBinary || !is_file($this->csFixerBinary)) {
            return;
        }

        (new Process([\PHP_BINARY, $this->csFixerBinary, 'fix', $file, '--quiet']))->run();
    }
}
