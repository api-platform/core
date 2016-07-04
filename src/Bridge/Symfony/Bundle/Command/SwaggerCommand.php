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

use ApiPlatform\Core\Swagger\ApiDocumentationBuilder;
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
    private $apiDocumentationBuilder;

    public function __construct(ApiDocumentationBuilder $apiDocumentationBuilder)
    {
        parent::__construct();
        $this->apiDocumentationBuilder = $apiDocumentationBuilder;
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
        $data = $this->apiDocumentationBuilder->getApiDocumentation();
        $content = json_encode($data, JSON_PRETTY_PRINT);
        $output->writeln($content);
    }
}
