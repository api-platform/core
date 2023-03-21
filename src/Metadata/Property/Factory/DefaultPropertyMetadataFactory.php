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

namespace ApiPlatform\Metadata\Property\Factory;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Exception\PropertyNotFoundException;

/**
 * Populates defaults values of the resource properties using the default PHP values of properties.
 */
final class DefaultPropertyMetadataFactory implements PropertyMetadataFactoryInterface
{
    public function __construct(private readonly ?PropertyMetadataFactoryInterface $decorated = null)
    {
    }

    public function create(string $resourceClass, string $property, array $options = []): ApiProperty
    {
        if (null === $this->decorated) {
            $propertyMetadata = new ApiProperty();
        } else {
            try {
                $propertyMetadata = $this->decorated->create($resourceClass, $property, $options);
            } catch (PropertyNotFoundException) {
                $propertyMetadata = new ApiProperty();
            }
        }

        try {
            $reflectionClass = new \ReflectionClass($resourceClass);
        } catch (\ReflectionException) {
            return $propertyMetadata;
        }

        $defaultProperties = $reflectionClass->getDefaultProperties();

        if (!\array_key_exists($property, $defaultProperties) || null === ($defaultProperty = $defaultProperties[$property])) {
            return $propertyMetadata;
        }

        return $propertyMetadata->withDefault($defaultProperty);
    }
}
