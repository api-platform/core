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

use ApiPlatform\Elasticsearch\Metadata\Operation as ElasticsearchOperation;
use ApiPlatform\Elasticsearch\State\ElasticsearchCollectionProvider;
use ApiPlatform\Elasticsearch\State\ElasticsearchItemProvider;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
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
                foreach ($operations as $operationName => $operation) {
                    $operations->add($operationName, $this->configureOperation($operation, $resourceClass) ?? $operation);
                }
                $resourceMetadata = $resourceMetadata->withOperations($operations);
            }

            // $graphqlOperations and $operations have not same type, we cannot combine function
            if ($graphQlOperations = $resourceMetadata->getGraphQlOperations()) {
                foreach ($graphQlOperations as $operationName => $graphQlOperation) {
                    $graphQlOperations[$operationName] = $this->configureOperation($graphQlOperation, $resourceClass) ?? $graphQlOperation;
                }
                $resourceMetadata = $resourceMetadata->withGraphQlOperations($graphQlOperations);
            }

            $resourceMetadataCollection[$i] = $resourceMetadata;
        }

        return $resourceMetadataCollection;
    }

    private function configureOperation(Operation $operation, string $resourceClass): ?ElasticsearchOperation
    {
        if (!$operation instanceof ElasticsearchOperation) {
            return null;
        }

        $isCollection = $operation instanceof CollectionOperationInterface;
        if (false === $operation->getElasticsearch()) {
            throw new \LogicException(sprintf('You cannot disable elasticsearch with %s, use %s instead', ElasticsearchOperation::class, Operation::class));
        }

        return $operation->withProvider($isCollection ? ElasticsearchCollectionProvider::class : ElasticsearchItemProvider::class);
    }
}
