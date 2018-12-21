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

use ApiPlatform\Core\Documentation\Documentation;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Core\Swagger\Serializer\DocumentationNormalizer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Console command to dump Swagger API documentations.
 *
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
final class SwaggerCommand extends Command
{
    private $normalizer;
    private $resourceNameCollectionFactory;
    private $apiTitle;
    private $apiDescription;
    private $apiVersion;
    private $apiFormats;

    public function __construct(NormalizerInterface $normalizer, ResourceNameCollectionFactoryInterface $resourceNameCollection, string $apiTitle, string $apiDescription, string $apiVersion, array $apiFormats)
    {
        $this->normalizer = $normalizer;
        $this->resourceNameCollectionFactory = $resourceNameCollection;
        $this->apiTitle = $apiTitle;
        $this->apiDescription = $apiDescription;
        $this->apiVersion = $apiVersion;
        $this->apiFormats = $apiFormats;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('api:openapi:export')
            ->setAliases(['api:swagger:export'])
            ->setDescription('Dump the OpenAPI documentation')
            ->addOption('yaml', 'y', InputOption::VALUE_NONE, 'Dump the documentation in YAML')
            ->addOption('spec-version', null, InputOption::VALUE_OPTIONAL, 'OpenAPI version to use ("2" or "3")', '2')
            ->addOption('output', 'o', InputOption::VALUE_OPTIONAL, 'Write output to file');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        /** @var string $version */
        $version = $input->getOption('spec-version');

        if (!\in_array($version, ['2', '3'], true)) {
            throw new InvalidOptionException(sprintf('This tool only supports version 2 and 3 of the OpenAPI specification ("%s" given).', $version));
        }

        $documentation = new Documentation($this->resourceNameCollectionFactory->create(), $this->apiTitle, $this->apiDescription, $this->apiVersion, $this->apiFormats);
        $data = $this->normalizer->normalize($documentation, DocumentationNormalizer::FORMAT, ['spec_version' => (int) $version]);
        $content = $input->getOption('yaml') ? Yaml::dump($data, 10, 2, Yaml::DUMP_OBJECT_AS_MAP | Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE) : (json_encode($data, JSON_PRETTY_PRINT) ?: '');

        if (!empty($filename = $input->getOption('output')) && \is_string($filename)) {
            file_put_contents($filename, $content);
            $io->success(sprintf('Data written to %s (specification version %s).', $filename, $version));
        } else {
            $output->writeln($content);
        }
    }
}
