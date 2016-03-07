<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Metadata\Property\Factory;

use ApiPlatform\Core\Annotation\Property;
use ApiPlatform\Core\Exception\PropertyNotFoundException;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Util\Reflection;
use Doctrine\Common\Annotations\Reader;

/**
 * Creates a property metadata from {@see Property} annotations.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class AnnotationPropertyMetadataFactory implements PropertyMetadataFactoryInterface
{
    private $reader;
    private $decorated;

    public function __construct(Reader $reader, PropertyMetadataFactoryInterface $decorated = null)
    {
        $this->reader = $reader;
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass, string $property, array $options = []) : PropertyMetadata
    {
        $parentPropertyMetadata = null;
        if (isset($this->decorated)) {
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
            $annotation = $this->reader->getPropertyAnnotation($reflectionClass->getProperty($property), Property::class);

            if (null !== $annotation) {
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

            $annotation = $this->reader->getMethodAnnotation($reflectionMethod, Property::class);
            if (null !== $annotation) {
                return $this->createMetadata($annotation, $parentPropertyMetadata);
            }
        }

        return $this->handleNotFound($parentPropertyMetadata, $resourceClass, $property);
    }

    /**
     * Returns the metadata from the decorated factory if available or throws an exception.
     *
     * @param PropertyMetadata|null $parentPropertyMetadata
     * @param string                $resourceClass
     * @param string                $property
     *
     * @return PropertyMetadata
     *
     * @throws PropertyNotFoundException
     */
    private function handleNotFound(PropertyMetadata $parentPropertyMetadata = null, string $resourceClass, string $property) : PropertyMetadata
    {
        if (isset($parentPropertyMetadata)) {
            return $parentPropertyMetadata;
        }

        throw new PropertyNotFoundException(sprintf('Property "%s" of class "%s" not found.', $property, $resourceClass));
    }

    private function createMetadata(Property $annotation, PropertyMetadata $parentPropertyMetadata = null) : PropertyMetadata
    {
        if (!$parentPropertyMetadata) {
            return new PropertyMetadata(
                null,
                $annotation->description,
                $annotation->readable,
                $annotation->writable,
                $annotation->readableLink,
                $annotation->writableLink,
                $annotation->required,
                $annotation->identifier,
                $annotation->iri,
                $annotation->attributes
            );
        }

        $propertyMetadata = $parentPropertyMetadata;
        foreach (['description', 'readable', 'writable', 'readableLink', 'writableLink', 'required', 'iri', 'identifier', 'attributes'] as $property) {
            $this->createWith($propertyMetadata, $property, $annotation->$property);
        }

        return $propertyMetadata;
    }

    private function createWith(PropertyMetadata $propertyMetadata, string $property, $value) : PropertyMetadata
    {
        $getter = 'get'.ucfirst($property);

        if (null !== $propertyMetadata->$getter()) {
            return $propertyMetadata;
        }

        $wither = 'with'.ucfirst($property);

        return $propertyMetadata->$wither($value);
    }
}
