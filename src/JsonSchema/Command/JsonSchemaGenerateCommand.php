<?php

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
        $this->formats = $formats;

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
            ->addOption('itemOperation', null, InputOption::VALUE_OPTIONAL, 'The item operation')
            ->addOption('collectionOperation', null, InputOption::VALUE_OPTIONAL, 'The collection operation')
            ->addOption('format', null, InputOption::VALUE_OPTIONAL, 'The response format', array_key_first($this->formats))
            ->addOption('output', null, InputOption::VALUE_NONE, 'Use this option to get the output version')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $resource = $input->getArgument('resource');
        $itemOperation = $input->getOption('itemOperation');
        $collectionOperation = $input->getOption('collectionOperation');
        $format = $input->getOption('format');
        $outputType = $input->getOption('output');

        if (!isset($this->formats[$format])) {
            throw new InvalidOptionException(\sprintf('The response format "%s" is not supported. Supported formats are : %s.', $format, implode(', ', array_keys($this->formats))));
        }

        $operationType = null;
        $operationName = null;

        if ($itemOperation && $collectionOperation) {
            throw new InvalidOptionException('You can only use one of "--itemOperation" and "--collectionOperation" options at the same time.');
        }

        if (null !== $itemOperation || null !== $collectionOperation) {
            $operationType = $itemOperation ? OperationType::ITEM : OperationType::COLLECTION;
            $operationName = $itemOperation ?? $collectionOperation;
        }

        $schema = $this->schemaFactory->buildSchema($resource, $format,  $outputType , $operationType, $operationName);

        if (!$schema->isDefined()) {
            $io->error(\sprintf('There is no %s defined for the operation "%s" of the resource "%s".',  $outputType ? 'outputs': 'inputs', $operationName, $resource));
            return;
        }

        $io->text(\json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
