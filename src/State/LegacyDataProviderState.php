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

namespace ApiPlatform\State;

use ApiPlatform\Core\DataProvider\CollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\ContextAwareCollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\SubresourceDataProviderInterface;
use ApiPlatform\Metadata\AbstractOperation;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;

/**
 * @internal
 *
 * @deprecated
 */
final class LegacyDataProviderState implements ProviderInterface
{
    private $itemDataProvider;
    private $collectionDataProvider;
    private $subresourceDataProvider;
    private $resourceMetadataCollectionFactory;

    public function __construct(ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory, ItemDataProviderInterface $itemDataProvider, CollectionDataProviderInterface $collectionDataProvider, SubresourceDataProviderInterface $subresourceDataProvider)
    {
        $this->itemDataProvider = $itemDataProvider;
        $this->collectionDataProvider = $collectionDataProvider;
        $this->subresourceDataProvider = $subresourceDataProvider;
        $this->resourceMetadataCollectionFactory = $resourceMetadataCollectionFactory;
    }

    public function provide(AbstractOperation $operation, array $uriVariables = [], array $context = [])
    {
        exit('hello');
        if ($operation && (
                ($operation->getExtraProperties()['is_legacy_subresource'] ?? false) ||
                ($operation->getExtraProperties()['is_alternate_resource_metadata'] ?? false)
            )) {
            $subresourceContext = ['filters' => $context['filters'] ?? [], 'identifiers' => $operation->getExtraProperties()['legacy_subresource_identifiers'] ?? [], 'property' => $operation->getExtraProperties()['legacy_subresource_property'] ?? null, 'collection' => $operation instanceof CollectionOperationInterface] + $context;
            $subresourceIdentifiers = [];
            foreach ($operation->getUriVariables() as $parameterName => $uriTemplateDefinition) {
                $subresourceIdentifiers[$parameterName] = [$uriTemplateDefinition->getIdentifiers()[0] => $identifiers[$parameterName]];
            }

            return $this->subresourceDataProvider->getSubresource($resourceClass, $subresourceIdentifiers, $subresourceContext, $operationName);
        }

        if ($identifiers) {
            return $this->itemDataProvider->getItem($resourceClass, $identifiers, $operationName, $context);
        }

        if ($this->collectionDataProvider instanceof ContextAwareCollectionDataProviderInterface) {
            return $this->collectionDataProvider->getCollection($resourceClass, $operationName, $context);
        }

        return $this->collectionDataProvider->getCollection($resourceClass, $operationName);
    }
}
