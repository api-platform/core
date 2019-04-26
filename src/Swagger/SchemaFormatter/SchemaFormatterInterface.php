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

namespace ApiPlatform\Core\Swagger\SchemaFormatter;

use ApiPlatform\Core\Metadata\Property\PropertyMetadata;

interface SchemaFormatterInterface
{
    /**
     * Returns if the mimetype is supported by this formatter.
     *
     * @param string $mimeType
     *
     * @return bool
     */
    public function supports(string $mimeType): bool;

    /**
     * Builds the basic schema if needed for this mimetype.
     *
     * @return array
     */
    public function buildBaseSchemaFormat(): array;

    /**
     * Sets the property in the correct fields for this mime type.
     *
     * @param \ArrayObject $definitionSchema
     * @param $normalizedPropertyName
     * @param \ArrayObject $property
     * @param PropertyMetadata $propertyMetadata
     */
    public function setProperty(\ArrayObject $definitionSchema, $normalizedPropertyName, \ArrayObject $property, PropertyMetadata $propertyMetadata): void;
}
