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

use ApiPlatform\Core\Bridge\Rector\Parser\TransformApiSubresourceVisitor;
use ApiPlatform\Core\Bridge\Rector\Service\SubresourceTransformer;
use ApiPlatform\Core\Bridge\Rector\Set\ApiPlatformSetList;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Operation\Factory\SubresourceOperationFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use PhpParser\Lexer\Emulative;
use PhpParser\NodeTraverser;
use PhpParser\Parser\Php7;
use PhpParser\PrettyPrinter\Standard;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @experimental
 */
final class RectorCommand extends Command
{
    private const OPERATIONS = [
        'annotation-to-legacy-api-resource' => '@ApiResource to #[ApiPlatform\Core\Annotation\ApiResource] - deprecated since 2.7',
        'annotation-to-api-resource' => '@ApiResource to #[ApiPlatform\Metadata\ApiResource]',
        'keep-attribute' => '#[ApiPlatform\Core\Annotation\ApiResource] to #[ApiPlatform\Metadata\ApiResource]',
        'transform-apisubresource' => 'Transform @ApiSubresource to alternate resources',
    ];

    protected static $defaultName = 'api:rector:upgrade';

    private $resourceNameCollectionFactory;
    private $resourceMetadataFactory;
    private $subresourceOperationFactory;
    private $subresourceTransformer;
    private $localCache = [];

    public function __construct(ResourceNameCollectionFactoryInterface $resourceNameCollectionFactory, ResourceMetadataFactoryInterface $resourceMetadataFactory, SubresourceOperationFactoryInterface $subresourceOperationFactory, SubresourceTransformer $subresourceTransformer)
    {
        $this->resourceNameCollectionFactory = $resourceNameCollectionFactory;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->subresourceOperationFactory = $subresourceOperationFactory;
        $this->subresourceTransformer = $subresourceTransformer;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('Change "ApiPlatform\Core\Annotation\ApiResource" annotation/attribute to new "ApiPlatform\Metadata\ApiResource" attribute')
            ->addOption('dry-run', '-d', InputOption::VALUE_NONE, 'Rector will show you diff of files that it would change. To make the changes, drop --dry-run')
            ->addOption('silent', '-s', InputOption::VALUE_NONE, 'Run Rector silently')
            ->addArgument('src', InputArgument::REQUIRED, 'Path to folder/file to convert, forwarded to Rector');

        foreach (self::OPERATIONS as $operationKey => $operationDescription) {
            $this->addOption($operationKey, null, InputOption::VALUE_NONE, $operationDescription);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!file_exists('vendor/bin/rector')) {
            $output->write('Rector is not installed. Please execute composer require --dev rector/rector:0.12.5');

            return Command::FAILURE;
        }

        $io = new SymfonyStyle($input, $output);
        $operations = self::OPERATIONS;

        $choices = array_values($operations);

        $choice = null;
        $operationCount = 0;
        $askForSubresources = true;

        foreach ($operations as $operationKey => $operationDescription) {
            if ($input->getOption($operationKey)) {
                $choice = $operationKey;
                ++$operationCount;
            }
        }

        if ($operationCount > 1) {
            $output->write('Only one operation can be given as a parameter.');

            return Command::FAILURE;
        }

        if (!$choice) {
            $io->text([
                'Welcome,',
                'This tool allows you to transform Doctrine Annotations "@ApiResource" into Attributes "#[ApiPlatform\Core\Annotation\ApiResource]".',
                'Note that since 2.7 there is a new Attribute at ApiPlatform\Metadata\ApiResource that allows you more control over resources. It\'s the new default in 3.0.',
            ]);
            $choice = $io->choice('Choose an operation to perform:', $choices);
        } else {
            $askForSubresources = false;
        }

        $operationKey = $this->getOperationKeyByChoice($operations, $choice);

        $command = 'vendor/bin/rector process '.$input->getArgument('src');

        if ($output->isDebug()) {
            $command .= ' --debug';
        }

        $operationKeys = array_keys($operations);

        switch ($operationKey) {
            case $operationKeys[0]:
                $command .= ' --config='.ApiPlatformSetList::ANNOTATION_TO_LEGACY_API_RESOURCE_ATTRIBUTE;
                break;
            case $operationKeys[1]:
                if ($askForSubresources && $this->isThereSubresources($io, $output)) {
                    return Command::FAILURE;
                }
                $command .= ' --config='.ApiPlatformSetList::ANNOTATION_TO_API_RESOURCE_ATTRIBUTE;
                break;
            case $operationKeys[2]:
                if ($askForSubresources && $this->isThereSubresources($io, $output)) {
                    return Command::FAILURE;
                }
                $command .= ' --config='.ApiPlatformSetList::ATTRIBUTE_TO_API_RESOURCE_ATTRIBUTE;
                break;
            case $operationKeys[3]:
                $command .= ' --config='.ApiPlatformSetList::TRANSFORM_API_SUBRESOURCE;
                break;
        }

        if ($input->getOption('dry-run')) {
            $command .= ' --dry-run';
        } else {
            if (!$io->confirm('Your files will be overridden. Do you want to continue ?')) {
                $output->write('Migration aborted.');

                return Command::FAILURE;
            }
        }

        $io->title('Run '.$command);

        if ($operationKey === $operationKeys[3] && !$input->getOption('dry-run')) {
            $this->transformApiSubresource($input->getArgument('src'), $output);
        }

        if ($input->getOption('silent')) {
            exec($command.' --no-progress-bar --no-diffs');
        } else {
            passthru($command);
        }

        $output->writeln('Migration successful.');

        return Command::SUCCESS;
    }

    private function getOperationKeyByChoice($operations, $choice): string
    {
        if (\in_array($choice, array_keys($operations), true)) {
            return $choice;
        }

        return array_search($choice, $operations, true);
    }

    private function transformApiSubresource(string $src, OutputInterface $output)
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

            if (!str_contains($fileName, $src)) {
                continue;
            }

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
                $traverser->addVisitor(new TransformApiSubresourceVisitor($subresourceMetadata, $referenceType));
                $prettyPrinter = new Standard();

                $oldStmts = $parser->parse(file_get_contents($fileName));
                $oldTokens = $lexer->getTokens();

                $newStmts = $traverser->traverse($oldStmts);

                $newCode = $prettyPrinter->printFormatPreserving($newStmts, $oldStmts, $oldTokens);

                file_put_contents($fileName, $newCode);
            }
        }
    }

    private function isThereSubresources($io, $output): bool
    {
        if ($io->confirm('Do you have any @ApiSubresource or #[ApiSubresource] left in your code ?')) {
            $output->writeln('You will not be able to convert them afterwards. Please run the command "Transform @ApiSubresource to alternate resources" first.');

            return true;
        }

        return false;
    }
}
