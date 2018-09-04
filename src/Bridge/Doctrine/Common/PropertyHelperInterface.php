<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Bridge\Doctrine\Common;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\DBAL\Types\Type;

/**
 * Helper for getting information regarding a property using the resource metadata.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Théo FIDRY <theo.fidry@gmail.com>
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
interface PropertyHelperInterface
{
    /**
     * Determines whether the given property is mapped.
     */
    public function isPropertyMapped(string $property, string $resourceClass, bool $allowAssociation = false): bool;

    /**
     * Determines whether the given property is nested.
     */
    public function isPropertyNested(string $property, ?string $resourceClass): bool;

    /**
     * Determines whether the given property is embedded.
     */
    public function isPropertyEmbedded(string $property, string $resourceClass): bool;

    /**
     * Splits the given property into parts.
     *
     * Returns an array with the following keys:
     *   - associations: array of associations according to nesting order
     *   - field: string holding the actual field (leaf node)
     */
    public function splitPropertyParts(string $property, ?string $resourceClass): array;

    /**
     * Gets the Doctrine Type of a given property/resourceClass.
     *
     * @return Type|string|null
     */
    public function getDoctrineFieldType(string $property, string $resourceClass);

    /**
     * Gets nested class metadata for the given resource.
     *
     * @param string[] $associations
     */
    public function getNestedMetadata(string $resourceClass, array $associations): ClassMetadata;

    /**
     * Gets class metadata for the given resource.
     */
    public function getClassMetadata(string $resourceClass): ClassMetadata;
}
