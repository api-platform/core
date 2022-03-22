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

namespace ApiPlatform\Core\Bridge\Symfony\Bundle\Command;

use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Operation\Factory\SubresourceOperationFactoryInterface;
use ApiPlatform\Core\Upgrade\ColorConsoleDiffFormatter;
use ApiPlatform\Core\Upgrade\SubresourceTransformer;
use ApiPlatform\Core\Upgrade\UpgradeApiResourceVisitor;
use ApiPlatform\Core\Upgrade\UpgradeApiSubresourceVisitor;
use ApiPlatform\Exception\ResourceClassNotFoundException;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use Doctrine\Common\Annotations\AnnotationReader;
use PhpParser\Lexer\Emulative;
use PhpParser\NodeTraverser;
use PhpParser\Parser\Php7;
use PhpParser\PrettyPrinter\Standard;
use SebastianBergmann\Diff\Differ;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class UpgradeApiResourceCommand extends Command
{
    protected static $defaultName = 'api:upgrade-resource';

    private $resourceNameCollectionFactory;
    private $resourceMetadataFactory;
    private $subresourceOperationFactory;
    private $subresourceTransformer;
    private $reader;
    private $localCache = [];

    public function __construct(ResourceNameCollectionFactoryInterface $resourceNameCollectionFactory, ResourceMetadataFactoryInterface $resourceMetadataFactory, SubresourceOperationFactoryInterface $subresourceOperationFactory, SubresourceTransformer $subresourceTransformer, AnnotationReader $reader)
    {
        $this->resourceNameCollectionFactory = $resourceNameCollectionFactory;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->subresourceOperationFactory = $subresourceOperationFactory;
        $this->subresourceTransformer = $subresourceTransformer;
        $this->reader = $reader;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('The "api:upgrade-resource" command upgrades your API Platform metadata from versions below 2.6 to the new metadata from versions above 2.7.
Once you executed this script, make sure that the "metadata_backward_compatibility_layer" flag is set to "false" in the API Platform configuration.
This will remove "ApiPlatform\Core\Annotation\ApiResource" annotation/attribute and use the "ApiPlatform\Metadata\ApiResource" attribute instead.')
            ->addOption('dry-run', '-d', InputOption::VALUE_NEGATABLE, 'Dry mode outputs a diff instead of writing files.', true)
            ->addOption('silent', '-s', InputOption::VALUE_NONE, 'Silent output.')
            ->addOption('force', '-f', InputOption::VALUE_NONE, 'Writes the files in place and skips PHP version check.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$input->getOption('force') && \PHP_VERSION_ID < 80100) {
            $output->write('<error>The new metadata system only works with PHP 8.1 and above.');

            return Command::INVALID;
        }

        $this->transformApiSubresource($input, $output);
        $this->transformApiResource($input, $output);

        return Command::SUCCESS;
    }

    /**
     * This computes a local cache with resource classes having subresources.
     * We first loop over all the classes and re-map the metadata on the correct Resource class.
     * Then we transform the ApiSubresource to an ApiResource class.
     */
    private function transformApiSubresource(InputInterface $input, OutputInterface $output): void
    {
        foreach ($this->resourceNameCollectionFactory->create() as $resourceClass) {
            try {
                new \ReflectionClass($resourceClass);
            } catch (\Exception $e) {
                continue;
            }

            if (!isset($this->localCache[$resourceClass])) {
                $this->localCache[$resourceClass] = [];
            }

            foreach ($this->subresourceOperationFactory->create($resourceClass) as $subresourceMetadata) {
                if (!isset($this->localCache[$subresourceMetadata['resource_class']])) {
                    $this->localCache[$subresourceMetadata['resource_class']] = [];
                }

                foreach ($this->localCache[$subresourceMetadata['resource_class']] as $currentSubresourceMetadata) {
                    if ($currentSubresourceMetadata['path'] === $subresourceMetadata['path']) {
                        continue 2;
                    }
                }
                $this->localCache[$subresourceMetadata['resource_class']][] = $subresourceMetadata;
            }
        }

        // Compute URI variables
        foreach ($this->localCache as $class => $subresources) {
            if (!$subresources) {
                unset($this->localCache[$class]);
                continue;
            }

            foreach ($subresources as $i => $subresourceMetadata) {
                $this->localCache[$class][$i]['uri_variables'] = $this->subresourceTransformer->toUriVariables($subresourceMetadata);
            }
        }

        foreach ($this->localCache as $resourceClass => $linkedSubresourceMetadata) {
            $fileName = (new \ReflectionClass($resourceClass))->getFilename();

            $referenceType = null;
            try {
                $metadata = $this->resourceMetadataFactory->create($resourceClass);
                $referenceType = $metadata->getAttribute('url_generation_strategy');
            } catch (\Exception $e) {
            }

            foreach ($linkedSubresourceMetadata as $subresourceMetadata) {
                $lexer = new Emulative([
                    'usedAttributes' => [
                        'comments',
                        'startLine', 'endLine',
                        'startTokenPos', 'endTokenPos',
                    ],
                ]);
                $parser = new Php7($lexer);

                $traverser = new NodeTraverser();
                $traverser->addVisitor(new UpgradeApiSubresourceVisitor($subresourceMetadata, $referenceType));
                $prettyPrinter = new Standard();

                $oldCode = file_get_contents($fileName);
                $oldStmts = $parser->parse($oldCode);
                $oldTokens = $lexer->getTokens();

                $newStmts = $traverser->traverse($oldStmts);

                $newCode = $prettyPrinter->printFormatPreserving($newStmts, $oldStmts, $oldTokens);

                if (!$input->getOption('force') && $input->getOption('dry-run')) {
                    if ($input->getOption('silent')) {
                        continue;
                    }

                    $this->printDiff($oldCode, $newCode, $output);
                    continue;
                }

                file_put_contents($fileName, $newCode);
            }
        }
    }

    private function transformApiResource(InputInterface $input, OutputInterface $output): void
    {
        $prettyPrinter = new Standard();
        foreach ($this->resourceNameCollectionFactory->create() as $resourceClass) {
            try {
                $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
            } catch (ResourceClassNotFoundException $e) {
                continue;
            }
            $lexer = new Emulative([
                'usedAttributes' => [
                    'comments',
                    'startLine',
                    'endLine',
                    'startTokenPos',
                    'endTokenPos',
                ],
            ]);
            $parser = new Php7($lexer);
            $fileName = (new \ReflectionClass($resourceClass))->getFilename();

            $traverser = new NodeTraverser();
            [$attribute, $isAnnotation] = $this->readApiResource($resourceClass);

            if (!$attribute) {
                continue;
            }

            $traverser->addVisitor(new UpgradeApiResourceVisitor($attribute, $isAnnotation));

            $oldCode = file_get_contents($fileName);
            $oldStmts = $parser->parse($oldCode);
            $oldTokens = $lexer->getTokens();

            $newStmts = $traverser->traverse($oldStmts);
            $newCode = $prettyPrinter->printFormatPreserving($newStmts, $oldStmts, $oldTokens);

            if (!$input->getOption('force') && $input->getOption('dry-run')) {
                if ($input->getOption('silent')) {
                    continue;
                }

                $this->printDiff($oldCode, $newCode, $output);
                continue;
            }

            file_put_contents($fileName, $newCode);
        }
    }

    private function printDiff(string $oldCode, string $newCode, OutputInterface $output): void
    {
        $consoleFormatter = new ColorConsoleDiffFormatter();
        $differ = new Differ();
        $diff = $differ->diff($oldCode, $newCode);
        $output->write($consoleFormatter->format($diff));
    }

    /**
     * @return [ApiResource, bool]
     */
    private function readApiResource(string $resourceClass): array
    {
        $reflectionClass = new \ReflectionClass($resourceClass);

        if (\PHP_VERSION_ID >= 80000 && $attributes = $reflectionClass->getAttributes(ApiResource::class)) {
            return [$attributes[0]->newInstance(), false];
        }

        return [$this->reader->getClassAnnotation($reflectionClass, ApiResource::class), true];
    }
}
