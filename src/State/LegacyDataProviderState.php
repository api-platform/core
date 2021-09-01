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

    public function provide(string $resourceClass, array $identifiers = [], ?string $operationName = null, array $context = [])
    {
        $operation = $context['operation'] ?? null;
        if ($operation && (
            ($operation->getExtraProperties()['is_legacy_subresource'] ?? false) ||
            ($operation->getExtraProperties()['is_alternate_resource_metadata'] ?? false)
        )) {
            $subresourceContext = ['filters' => $context['filters'] ?? [], 'identifiers' => $operation->getExtraProperties()['legacy_subresource_identifiers'] ?? [], 'property' => $operation->getExtraProperties()['legacy_subresource_property'] ?? null, 'collection' => $operation->isCollection()] + $context;
            $subresourceIdentifiers = [];
            foreach ($operation->getUriVariables() as $parameterName => $uriTemplateDefinition) {
                $subresourceIdentifiers[$parameterName] = [$uriTemplateDefinition['identifiers'][0] => $identifiers[$parameterName]];
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
        $operation = $context['operation'] ?? $this->resourceMetadataCollectionFactory->create($resourceClass)->getOperation($operationName);
        if (!($operation->getExtraProperties()['is_legacy_resource_metadata'] ?? false)) {
        // FIXME: uncomment the following line when all providers will be ported
        //    return false;
        }

        if ($identifiers && $this->itemDataProvider instanceof RestrictedDataProviderInterface) {
            return $this->itemDataProvider->supports($resourceClass, $operationName, $context);
        }

        if ($this->collectionDataProvider instanceof RestrictedDataProviderInterface) {
            return $this->collectionDataProvider->supports($resourceClass, $operationName, $context);
        }

        return false;
    }
}
