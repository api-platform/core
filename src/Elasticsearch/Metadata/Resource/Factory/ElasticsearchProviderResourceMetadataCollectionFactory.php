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
use ApiPlatform\Elasticsearch\State\CollectionProvider;
use ApiPlatform\Elasticsearch\State\ItemProvider;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Util\Inflector;
use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Elasticsearch\Common\Exceptions\NoNodesAvailableException;

final class ElasticsearchProviderResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    public function __construct(private readonly Client $client, private readonly ResourceMetadataCollectionFactoryInterface $decorated, private readonly bool $triggerDeprecation = true)
    {
        if ($this->triggerDeprecation) {
            trigger_deprecation('api-platform/core', '3.1', '%s is deprecated and will be removed in v4', self::class);
        }
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
                    if ($operation instanceof ElasticsearchOperation) {
                        continue;
                    }

                    if ($this->hasIndices($operation)) {
                        $operation = $operation->withElasticsearch(true);
                    }

                    if (null !== $operation->getProvider() || false === ($operation->getElasticsearch() ?? false)) {
                        continue;
                    }

                    $operations->add($operationName, $operation->withProvider($operation instanceof CollectionOperationInterface ? CollectionProvider::class : ItemProvider::class));
                }

                $resourceMetadata = $resourceMetadata->withOperations($operations);
            }

            $graphQlOperations = $resourceMetadata->getGraphQlOperations();

            if ($graphQlOperations) {
                foreach ($graphQlOperations as $operationName => $graphQlOperation) {
                    if ($graphQlOperation instanceof ElasticsearchOperation) {
                        continue;
                    }

                    if ($this->hasIndices($graphQlOperation)) {
                        $graphQlOperation = $graphQlOperation->withElasticsearch(true);
                    }

                    if (null !== $graphQlOperation->getProvider() || false === ($graphQlOperation->getElasticsearch() ?? false)) {
                        continue;
                    }

                    $graphQlOperations[$operationName] = $graphQlOperation->withProvider($graphQlOperation instanceof CollectionOperationInterface ? CollectionProvider::class : ItemProvider::class);
                }

                $resourceMetadata = $resourceMetadata->withGraphQlOperations($graphQlOperations);
            }

            $resourceMetadataCollection[$i] = $resourceMetadata;
        }

        return $resourceMetadataCollection;
    }

    private function hasIndices(Operation $operation): bool
    {
        if (false === $operation->getElasticsearch()) {
            return false;
        }

        $shortName = $operation->getShortName();
        $index = Inflector::tableize($shortName);

        try {
            $this->client->cat()->indices(['index' => $index]);

            return true;
        } catch (Missing404Exception) {
            return false;
        } catch (NoNodesAvailableException) {
            return false;
        }
    }
}
