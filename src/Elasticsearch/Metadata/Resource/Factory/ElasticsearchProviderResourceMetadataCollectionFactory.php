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
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;

final class ElasticsearchProviderResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    private readonly QueryLanguage $defaultQueryLanguage;

    public function __construct(
        private readonly ResourceMetadataCollectionFactoryInterface $decorated,
        QueryLanguage|string $defaultQueryLanguage = QueryLanguage::Dsl,
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

                    $graphQlOperations[$operationName] = $graphQlOperation->withProvider($this->getProvider($graphQlOperation));
                }

                $resourceMetadata = $resourceMetadata->withGraphQlOperations($graphQlOperations);
            }

            $resourceMetadataCollection[$i] = $resourceMetadata;
        }

        return $resourceMetadataCollection;
    }

    private function getProvider(Operation $operation): string
    {
        if (!$operation instanceof CollectionOperationInterface) {
            // items are always fetched through the document GET API, whatever the query language
            return ItemProvider::class;
        }

        /** @var Options $options */
        $options = $operation->getStateOptions();

        return QueryLanguage::Esql === ($options->getQueryLanguage() ?? $this->defaultQueryLanguage) ? EsqlCollectionProvider::class : CollectionProvider::class;
    }
}
