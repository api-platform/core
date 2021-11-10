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

namespace ApiPlatform\Core\Metadata\Property\Factory;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Exception\PropertyNotFoundException;
use ApiPlatform\Metadata\ApiProperty as ApiPropertyMetadata;
use ApiPlatform\Metadata\Property\DeprecationMetadataTrait;
use ApiPlatform\Util\Reflection;
use Doctrine\Common\Annotations\Reader;

/**
 * Creates a property metadata from {@see ApiProperty} annotations.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class AnnotationPropertyMetadataFactory implements PropertyMetadataFactoryInterface
{
    use DeprecationMetadataTrait;
    private $reader;
    private $decorated;

    public function __construct(Reader $reader = null, PropertyMetadataFactoryInterface $decorated = null)
    {
        $this->reader = $reader;
        $this->decorated = $decorated;

        trigger_deprecation('api-platform/core', '2.7', sprintf('The "%s" annotation is deprecated, use "%s" instead.', ApiProperty::class, ApiPropertyMetadata::class));
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass, string $property, array $options = [])
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
            $annotation = null;
            $reflectionProperty = $reflectionClass->getProperty($property);
            if (\PHP_VERSION_ID >= 80000 && $attributes = $reflectionProperty->getAttributes(ApiProperty::class)) {
                $annotation = $attributes[0]->newInstance();
            } elseif (null !== $this->reader) {
                $annotation = $this->reader->getPropertyAnnotation($reflectionProperty, ApiProperty::class);
            }

            if ($annotation instanceof ApiProperty) {
                return $this->createMetadata($annotation, $parentPropertyMetadata);
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

            $annotation = null;
            if (\PHP_VERSION_ID >= 80000 && $attributes = $reflectionMethod->getAttributes(ApiProperty::class)) {
                $annotation = $attributes[0]->newInstance();
            } elseif (null !== $this->reader) {
                $annotation = $this->reader->getMethodAnnotation($reflectionMethod, ApiProperty::class);
            }

            if ($annotation instanceof ApiProperty) {
                return $this->createMetadata($annotation, $parentPropertyMetadata);
            }
        }

        return $this->handleNotFound($parentPropertyMetadata, $resourceClass, $property);
    }

    /**
     * Returns the metadata from the decorated factory if available or throws an exception.
     *
     * @param ApiPropertyMetadata|PropertyMetadata|null $parentPropertyMetadata
     *
     * @throws PropertyNotFoundException
     *
     * @return ApiPropertyMetadata|PropertyMetadata
     */
    private function handleNotFound($parentPropertyMetadata, string $resourceClass, string $property)
    {
        if (null !== $parentPropertyMetadata) {
            return $parentPropertyMetadata;
        }

        throw new PropertyNotFoundException(sprintf('Property "%s" of class "%s" not found.', $property, $resourceClass));
    }

    /**
     * @param ApiPropertyMetadata|PropertyMetadata $parentPropertyMetadata
     *
     * @return ApiPropertyMetadata|PropertyMetadata
     */
    private function createMetadata(ApiProperty $annotation, $parentPropertyMetadata = null)
    {
        if (null === $parentPropertyMetadata) {
            $apiPropertyMetadata = new ApiPropertyMetadata();
            foreach (array_keys(get_class_vars(ApiProperty::class)) as $key) {
                if ('iri' === $key) {
                    continue;
                }
                $methodName = 'with'.ucfirst($key);

                if (method_exists($apiPropertyMetadata, $methodName) && null !== $annotation->{$key}) {
                    $apiPropertyMetadata = 'types' !== $key ? $apiPropertyMetadata->{$methodName}($annotation->{$key}) : $apiPropertyMetadata->{$methodName}([$annotation->iri]);
                }
            }

            return $this->withDeprecatedAttributes($apiPropertyMetadata, $annotation->attributes);
        }

        $propertyMetadata = $parentPropertyMetadata;
        foreach ([['get', 'description'], ['is', 'readable'], ['is', 'writable'], ['is', 'readableLink'], ['is', 'writableLink'], ['is', 'required'], ['is', 'identifier'], ['get', 'default'], ['get', 'example']] as $property) {
            if (null !== $value = $annotation->{$property[1]}) {
                $propertyMetadata = $this->createWith($propertyMetadata, $property, $value);
            }
        }

        if ($annotation->iri) {
            if ($propertyMetadata instanceof ApiPropertyMetadata) {
                trigger_deprecation('api-platform', '2.7', sprintf('Using "iri" on the "%s" annotation is deprecated, use "types" on the attribute "%s" instead.', ApiProperty::class, ApiPropertyMetadata::class));
                $propertyMetadata = $propertyMetadata->withTypes([$annotation->iri]);
            } else {
                $propertyMetadata = $propertyMetadata->withIri($annotation->iri);
            }
        }

        return $this->withDeprecatedAttributes($propertyMetadata, $annotation->attributes ?? []);
    }

    /**
     * @param PropertyMetadata|ApiPropertyMetadata $propertyMetadata
     * @param mixed                                $value
     */
    private function createWith($propertyMetadata, array $property, $value)
    {
        $wither = 'with'.ucfirst($property[1]);

        return $propertyMetadata->{$wither}($value);
    }
}
