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

namespace ApiPlatform\Doctrine\Orm\Metadata\Resource;

use ApiPlatform\Doctrine\Orm\State\CollectionProvider;
use ApiPlatform\Doctrine\Orm\State\ItemProvider;
use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\State\Util\StateOptionsTrait;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

final class DoctrineOrmResourceCollectionMetadataFactory implements ResourceMetadataCollectionFactoryInterface
{
    use StateOptionsTrait;

    public function __construct(private readonly ManagerRegistry $managerRegistry, private readonly ResourceMetadataCollectionFactoryInterface $decorated)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass): ResourceMetadataCollection
    {
        $resourceMetadataCollection = $this->decorated->create($resourceClass);

        foreach ($resourceMetadataCollection as $i => $resourceMetadata) {
            $operations = $resourceMetadata->getOperations();

            if ($operations) {
                foreach ($operations as $operationName => $operation) {
                    $entityClass = $this->getStateOptionsClass($operation, $operation->getClass(), Options::class);

                    $manager = $this->managerRegistry->getManagerForClass($entityClass);
                    if (!$manager instanceof EntityManagerInterface) {
                        continue;
                    }

                    $classMetadata = $manager->getClassMetadata($entityClass);
                    // @see https://www.doctrine-project.org/projects/doctrine-orm/en/3.5/reference/improving-performance.html#read-only-entities
                    // Read-Only allows to persist new entities of a kind and remove existing ones, they are just not considered for updates.
                    if ($classMetadata->isReadOnly && ($operation instanceof Put || $operation instanceof Patch)) {
                        $operations->remove($operationName);
                        continue;
                    }

                    $operations->add($operationName, $this->addDefaults($operation));
                }

                $resourceMetadata = $resourceMetadata->withOperations($operations);
            }

            $graphQlOperations = $resourceMetadata->getGraphQlOperations();

            if ($graphQlOperations) {
                foreach ($graphQlOperations as $operationName => $graphQlOperation) {
                    $entityClass = $this->getStateOptionsClass($graphQlOperation, $graphQlOperation->getClass(), Options::class);

                    if (!$this->managerRegistry->getManagerForClass($entityClass) instanceof EntityManagerInterface) {
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
        if (null === $operation->getProvider()) {
            $operation = $operation->withProvider($this->getProvider($operation));
        }

        $options = $operation->getStateOptions() ?: new Options();
        if ($options instanceof Options && null === $options->getHandleLinks()) {
            $options = $options->withHandleLinks('api_platform.doctrine.orm.links_handler');
            $operation = $operation->withStateOptions($options);
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
            return 'api_platform.doctrine.orm.state.remove_processor';
        }

        return 'api_platform.doctrine.orm.state.persist_processor';
    }
}
