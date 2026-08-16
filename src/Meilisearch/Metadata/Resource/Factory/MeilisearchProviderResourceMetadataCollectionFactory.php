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

namespace ApiPlatform\Meilisearch\Metadata\Resource\Factory;

use ApiPlatform\Meilisearch\State\CollectionProvider;
use ApiPlatform\Meilisearch\State\ItemProvider;
use ApiPlatform\Meilisearch\State\Options;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;

/**
 * Auto-assigns CollectionProvider/ItemProvider to any operation whose
 * stateOptions is a Meilisearch\State\Options instance and has no explicit
 * provider set -- so `stateOptions: new Options(index: 'movie')` is enough
 * on its own, no `provider:` needed. Mirrors
 * ElasticsearchProviderResourceMetadataCollectionFactory exactly.
 */
final class MeilisearchProviderResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    public function __construct(private readonly ResourceMetadataCollectionFactoryInterface $decorated)
    {
    }

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

                    $operations->add($operationName, $operation->withProvider($operation instanceof CollectionOperationInterface ? CollectionProvider::class : ItemProvider::class));
                }

                $resourceMetadata = $resourceMetadata->withOperations($operations);
            }

            $resourceMetadataCollection[$i] = $resourceMetadata;
        }

        return $resourceMetadataCollection;
    }
}
