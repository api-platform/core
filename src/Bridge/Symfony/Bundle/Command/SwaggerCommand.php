<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Bridge\Symfony\Bundle\Command;

use ApiPlatform\Core\Documentation\Documentation;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Core\Swagger\DocumentationNormalizer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Console command to dump Swagger API documentations.
 *
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
final class SwaggerCommand extends Command
{
    private $documentationNormalizer;
    private $resourceNameCollectionFactory;
    private $title;
    private $description;
    private $version;
    private $formats;

    public function __construct(DocumentationNormalizer $documentationNormalizer, ResourceNameCollectionFactoryInterface $resourceNameCollection, string $title, string $description, string $version, array $formats)
    {
        parent::__construct();
        $this->documentationNormalizer = $documentationNormalizer;
        $this->resourceNameCollectionFactory = $resourceNameCollection;
        $this->title = $title;
        $this->description = $description;
        $this->version = $version;
        $this->formats = $formats;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('api:swagger:export')
            ->setDescription('Dump the Swagger 2.0 (OpenAPI) documentation');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $documentation = new Documentation($this->resourceNameCollectionFactory->create(), $this->title, $this->description, $this->version, $this->formats);
        $data = $this->documentationNormalizer->normalize($documentation);
        $content = json_encode($data, JSON_PRETTY_PRINT);
        $output->writeln($content);
    }
}
