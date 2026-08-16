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

use ApiPlatform\Elasticsearch\Esql\State\CollectionProvider as EsqlCollectionProvider;
use ApiPlatform\Elasticsearch\State\CollectionProvider;
use ApiPlatform\Elasticsearch\State\ItemProvider;
use ApiPlatform\Elasticsearch\State\Options;
use ApiPlatform\Elasticsearch\State\QueryLanguage;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;

final class ElasticsearchProviderResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    private readonly QueryLanguage $defaultQueryLanguage;

    public function __construct(
        private readonly ResourceMetadataCollectionFactoryInterface $decorated,
        QueryLanguage|string $defaultQueryLanguage = QueryLanguage::Dsl,
        private readonly bool $esqlAvailable = true,
    ) {
        $this->defaultQueryLanguage = \is_string($defaultQueryLanguage) ? QueryLanguage::from($defaultQueryLanguage) : $defaultQueryLanguage;
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

                    if (!$operation->getStateOptions() instanceof Options) {
                        continue;
                    }

                    $operations->add($operationName, $operation->withProvider($this->getProvider($operation)));
                }

                $resourceMetadata = $resourceMetadata->withOperations($operations);
            }

            $graphQlOperations = $resourceMetadata->getGraphQlOperations();

            if ($graphQlOperations) {
                foreach ($graphQlOperations as $operationName => $graphQlOperation) {
                    if ($graphQlOperation->getProvider()) {
                        continue;
                    }

                    if (!$graphQlOperation->getStateOptions() instanceof Options) {
                        continue;
                    }

                    // ES|QL paginator implements partial pagination only, incompatible with GraphQL connections (totalCount/cursors)
                    $graphQlOperations[$operationName] = $graphQlOperation->withProvider($this->getProvider($graphQlOperation, esqlAllowed: false));
                }

                $resourceMetadata = $resourceMetadata->withGraphQlOperations($graphQlOperations);
            }

            $resourceMetadataCollection[$i] = $resourceMetadata;
        }

        return $resourceMetadataCollection;
    }

    /**
     * @param bool $esqlAllowed whether the operation may be served by the ES|QL provider
     */
    private function getProvider(Operation $operation, bool $esqlAllowed = true): string
    {
        if (!$operation instanceof CollectionOperationInterface) {
            // items are always fetched through the document GET API, whatever the query language
            return ItemProvider::class;
        }

        /** @var Options $options */
        $options = $operation->getStateOptions();

        if (!$esqlAllowed || QueryLanguage::Esql !== ($options->getQueryLanguage() ?? $this->defaultQueryLanguage)) {
            return CollectionProvider::class;
        }

        if (!$this->esqlAvailable) {
            throw new RuntimeException(\sprintf('ES|QL is not supported by the OpenSearch client, remove the "queryLanguage" state option of the operation "%s" or use the Elasticsearch client.', $operation->getName() ?? $operation->getShortName()));
        }

        return EsqlCollectionProvider::class;
    }
}
