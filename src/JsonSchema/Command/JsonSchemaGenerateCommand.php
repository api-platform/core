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

namespace ApiPlatform\Core\JsonSchema\Command;

use ApiPlatform\Core\Api\OperationType;
use ApiPlatform\Core\JsonSchema\SchemaFactoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Generates a resource JSON Schema.
 *
 * @author Jacques Lefebvre <jacques@les-tilleuls.coop>
 */
final class JsonSchemaGenerateCommand extends Command
{
    private $schemaFactory;
    private $formats;

    public function __construct(SchemaFactoryInterface $schemaFactory, array $formats)
    {
        $this->schemaFactory = $schemaFactory;
        $this->formats = array_keys($formats);

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('api:json-schema:generate')
            ->setDescription('Generates the JSON Schema for a resource operation.')
            ->addArgument('resource', InputArgument::REQUIRED, 'The Fully Qualified Class Name (FQCN) of the resource')
            ->addOption('itemOperation', null, InputOption::VALUE_REQUIRED, 'The item operation')
            ->addOption('collectionOperation', null, InputOption::VALUE_REQUIRED, 'The collection operation')
            ->addOption('format', null, InputOption::VALUE_REQUIRED, 'The response format', (string) $this->formats[0])
            ->addOption('type', null, InputOption::VALUE_REQUIRED, 'The type of schema to generate (input or output)', 'input');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        /** @var string $resource */
        $resource = $input->getArgument('resource');
        /** @var ?string $itemOperation */
        $itemOperation = $input->getOption('itemOperation');
        /** @var ?string $collectionOperation */
        $collectionOperation = $input->getOption('collectionOperation');
        /** @var string $format */
        $format = $input->getOption('format');
        /** @var string $outputType */
        $outputType = $input->getOption('type');

        if (!\in_array($outputType, ['input', 'output'], true)) {
            $io->error('You can only use "input" or "output" for the "type" option');

            return 1;
        }

        if (!\in_array($format, $this->formats, true)) {
            throw new InvalidOptionException(sprintf('The response format "%s" is not supported. Supported formats are : %s.', $format, implode(', ', $this->formats)));
        }

        /** @var ?string $operationType */
        $operationType = null;
        /** @var ?string $operationName */
        $operationName = null;

        if ($itemOperation && $collectionOperation) {
            $io->error('You can only use one of "--itemOperation" and "--collectionOperation" options at the same time.');

            return 1;
        }

        if (null !== $itemOperation || null !== $collectionOperation) {
            $operationType = $itemOperation ? OperationType::ITEM : OperationType::COLLECTION;
            $operationName = $itemOperation ?? $collectionOperation;
        }

        $schema = $this->schemaFactory->buildSchema($resource, $format, 'output' === $outputType, $operationType, $operationName);

        if (null !== $operationType && null !== $operationName && !$schema->isDefined()) {
            $io->error(sprintf('There is no %ss defined for the operation "%s" of the resource "%s".', $outputType, $operationName, $resource));

            return 1;
        }

        $io->text((string) json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return 0;
    }
}
