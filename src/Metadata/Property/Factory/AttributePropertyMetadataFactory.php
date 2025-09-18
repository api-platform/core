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
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

/**
 * Creates a property metadata from {@see ApiProperty} attribute.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class AttributePropertyMetadataFactory implements PropertyMetadataFactoryInterface
{
    public function __construct(
        private readonly ?PropertyMetadataFactoryInterface $decorated = null,
        private readonly ?NameConverterInterface $nameConverter = null,
    ) {
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

        if ($reflectionEnum && $reflectionEnum->hasCase($property)) {
            $reflectionCase = $reflectionEnum->getCase($property);
            if ($attributes = $reflectionCase->getAttributes(ApiProperty::class)) {
                return $this->createMetadata($attributes[0]->newInstance(), $parentPropertyMetadata);
            }
        }

        if ($reflectionClass->hasProperty($property)) {
            $reflectionProperty = $reflectionClass->getProperty($property);
            if ($attributes = $reflectionProperty->getAttributes(ApiProperty::class)) {
                return $this->createMetadata($attributes[0]->newInstance(), $parentPropertyMetadata);
            }
        }

        foreach (array_merge(Reflection::ACCESSOR_PREFIXES, Reflection::MUTATOR_PREFIXES) as $prefix) {
            $methodName = $prefix.ucfirst($property);
            if (!$reflectionClass->hasMethod($methodName) && !$reflectionEnum?->hasMethod($methodName)) {
                continue;
            }

            $reflectionMethod = $reflectionClass->hasMethod($methodName) ? $reflectionClass->getMethod($methodName) : $reflectionEnum?->getMethod($methodName);
            if (!$reflectionMethod->isPublic()) {
                continue;
            }

            if ($attributes = $reflectionMethod->getAttributes(ApiProperty::class)) {
                return $this->createMetadata($attributes[0]->newInstance(), $parentPropertyMetadata);
            }
        }

        $attributes = $reflectionClass->getAttributes(ApiProperty::class);
        foreach ($attributes as $attribute) {
            $instance = $attribute->newInstance();
            if ($instance->getProperty() === ($this->nameConverter?->denormalize($property) ?? $property)) {
                return $this->createMetadata($instance, $parentPropertyMetadata);
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

        throw new PropertyNotFoundException(\sprintf('Property "%s" of class "%s" not found.', $property, $resourceClass));
    }

    private function createMetadata(ApiProperty $attribute, ?ApiProperty $propertyMetadata = null): ApiProperty
    {
        if (null === $propertyMetadata) {
            return $this->handleUserDefinedSchema($attribute);
        }

        foreach (get_class_methods(ApiProperty::class) as $method) {
            if (preg_match('/^(?:get|is)(.*)/', (string) $method, $matches)) {
                // BC layer, to remove in 5.0
                if ('getBuiltinTypes' === $method) {
                    if (method_exists(PropertyInfoExtractor::class, 'getType')) {
                        continue;
                    }

                    if ($builtinTypes = $attribute->getBuiltinTypes()) {
                        $propertyMetadata = $propertyMetadata->withBuiltinTypes($builtinTypes);
                    }

                    continue;
                }

                if (null !== $val = $attribute->{$method}()) {
                    $propertyMetadata = $propertyMetadata->{"with{$matches[1]}"}($val);
                }
            }
        }

        return $this->handleUserDefinedSchema($propertyMetadata);
    }

    private function handleUserDefinedSchema(ApiProperty $propertyMetadata): ApiProperty
    {
        // can't know later if the schema has been defined by the user or by API Platform
        // store extra key to make this difference
        if (null !== $propertyMetadata->getSchema()) {
            $extraProperties = $propertyMetadata->getExtraProperties();
            $propertyMetadata = $propertyMetadata->withExtraProperties([SchemaPropertyMetadataFactory::JSON_SCHEMA_USER_DEFINED => true] + $extraProperties);
        }

        return $propertyMetadata;
    }
}
