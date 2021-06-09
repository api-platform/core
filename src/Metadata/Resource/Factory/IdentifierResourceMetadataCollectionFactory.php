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

namespace ApiPlatform\Metadata\Resource\Factory;

use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;

/**
 * Helps creating metadata on the Resource based on the properties of this same resource. Computes "identifiers".
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 * @experimental
 */
final class IdentifierResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    private $decorated;
    private $propertyNameCollectionFactory;
    private $propertyMetadataFactory;

    public function __construct(ResourceMetadataCollectionFactoryInterface $decorated, PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory)
    {
        $this->decorated = $decorated;
        $this->propertyNameCollectionFactory = $propertyNameCollectionFactory;
        $this->propertyMetadataFactory = $propertyMetadataFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass): ResourceMetadataCollection
    {
        $resourceMetadataCollection = $this->decorated->create($resourceClass);

        $identifiers = null;
        foreach ($resourceMetadataCollection as $i => $resource) {
            if (!$resource->getIdentifiers()) {
                $resource = $resource->withIdentifiers($identifiers ?: ($identifiers = $this->getIdentifiersFromResourceClass($resourceClass)));
            } else {
                $identifiers = $resource->getIdentifiers();
            }

            $resource = $resource->withIdentifiers($this->normalizeIdentifiers($resource->getIdentifiers(), $resourceClass));

            $operations = $resource->getOperations();
            // Copy identifiers to operations if not defined
            foreach ($operations as $key => $operation) {
                if ($identifiers && !$operation->getIdentifiers() && !$operation->isCollection()) {
                    if (0 === $i && 1 < \count($identifiers) && null === $operation->getCompositeIdentifier()) {
                        trigger_deprecation('api-platform/core', '2.7', sprintf('You have multiple identifiers on the resource "%s" but did not specify the "compositeIdentifier" property, we will set this to "true". Not specifying this attribute in 3.0 will break.', $resourceClass));
                        $operation = $operation->withCompositeIdentifier(true);
                    }

                    $operation = $operation->withIdentifiers($identifiers);
                }

                $operations->add($key, $operation->withIdentifiers($this->normalizeIdentifiers($operation->getIdentifiers(), $resourceClass)));
            }

            $resourceMetadataCollection[$i] = $resource->withOperations($operations);
        }

        return $resourceMetadataCollection;
    }

    private function normalizeIdentifiers($identifiers, string $resourceClass): array
    {
        if (!$identifiers) {
            return [];
        }

        if (\is_string($identifiers)) {
            return [$identifiers => [$resourceClass, $identifiers]];
        }

        $normalized = [];

        foreach ($identifiers as $parameterName => $identifier) {
            if (\is_int($parameterName)) {
                $normalized[$identifier] = [$resourceClass, $identifier];
            } elseif (\is_string($identifier)) {
                $normalized[$parameterName] = [$resourceClass, $identifier];
            } else {
                $normalized[$parameterName] = $identifier;
            }
        }

        return $normalized;
    }

    private function getIdentifiersFromResourceClass(string $resourceClass): array
    {
        $identifiers = [];
        $hasIdProperty = false;
        foreach ($this->propertyNameCollectionFactory->create($resourceClass) as $property) {
            $hasIdProperty = 'id' === $property;
            if ($this->propertyMetadataFactory->create($resourceClass, $property)->isIdentifier() ?? false) {
                $identifiers[$property] = [$resourceClass, $property];
            }
        }

        if (!$identifiers) {
            if ($hasIdProperty) {
                return ['id' => [$resourceClass, 'id']];
            }

            return $identifiers;
        }

        return $identifiers;
    }
}
