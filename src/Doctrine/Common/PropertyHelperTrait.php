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

namespace ApiPlatform\Doctrine\Common;

use Doctrine\Persistence\Mapping\ClassMetadata;

/**
 * Helper trait for getting information regarding a property using the resource metadata.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Théo FIDRY <theo.fidry@gmail.com>
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
trait PropertyHelperTrait
{
    /**
     * Gets class metadata for the given resource.
     */
    abstract protected function getClassMetadata(string $resourceClass): ClassMetadata;

    /**
     * Determines whether the given property is mapped.
     */
    protected function isPropertyMapped(string $property, string $resourceClass, bool $allowAssociation = false): bool
    {
        if ($this->isPropertyNested($property, $resourceClass)) {
            $propertyParts = $this->splitPropertyParts($property, $resourceClass);
            $metadata = $this->getNestedMetadata($resourceClass, $propertyParts['associations']);
            $property = $propertyParts['field'];
        } else {
            $metadata = $this->getClassMetadata($resourceClass);
        }

        return $metadata->hasField($property) || ($allowAssociation && $metadata->hasAssociation($property));
    }

    /**
     * Determines whether the given property is nested.
     */
    protected function isPropertyNested(string $property, string $resourceClass): bool
    {
        $pos = strpos($property, '.');
        if (false === $pos) {
            return false;
        }

        return $this->getClassMetadata($resourceClass)->hasAssociation(substr($property, 0, $pos));
    }

    /**
     * Determines whether the given property is embedded.
     */
    protected function isPropertyEmbedded(string $property, string $resourceClass): bool
    {
        return str_contains($property, '.') && $this->getClassMetadata($resourceClass)->hasField($property);
    }

    /**
     * Splits the given property into parts.
     *
     * Returns an array with the following keys:
     *   - associations: array of associations according to nesting order
     *   - field: string holding the actual field (leaf node)
     */
    protected function splitPropertyParts(string $property, string $resourceClass): array
    {
        $parts = explode('.', $property);

        $metadata = $this->getClassMetadata($resourceClass);
        $slice = 0;

        foreach ($parts as $part) {
            if ($metadata->hasAssociation($part)) {
                $metadata = $this->getClassMetadata($metadata->getAssociationTargetClass($part));
                ++$slice;
            }
        }

        if (\count($parts) === $slice) {
            --$slice;
        }

        return [
            'associations' => \array_slice($parts, 0, $slice),
            'field' => implode('.', \array_slice($parts, $slice)),
        ];
    }

    /**
     * Gets the Doctrine Type of a given property/resourceClass.
     */
    protected function getDoctrineFieldType(string $property, string $resourceClass): ?string
    {
        $propertyParts = $this->splitPropertyParts($property, $resourceClass);
        $metadata = $this->getNestedMetadata($resourceClass, $propertyParts['associations']);

        return $metadata->getTypeOfField($propertyParts['field']);
    }

    /**
     * Gets nested class metadata for the given resource.
     *
     * @param string[] $associations
     */
    protected function getNestedMetadata(string $resourceClass, array $associations): ClassMetadata
    {
        $metadata = $this->getClassMetadata($resourceClass);

        foreach ($associations as $association) {
            if ($metadata->hasAssociation($association)) {
                $associationClass = $metadata->getAssociationTargetClass($association);

                $metadata = $this->getClassMetadata($associationClass);
            }
        }

        return $metadata;
    }
}
