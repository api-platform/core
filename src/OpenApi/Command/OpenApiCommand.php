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

namespace ApiPlatform\OpenApi\Command;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Dumps Open API documentation.
 */
#[AsCommand(name: 'api:openapi:export')]
final class OpenApiCommand extends Command
{
    public function __construct(private readonly OpenApiFactoryInterface $openApiFactory, private readonly NormalizerInterface $normalizer)
    {
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setDescription('Dump the Open API documentation')
            ->addOption('yaml', 'y', InputOption::VALUE_NONE, 'Dump the documentation in YAML')
            ->addOption('output', 'o', InputOption::VALUE_REQUIRED, 'Write output to file')
            ->addOption('spec-version', null, InputOption::VALUE_REQUIRED, 'Open API version to use (2 or 3) (2 is deprecated)', '3')
            ->addOption('api-gateway', null, InputOption::VALUE_NONE, 'Enable the Amazon API Gateway compatibility mode')
            ->addOption('filter-tags', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Filter only matching x-apiplatform-tag operations', null);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $filesystem = new Filesystem();
        $io = new SymfonyStyle($input, $output);
        $data = $this->normalizer->normalize(
            $this->openApiFactory->__invoke(['filter_tags' => $input->getOption('filter-tags')]),
            'json',
            ['spec_version' => $input->getOption('spec-version')]
        );

        if ($input->getOption('yaml') && !class_exists(Yaml::class)) {
            $output->writeln('The "symfony/yaml" component is not installed.');

            return 1;
        }

        $content = $input->getOption('yaml')
            ? Yaml::dump($data, 10, 2, Yaml::DUMP_OBJECT_AS_MAP | Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE | Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK | Yaml::DUMP_NUMERIC_KEY_AS_STRING)
            : (json_encode($data, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES) ?: '');

        $filename = $input->getOption('output');
        if ($filename && \is_string($filename)) {
            $filesystem->dumpFile($filename, $content);
            $io->success(\sprintf('Data written to %s.', $filename));

            return \defined(Command::class.'::SUCCESS') ? Command::SUCCESS : 0;
        }

        $output->writeln($content);

        return \defined(Command::class.'::SUCCESS') ? Command::SUCCESS : 0;
    }
}
