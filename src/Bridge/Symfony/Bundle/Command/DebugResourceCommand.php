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

use ApiPlatform\Core\Metadata\ResourceCollection\Factory\ResourceCollectionMetadataFactoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DebugResourceCommand extends Command
{
    protected static $defaultName = 'debug:api';

    private $resourceCollectionMetadataFactory;

    public function __construct(ResourceCollectionMetadataFactoryInterface $resourceCollectionMetadataFactory)
    {
        parent::__construct();
        $this->resourceCollectionMetadataFactory = $resourceCollectionMetadataFactory;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setDescription('Debug API Platform resources')
            ->addArgument('class');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $resourceClass = $input->getArgument('class');

        $resourceCollection = $this->resourceCollectionMetadataFactory->create($resourceClass);

        if (0 === \count($resourceCollection)) {
            $output->writeln(sprintf('<error>No resources found for class %s</error>', $resourceClass));

            return 1;
        }

        $shortName = (false !== $pos = strrpos($resourceClass, '\\')) ? substr($resourceClass, $pos + 1) : $resourceClass;
        $output->writeln(sprintf('Class %s declares %d resource%s.', $shortName, \count($resourceCollection), \count($resourceCollection) > 1 ? 's' : '').\PHP_EOL);

        foreach ($resourceCollection as $resource) {
            foreach ($resource->getOperations() as $operation) {
                $table = new Table($output);
                $table->setHeaders([sprintf('%s %s', $operation->getMethod(), $operation->getUriTemplate())]);

                foreach ($operation->getIdentifiers() ?? [] as $parameter => [$class, $property]) {
                    $table->addRow([sprintf('%s: %s::%s ', $parameter, $class === $resourceClass ? $shortName : $class, $property)]);
                }

                $links = [];
                foreach ($operation->getLinks() ?? [] as [$link]) {
                    $links[] = $link;
                }

                $table->addRow(['Links to: ' . implode(', ', $links)]);

                $table->render();
            }
        }

        return 0;
    }
}
