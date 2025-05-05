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

namespace ApiPlatform\JsonSchema\Command;

use ApiPlatform\JsonSchema\Schema;
use ApiPlatform\JsonSchema\SchemaFactoryInterface;
use ApiPlatform\Metadata\HttpOperation;
use Symfony\Component\Console\Attribute\AsCommand;
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
#[AsCommand(name: 'api:json-schema:generate')]
final class JsonSchemaGenerateCommand extends Command
{
    private array $formats;

    public function __construct(private readonly SchemaFactoryInterface $schemaFactory, array $formats)
    {
        $this->formats = array_keys($formats);

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setDescription('Generates the JSON Schema for a resource operation.')
            ->addArgument('resource', InputArgument::REQUIRED, 'The Fully Qualified Class Name (FQCN) of the resource')
            ->addOption('operation', null, InputOption::VALUE_REQUIRED, 'The operation name')
            ->addOption('format', null, InputOption::VALUE_REQUIRED, 'The response format', (string) $this->formats[0])
            ->addOption('type', null, InputOption::VALUE_REQUIRED, \sprintf('The type of schema to generate (%s or %s)', Schema::TYPE_INPUT, Schema::TYPE_OUTPUT), Schema::TYPE_INPUT);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /** @var string $resource */
        $resource = $input->getArgument('resource');
        $operation = $input->getOption('operation');
        /** @var string $format */
        $format = $input->getOption('format');
        /** @var string $type */
        $type = $input->getOption('type');

        if (!\in_array($type, [Schema::TYPE_INPUT, Schema::TYPE_OUTPUT], true)) {
            $io->error(\sprintf('You can only use "%s" or "%s" for the "type" option', Schema::TYPE_INPUT, Schema::TYPE_OUTPUT));

            return 1;
        }

        if (!\in_array($format, $this->formats, true)) {
            throw new InvalidOptionException(\sprintf('The response format "%s" is not supported. Supported formats are : %s.', $format, implode(', ', $this->formats)));
        }

        $schema = $this->schemaFactory->buildSchema($resource, $format, $type, $operation ? (new class extends HttpOperation {})->withName($operation) : null);

        if (!$schema->isDefined()) {
            $io->error(\sprintf('There is no %s defined for the operation "%s" of the resource "%s".', $type, $operation, $resource));

            return 1;
        }

        $io->text((string) json_encode($schema, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES));

        return 0;
    }
}
