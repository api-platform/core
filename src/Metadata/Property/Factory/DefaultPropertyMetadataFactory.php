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

use ApiPlatform\Exception\PropertyNotFoundException;
use ApiPlatform\Metadata\ApiProperty;

/**
 * Populates defaults values of the resource properties using the default PHP values of properties.
 */
final class DefaultPropertyMetadataFactory implements PropertyMetadataFactoryInterface
{
    private $decorated;

    public function __construct(PropertyMetadataFactoryInterface $decorated = null)
    {
        $this->decorated = $decorated;
    }

    public function create(string $resourceClass, string $property, array $options = []): ApiProperty
    {
        if (null === $this->decorated) {
            $propertyMetadata = new ApiProperty();
        } else {
            try {
                $propertyMetadata = $this->decorated->create($resourceClass, $property, $options);
            } catch (PropertyNotFoundException $propertyNotFoundException) {
                $propertyMetadata = new ApiProperty();
            }
        }

        try {
            $reflectionClass = new \ReflectionClass($resourceClass);
        } catch (\ReflectionException $reflectionException) {
            return $propertyMetadata;
        }

        $defaultProperties = $reflectionClass->getDefaultProperties();

        if (!\array_key_exists($property, $defaultProperties) || null === ($defaultProperty = $defaultProperties[$property])) {
            return $propertyMetadata;
        }

        return $propertyMetadata->withDefault($defaultProperty);
    }
}
