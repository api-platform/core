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
use ApiPlatform\Util\Reflection;

/**
 * Creates a property metadata from {@see ApiProperty} attribute.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class AttributePropertyMetadataFactory implements PropertyMetadataFactoryInterface
{
    private $decorated;

    public function __construct(PropertyMetadataFactoryInterface $decorated = null)
    {
        $this->decorated = $decorated;
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
            } catch (PropertyNotFoundException $propertyNotFoundException) {
                // Ignore not found exception from decorated factories
            }
        }

        try {
            $reflectionClass = new \ReflectionClass($resourceClass);
        } catch (\ReflectionException $reflectionException) {
            return $this->handleNotFound($parentPropertyMetadata, $resourceClass, $property);
        }

        if ($reflectionClass->hasProperty($property)) {
            $reflectionProperty = $reflectionClass->getProperty($property);
            if (\PHP_VERSION_ID >= 80000 && $attributes = $reflectionProperty->getAttributes(ApiProperty::class)) {
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

            if (\PHP_VERSION_ID >= 80000 && $attributes = $reflectionMethod->getAttributes(ApiProperty::class)) {
                return $this->createMetadata($attributes[0]->newInstance(), $parentPropertyMetadata);
            }
        }

        return $this->handleNotFound($parentPropertyMetadata, $resourceClass, $property);
    }

    /**
     * Returns the metadata from the decorated factory if available or throws an exception.
     *
     * @param ApiProperty|null $parentPropertyMetadata
     *
     * @throws PropertyNotFoundException
     */
    private function handleNotFound($parentPropertyMetadata, string $resourceClass, string $property): ApiProperty
    {
        if (null !== $parentPropertyMetadata) {
            return $parentPropertyMetadata;
        }

        throw new PropertyNotFoundException(sprintf('Property "%s" of class "%s" not found.', $property, $resourceClass));
    }

    private function createMetadata(ApiProperty $attribute, ApiProperty $propertyMetadata = null): ApiProperty
    {
        if (null === $propertyMetadata) {
            return $attribute;
        }

        foreach (get_class_methods(ApiProperty::class) as $method) {
            if (
                // TODO: remove these checks for deprecated methods in 3.0
                'getAttribute' !== $method &&
                'isChildInherited' !== $method &&
                'getSubresource' !== $method &&
                'getAttributes' !== $method &&
                // end of deprecated methods

                preg_match('/^(?:get|is)(.*)/', $method, $matches) &&
                null !== $val = $attribute->{$method}()
            ) {
                $propertyMetadata = $propertyMetadata->{"with{$matches[1]}"}($val);
            }
        }

        return $propertyMetadata;
    }
}
