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
use ApiPlatform\Doctrine\Odm\State\Options;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\Persistence\ManagerRegistry;

final class DoctrineMongoDbOdmResourceCollectionMetadataFactory implements ResourceMetadataCollectionFactoryInterface
{
    public function __construct(private readonly ManagerRegistry $managerRegistry, private readonly ResourceMetadataCollectionFactoryInterface $decorated)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass): ResourceMetadataCollection
    {
        $resourceMetadataCollection = $this->decorated->create($resourceClass);

        /** @var ApiResource $resourceMetadata */
        foreach ($resourceMetadataCollection as $i => $resourceMetadata) {
            $operations = $resourceMetadata->getOperations();

            if ($operations) {
                /** @var Operation $operation */
                foreach ($resourceMetadata->getOperations() as $operationName => $operation) {
                    $documentClass = $operation->getClass();
                    if (($options = $operation->getStateOptions()) && $options instanceof Options && $options->getDocumentClass()) {
                        $documentClass = $options->getDocumentClass();
                    }

                    if (!$this->managerRegistry->getManagerForClass($documentClass) instanceof DocumentManager) {
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

    private function addDefaults(Operation $operation): Operation
    {
        $options = $operation->getStateOptions() ?: new Options();
        if ($options instanceof Options && null === $options->getHandleLinks()) {
            $options = $options->withHandleLinks('api_platform.doctrine.odm.links_handler');
            $operation = $operation->withStateOptions($options);
        }

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
