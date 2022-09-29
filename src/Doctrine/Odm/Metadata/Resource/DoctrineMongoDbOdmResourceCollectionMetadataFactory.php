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

namespace ApiPlatform\Doctrine\Odm\Metadata\Resource;

use ApiPlatform\Doctrine\Odm\State\CollectionProvider;
use ApiPlatform\Doctrine\Odm\State\ItemProvider;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\Persistence\ManagerRegistry;

final class DoctrineMongoDbOdmResourceCollectionMetadataFactory implements ResourceMetadataCollectionFactoryInterface
{
    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;

    /**
     * @var ResourceMetadataCollectionFactoryInterface
     */
    private $decorated;

    public function __construct(ManagerRegistry $managerRegistry, ResourceMetadataCollectionFactoryInterface $decorated)
    {
        $this->decorated = $decorated;
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * {@inheritDoc}
     */
    public function create(string $resourceClass): ResourceMetadataCollection
    {
        $resourceMetadataCollection = $this->decorated->create($resourceClass);

        foreach ($resourceMetadataCollection as $i => $resourceMetadata) {
            $operations = $resourceMetadata->getOperations();

            if ($operations) {
                foreach ($resourceMetadata->getOperations() as $operationName => $operation) {
                    if (!$this->managerRegistry->getManagerForClass($operation->getClass()) instanceof DocumentManager) {
                        continue;
                    }

                    $operations->add($operationName, $this->addDefaults($operation));
                }

                $resourceMetadata = $resourceMetadata->withOperations($operations);
            }

            $graphQlOperations = $resourceMetadata->getGraphQlOperations();

            if ($graphQlOperations) {
                foreach ($graphQlOperations as $operationName => $graphQlOperation) {
                    if (!$this->managerRegistry->getManagerForClass($graphQlOperation->getClass()) instanceof DocumentManager) {
                        continue;
                    }

                    $graphQlOperations[$operationName] = $this->addDefaults($graphQlOperation);
                }

                $resourceMetadata = $resourceMetadata->withGraphQlOperations($graphQlOperations);
            }

            $resourceMetadataCollection[$i] = $resourceMetadata;
        }

        return $resourceMetadataCollection;
    }

    private function addDefaults($operation): Operation
    {
        if (null === $operation->getProvider()) {
            $operation = $operation->withProvider($this->getProvider($operation));
        }

        if (null === $operation->getProcessor()) {
            $operation = $operation->withProcessor($this->getProcessor($operation));
        }

        return $operation;
    }

    private function getProvider(Operation $operation): string
    {
        if ($operation instanceof CollectionOperationInterface) {
            return CollectionProvider::class;
        }

        return ItemProvider::class;
    }

    private function getProcessor(Operation $operation): string
    {
        if ($operation instanceof DeleteOperationInterface) {
            return 'api_platform.doctrine_mongodb.odm.state.remove_processor';
        }

        return 'api_platform.doctrine_mongodb.odm.state.persist_processor';
    }
}
