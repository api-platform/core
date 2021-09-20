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
final class UriVariablesResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
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

        $uriVariables = null;
        foreach ($resourceMetadataCollection as $i => $resource) {
            if (!$resource->getUriVariables()) {
                $resource = $resource->withUriVariables($uriVariables ?: ($uriVariables = $this->getUriVariablesFromResourceClass($resourceClass, $resource->getCompositeIdentifier())));
            } else {
                $uriVariables = $resource->getUriVariables();
            }

            $resource = $resource->withUriVariables($this->normalizeUriVariables($resource->getUriVariables(), $resourceClass, $resource->getCompositeIdentifier()));

            $operations = $resource->getOperations();
            // Copy identifiers to operations if not defined
            foreach ($operations as $key => $operation) {
                if ($uriVariables && !$operation->getUriVariables() && !$operation->isCollection()) {
                    $operation = $operation->withUriVariables($uriVariables);
                }

                $operations->add($key, $operation->withUriVariables($this->normalizeUriVariables($operation->getUriVariables(), $resourceClass, $operation->getCompositeIdentifier())));
            }

            $resourceMetadataCollection[$i] = $resource->withOperations($operations);
        }

        return $resourceMetadataCollection;
    }

    private function normalizeUriVariables($identifiers, string $resourceClass, bool $hasCompositeIdentifier = null): ?array
    {
        if (!$identifiers) {
            return null;
        }

        if (\is_string($identifiers)) {
            return [$identifiers => ['class' => $resourceClass, 'identifiers' => [$identifiers]]];
        }

        $normalized = [];

        foreach ($identifiers as $parameterName => $identifier) {
            if (\is_int($parameterName)) {
                $normalized[$identifier] = ['class' => $resourceClass, 'identifiers' => [$identifier]];
            } elseif (\is_string($identifier)) {
                $normalized[$parameterName] = ['class' => $resourceClass, 'identifiers' => [$identifier]];
            } elseif (\is_array($identifier) && !isset($identifier['class'])) {
                $normalized[$parameterName] = ['class' => $resourceClass, 'identifiers' => $identifier];
            } else {
                $normalized[$parameterName] = $identifier;
            }

            if (null !== $hasCompositeIdentifier) {
                $normalized[$parameterName]['composite_identifier'] = $hasCompositeIdentifier;
            }
        }

        return $normalized;
    }

    private function getUriVariablesFromResourceClass(string $resourceClass, bool $hasCompositeIdentifier = null): ?array
    {
        $identifiers = [];
        $hasIdProperty = false;
        foreach ($this->propertyNameCollectionFactory->create($resourceClass) as $property) {
            if (!$hasIdProperty) {
                $hasIdProperty = 'id' === $property;
            }
            if ($this->propertyMetadataFactory->create($resourceClass, $property)->isIdentifier() ?? false) {
                $identifiers[] = $property;
            }
        }

        if (!$identifiers) {
            return $hasIdProperty ? ['id' => ['class' => $resourceClass, 'identifiers' => ['id']]] : null;
        }

        if (!($hasCompositeIdentifier ?? true)) {
            $uriVariables = [];
            foreach ($identifiers as $identifier) {
                $uriVariables[$identifier] = ['class' => $resourceClass, 'identifiers' => [$identifier], 'composite_identifier' => false];
            }

            return $uriVariables;
        }

        $uriVariable = ['class' => $resourceClass, 'identifiers' => $identifiers];
        $parameterName = $identifiers[0];

        if (1 < \count($identifiers)) {
            $parameterName = 'id';
            $uriVariable['composite_identifier'] = true;
        }

        return [$parameterName => $uriVariable];
    }
}
