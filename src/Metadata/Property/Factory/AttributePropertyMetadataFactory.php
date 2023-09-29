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

use ApiPlatform\JsonSchema\Metadata\Property\Factory\SchemaPropertyMetadataFactory;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Exception\PropertyNotFoundException;
use ApiPlatform\Metadata\Util\Reflection;

/**
 * Creates a property metadata from {@see ApiProperty} attribute.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class AttributePropertyMetadataFactory implements PropertyMetadataFactoryInterface
{
    public function __construct(private readonly ?PropertyMetadataFactoryInterface $decorated = null)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass, string $property, array $options = []): ApiProperty
    {
        $parentPropertyMetadata = null;
        if ($this->decorated) {
            try {
                $parentPropertyMetadata = $this->decorated->create($resourceClass, $property, $options);
            } catch (PropertyNotFoundException) {
                // Ignore not found exception from decorated factories
            }
        }

        $reflectionClass = null;
        $reflectionEnum = null;

        try {
            $reflectionClass = new \ReflectionClass($resourceClass);
        } catch (\ReflectionException) {
        }
        try {
            $reflectionEnum = new \ReflectionEnum($resourceClass);
        } catch (\ReflectionException) {
        }

        if (!$reflectionClass && !$reflectionEnum) {
            return $this->handleNotFound($parentPropertyMetadata, $resourceClass, $property);
        }

        if ($reflectionEnum) {
            if ($reflectionEnum->hasCase($property)) {
                $reflectionCase = $reflectionEnum->getCase($property);
                if ($attributes = $reflectionCase->getAttributes(ApiProperty::class)) {
                    return $this->createMetadata($attributes[0]->newInstance(), $parentPropertyMetadata);
                }
            }

            return $this->handleNotFound($parentPropertyMetadata, $resourceClass, $property);
        }

        if ($reflectionClass->hasProperty($property)) {
            $reflectionProperty = $reflectionClass->getProperty($property);
            if ($attributes = $reflectionProperty->getAttributes(ApiProperty::class)) {
                return $this->createMetadata($attributes[0]->newInstance(), $parentPropertyMetadata);
            }
        }

        foreach (array_merge(Reflection::ACCESSOR_PREFIXES, Reflection::MUTATOR_PREFIXES) as $prefix) {
            $methodName = $prefix.ucfirst($property);
            if (!$reflectionClass->hasMethod($methodName)) {
                continue;
            }

            $reflectionMethod = $reflectionClass->getMethod($methodName);
            if (!$reflectionMethod->isPublic()) {
                continue;
            }

            if ($attributes = $reflectionMethod->getAttributes(ApiProperty::class)) {
                return $this->createMetadata($attributes[0]->newInstance(), $parentPropertyMetadata);
            }
        }

        return $this->handleNotFound($parentPropertyMetadata, $resourceClass, $property);
    }

    /**
     * Returns the metadata from the decorated factory if available or throws an exception.
     *
     * @throws PropertyNotFoundException
     */
    private function handleNotFound(?ApiProperty $parentPropertyMetadata, string $resourceClass, string $property): ApiProperty
    {
        if (null !== $parentPropertyMetadata) {
            return $parentPropertyMetadata;
        }

        throw new PropertyNotFoundException(sprintf('Property "%s" of class "%s" not found.', $property, $resourceClass));
    }

    private function createMetadata(ApiProperty $attribute, ApiProperty $propertyMetadata = null): ApiProperty
    {
        if (null === $propertyMetadata) {
            return $this->handleUserDefinedSchema($attribute);
        }

        foreach (get_class_methods(ApiProperty::class) as $method) {
            if (preg_match('/^(?:get|is)(.*)/', (string) $method, $matches) && null !== $val = $attribute->{$method}()) {
                $propertyMetadata = $propertyMetadata->{"with{$matches[1]}"}($val);
            }
        }

        return $this->handleUserDefinedSchema($propertyMetadata);
    }

    private function handleUserDefinedSchema(ApiProperty $propertyMetadata): ApiProperty
    {
        // can't know later if the schema has been defined by the user or by API Platform
        // store extra key to make this difference
        if (null !== $propertyMetadata->getSchema()) {
            $extraProperties = $propertyMetadata->getExtraProperties() ?? [];
            $propertyMetadata = $propertyMetadata->withExtraProperties([SchemaPropertyMetadataFactory::JSON_SCHEMA_USER_DEFINED => true] + $extraProperties);
        }

        return $propertyMetadata;
    }
}
