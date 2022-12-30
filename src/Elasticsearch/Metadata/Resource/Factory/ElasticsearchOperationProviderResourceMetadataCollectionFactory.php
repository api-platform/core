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

namespace ApiPlatform\Elasticsearch\Metadata\Resource\Factory;

use ApiPlatform\Elasticsearch\State\ElasticsearchCollectionProvider;
use ApiPlatform\Elasticsearch\State\ElasticsearchItemProvider;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\GraphQl\Operation as GraphQlOperation;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\PersistenceMeansInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;

/**
 * @internal
 */
class ElasticsearchOperationProviderResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    public function __construct(
        private readonly ResourceMetadataCollectionFactoryInterface $decorated,
    ) {
    }

    public function create(string $resourceClass): ResourceMetadataCollection
    {
        $resourceMetadataCollection = $this->decorated->create($resourceClass);

        foreach ($resourceMetadataCollection as $i => $resourceMetadata) {
            if ($operations = $resourceMetadata->getOperations()) {
                $resourceMetadata = $resourceMetadata->withOperations(self::configureOperations($operations));
            }

            // $graphqlOperations and $operations have not same type, we cannot combine function
            if ($graphQlOperations = $resourceMetadata->getGraphQlOperations()) {
                $resourceMetadata = $resourceMetadata->withGraphQlOperations(self::configureOperations($graphQlOperations));
            }

            $resourceMetadataCollection[$i] = $resourceMetadata;
        }

        return $resourceMetadataCollection;
    }

    /**
     * @template  T of Operations|array<GraphQlOperation>
     *
     * @phpstan-param  T $operations
     *
     * @phpstan-return T
     */
    private static function configureOperations(Operations|array $operations): Operations|array
    {
        foreach ($operations as $operationName => $operation) {
            $configuredOperation = self::configureOperation($operation);
            if ($configuredOperation === $operation) {
                continue;
            }
            if ($operations instanceof Operations) {
                $operations->add($operationName, $configuredOperation);
            } else {
                $operations[$operationName] = $configuredOperation;
            }
        }

        return $operations;
    }

    private static function configureOperation(Operation $operation): Operation
    {
        if (null !== $operation->getProvider()) {
            return $operation;
        }
        if (!$operation->getPersistenceMeans() instanceof PersistenceMeansInterface) {
            return $operation;
        }
        if (false === $operation->getElasticsearch()) {
            throw new \LogicException('You cannot set $elasticsearch to false while configuring an elasticsearch document');
        }

        return $operation->withProvider($operation instanceof CollectionOperationInterface ? ElasticsearchCollectionProvider::class : ElasticsearchItemProvider::class);
    }
}
