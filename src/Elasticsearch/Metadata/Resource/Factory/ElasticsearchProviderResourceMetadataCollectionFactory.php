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

use ApiPlatform\Elasticsearch\State\CollectionProvider;
use ApiPlatform\Elasticsearch\State\ItemProvider;
use ApiPlatform\Elasticsearch\State\Options;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\Util\Inflector;
use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Elasticsearch\Common\Exceptions\NoNodesAvailableException;

final class ElasticsearchProviderResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    public function __construct(private readonly ?Client $client, private readonly ResourceMetadataCollectionFactoryInterface $decorated, private readonly bool $triggerDeprecation = true) // @phpstan-ignore-line
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
                foreach ($resourceMetadata->getOperations() as $operationName => $operation) {
                    if ($operation->getProvider()) {
                        continue;
                    }

                    if (null !== ($elasticsearch = $operation->getElasticsearch())) {
                        trigger_deprecation('api-platform/core', '3.1', sprintf('The "elasticsearch" property is deprecated. Use a stateOptions: "%s" instead.', Options::class));
                    }

                    $hasElasticsearch = true === $elasticsearch || $operation->getStateOptions() instanceof Options;

                    // Old behavior in ES < 8
                    if ($this->client instanceof LegacyClient && $this->hasIndices($operation)) { // @phpstan-ignore-line
                        $hasElasticsearch = true;
                    }

                    if (!$hasElasticsearch) {
                        continue;
                    }

                    $operations->add($operationName, $operation->withProvider($operation instanceof CollectionOperationInterface ? CollectionProvider::class : ItemProvider::class));
                }

                $resourceMetadata = $resourceMetadata->withOperations($operations);
            }

            $graphQlOperations = $resourceMetadata->getGraphQlOperations();

            if ($graphQlOperations) {
                foreach ($graphQlOperations as $operationName => $graphQlOperation) {
                    if ($graphQlOperation->getProvider()) {
                        continue;
                    }

                    if (null !== ($elasticsearch = $graphQlOperation->getElasticsearch())) {
                        trigger_deprecation('api-platform/core', '3.1', sprintf('The "elasticsearch" property is deprecated. Use a stateOptions: "%s" instead.', Options::class));
                    }

                    $hasElasticsearch = true === $elasticsearch || $graphQlOperation->getStateOptions() instanceof Options;

                    // Old behavior in ES < 8
                    if ($this->client instanceof LegacyClient && $this->hasIndices($operation)) { // @phpstan-ignore-line
                        $hasElasticsearch = true;
                    }

                    if (!$hasElasticsearch) {
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
        $shortName = $operation->getShortName();
        $index = Inflector::tableize($shortName);

        try {
            $this->client->cat()->indices(['index' => $index]); // @phpstan-ignore-line

            return true;
        } catch (Missing404Exception|NoNodesAvailableException) { // @phpstan-ignore-line
            return false;
        }
    }
}
