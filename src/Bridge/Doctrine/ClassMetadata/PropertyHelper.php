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

namespace ApiPlatform\Core\Bridge\Doctrine\ClassMetadata;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\DBAL\Types\Type;

/**
 * Helper for getting information regarding a property using the resource metadata.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Théo FIDRY <theo.fidry@gmail.com>
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class PropertyHelper
{
    private $managerRegistry;

    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * Determines whether the given property is mapped.
     */
    public function isPropertyMapped(string $property, string $resourceClass, bool $allowAssociation = false): bool
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
    public function isPropertyNested(string $property, ?string $resourceClass): bool
    {
        $pos = strpos($property, '.');
        if (false === $pos) {
            return false;
        }

        return null !== $resourceClass && $this->getClassMetadata($resourceClass)->hasAssociation(substr($property, 0, $pos));
    }

    /**
     * Determines whether the given property is embedded.
     */
    public function isPropertyEmbedded(string $property, string $resourceClass): bool
    {
        return false !== strpos($property, '.') && $this->getClassMetadata($resourceClass)->hasField($property);
    }

    /**
     * Splits the given property into parts.
     *
     * Returns an array with the following keys:
     *   - associations: array of associations according to nesting order
     *   - field: string holding the actual field (leaf node)
     */
    public function splitPropertyParts(string $property, ?string $resourceClass): array
    {
        $parts = explode('.', $property);

        if (null === $resourceClass) {
            return [
                'associations' => \array_slice($parts, 0, -1),
                'field' => end($parts),
            ];
        }

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
     *
     * @return Type|string|null
     */
    public function getDoctrineFieldType(string $property, string $resourceClass)
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
    public function getNestedMetadata(string $resourceClass, array $associations): ClassMetadata
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

    /**
     * Gets class metadata for the given resource.
     */
    public function getClassMetadata(string $resourceClass): ClassMetadata
    {
        return $this
            ->managerRegistry
            ->getManagerForClass($resourceClass)
            ->getClassMetadata($resourceClass);
    }
}
