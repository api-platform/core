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

use ApiPlatform\Core\OpenApi\Factory\OpenApiFactoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Console command to dump OpenApi documentations.
 */
final class OpenApiCommand extends Command
{
    private $openApiFactory;
    private $normalizer;

    public function __construct(OpenApiFactoryInterface $openApiFactory, NormalizerInterface $normalizer)
    {
        $this->openApiFactory = $openApiFactory;
        $this->normalizer = $normalizer;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('api:openapi:export')
            ->setDescription('Dump the OpenAPI documentation')
            ->addOption('yaml', 'y', InputOption::VALUE_NONE, 'Dump the documentation in YAML')
            ->addOption('output', 'o', InputOption::VALUE_OPTIONAL, 'Write output to file');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $data = $this->normalizer->normalize($this->openApiFactory->create(), 'json');
        $content = $input->getOption('yaml')
            ? Yaml::dump($data, 10, 2, Yaml::DUMP_OBJECT_AS_MAP | Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE | Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK)
            : (json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '');

        if (!empty($filename = $input->getOption('output')) && \is_string($filename)) {
            file_put_contents($filename, $content);
            $io->success(sprintf('Data written to %s.', $filename));
        } else {
            $output->writeln($content);
        }

        return 0;
    }
}
