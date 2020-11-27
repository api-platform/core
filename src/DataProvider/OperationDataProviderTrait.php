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

namespace ApiPlatform\Core\DataProvider;

use ApiPlatform\Core\Exception\InvalidIdentifierException;
use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Identifier\IdentifierConverterInterface;

/**
 * @internal
 */
trait OperationDataProviderTrait
{
    /**
     * @var CollectionDataProviderInterface
     */
    private $collectionDataProvider;

    /**
     * @var ItemDataProviderInterface
     */
    private $itemDataProvider;

    /**
     * @var SubresourceDataProviderInterface|null
     */
    private $subresourceDataProvider;

    /**
     * @var IdentifierConverterInterface|null
     */
    private $identifierConverter;

    /**
     * Retrieves data for a collection operation.
     *
     * @return iterable
     */
    private function getCollectionData(array $attributes, array $context)
    {
        return $this->collectionDataProvider->getCollection($attributes['resource_class'], $attributes['collection_operation_name'], $context);
    }

    /**
     * Gets data for an item operation.
     *
     * @return object|null
     */
    private function getItemData($identifiers, array $attributes, array $context)
    {
        return $this->itemDataProvider->getItem($attributes['resource_class'], $identifiers, $attributes['item_operation_name'], $context);
    }

    /**
     * Gets data for a nested operation.
     *
     * @throws RuntimeException
     *
     * @return array|object|null
     */
    private function getSubresourceData($identifiers, array $attributes, array $context)
    {
        if (null === $this->subresourceDataProvider) {
            throw new RuntimeException('Subresources not supported');
        }

        $subresourceIdentifiers = [];
        foreach ($attributes['subresource_context']['identifiers'] as list($identifier, , $isItem, $identifiedBy)) {
            if ($isItem) {
                $subresourceIdentifiers[$identifier] = [$identifiedBy[0] => current($identifiers)];
                next($identifiers);
                continue;
            }

            $subresourceIdentifiers[$identifier] = [];
        }

        return $this->subresourceDataProvider->getSubresource($attributes['resource_class'], $subresourceIdentifiers, $attributes['subresource_context'] + $context, $attributes['subresource_operation_name']);
    }

    /**
     * @param array $parameters - usually comes from $request->attributes->all()
     *
     * @throws InvalidIdentifierException
     */
    private function extractIdentifiers(array $parameters, array $attributes)
    {
        $identifiersKeys = $attributes['identified_by'] ?? [];
        $identifiers = [];

        if (isset($attributes['subresource_context'])) {
            $subresourceIdentifiersKeys = [];
            $i = 0;
            foreach ($attributes['subresource_context']['identifiers'] as list($identifier, , $isItem)) {
                if ($isItem) {
                    $subresourceIdentifiersKeys[] = $identifiersKeys[$i++] ?? $identifier;
                }
            }
            $identifiersKeys = $subresourceIdentifiersKeys;
        }

        $identifiersNumber = \count($identifiersKeys);
        foreach ($identifiersKeys as $identifier) {
            if (!isset($parameters[$identifier])) {
                if ($attributes['has_composite_identifier']) {
                    $identifiers = CompositeIdentifierParser::parse($parameters['id']);
                    if (($currentIdentifiersNumber = \count($identifiers)) !== $identifiersNumber) {
                        throw new InvalidIdentifierException(sprintf('Expected %d identifiers, got %d', $identifiersNumber, $currentIdentifiersNumber));
                    }

                    return $identifiers;
                }

                throw new InvalidIdentifierException(sprintf('Parameter "%s" not found', $identifier));
            }

            $identifiers[$identifier] = $parameters[$identifier];
        }

        return $this->identifierConverter->convert($identifiers, $attributes['resource_class']);
    }
}
