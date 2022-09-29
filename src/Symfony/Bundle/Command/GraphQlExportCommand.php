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

use ApiPlatform\Core\GraphQl\Type\SchemaBuilderInterface as SchemaBuilderLegacyInterface;
use ApiPlatform\GraphQl\Type\SchemaBuilderInterface;
use GraphQL\Utils\SchemaPrinter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Export the GraphQL schema in Schema Definition Language (SDL).
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class GraphQlExportCommand extends Command
{
    /** @var SchemaBuilderLegacyInterface|SchemaBuilderInterface */
    private $schemaBuilder;

    public function __construct($schemaBuilder)
    {
        $this->schemaBuilder = $schemaBuilder;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setDescription('Export the GraphQL schema in Schema Definition Language (SDL)')
            ->addOption('comment-descriptions', null, InputOption::VALUE_NONE, 'Use preceding comments as the description')
            ->addOption('output', 'o', InputOption::VALUE_REQUIRED, 'Write output to file');
    }

    /**
     * {@inheritdoc}
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $options = [];

        if ($input->getOption('comment-descriptions')) {
            $options['commentDescriptions'] = true;
        }

        $schemaExport = SchemaPrinter::doPrint($this->schemaBuilder->getSchema(), $options);

        $filename = $input->getOption('output');
        if (\is_string($filename)) {
            file_put_contents($filename, $schemaExport);
            $io->success(sprintf('Data written to %s.', $filename));
        } else {
            $output->writeln($schemaExport);
        }

        return \defined(Command::class.'::SUCCESS') ? Command::SUCCESS : 0;
    }

    public static function getDefaultName(): string
    {
        return 'api:graphql:export';
    }
}

class_alias(GraphQlExportCommand::class, \ApiPlatform\Core\Bridge\Symfony\Bundle\Command\GraphQlExportCommand::class);
