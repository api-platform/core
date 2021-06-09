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
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use ApiPlatform\Core\DataProvider\SubresourceDataProviderInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;

/**
 * @deprecated
 */
final class LegacyDataProviderState implements ProviderInterface
{
    private $itemDataProvider;
    private $collectionDataProvider;
    private $subresourceDataProvider;
    private $propertyNameCollectionFactory;
    private $propertyMetadataFactory;

    public function __construct(ItemDataProviderInterface $itemDataProvider, CollectionDataProviderInterface $collectionDataProvider, SubresourceDataProviderInterface $subresourceDataProvider, PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory)
    {
        $this->itemDataProvider = $itemDataProvider;
        $this->collectionDataProvider = $collectionDataProvider;
        $this->subresourceDataProvider = $subresourceDataProvider;
        $this->propertyNameCollectionFactory = $propertyNameCollectionFactory;
        $this->propertyMetadataFactory = $propertyMetadataFactory;
    }

    public function provide(string $resourceClass, array $identifiers = [], ?string $operationName = null, array $context = [])
    {
        $operation = $context['operation'] ?? null;
        if ($operation && (
            ($operation->getExtraProperties()['is_legacy_subresource'] ?? false) ||
            ($operation->getExtraProperties()['is_alternate_resource_metadata'] ?? false)
        )) {
            $subresourceContext = ['filters' => $context['filters'] ?? [], 'identifiers' => $operation->getExtraProperties()['legacy_subresource_identifiers'] ?? [], 'property' => $operation->getExtraProperties()['legacy_subresource_property'] ?? null, 'collection' => $operation->isCollection()] + $context;
            $subresourceIdentifiers = [];
            foreach ($operation->getIdentifiers() as $parameterName => [$class, $property]) {
                $subresourceIdentifiers[$parameterName] = [$property => $identifiers[$parameterName]];
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

    public function supports(string $resourceClass, array $identifiers = [], ?string $operationName = null, array $context = []): bool
    {
        if ($identifiers && $this->itemDataProvider instanceof RestrictedDataProviderInterface) {
            return $this->itemDataProvider->supports($resourceClass, $operationName, $context);
        }

        if ($this->collectionDataProvider instanceof RestrictedDataProviderInterface) {
            return $this->collectionDataProvider->supports($resourceClass, $operationName, $context);
        }

        return false;
    }
}
