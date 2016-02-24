<?php

/*
 * This file is part of the API Platform Builder package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Metadata\Property\Factory;

use ApiPlatform\Core\Annotation\Property;
use ApiPlatform\Core\Exception\PropertyNotFoundException;
use ApiPlatform\Core\Metadata\Property\ItemMetadata;
use ApiPlatform\Core\Util\Reflection;
use Doctrine\Common\Annotations\Reader;

/**
 * Creates a property metadata from {@see Property} annotations.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ItemMetadataAnnotationFactory implements ItemMetadataFactoryInterface
{
    private $reader;
    private $decorated;

    public function __construct(Reader $reader, ItemMetadataFactoryInterface $decorated = null)
    {
        $this->reader = $reader;
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass, string $property, array $options = []) : ItemMetadata
    {
        $parentItemMetadata = null;
        if (isset($this->decorated)) {
            try {
                $parentItemMetadata = $this->decorated->create($resourceClass, $property, $options);
            } catch (PropertyNotFoundException $propertyNotFoundException) {
                // Ignore not found exception from decorated factories
            }
        }

        try {
            $reflectionClass = new \ReflectionClass($resourceClass);
        } catch (\ReflectionException $reflectionException) {
            return $this->handleNotFound($parentItemMetadata, $resourceClass, $property);
        }

        if ($reflectionClass->hasProperty($property)) {
            $annotation = $this->reader->getPropertyAnnotation($reflectionClass->getProperty($property), Property::class);

            if (null !== $annotation) {
                return $this->createMetadata($annotation, $parentItemMetadata);
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
                return $this->createMetadata($annotation, $parentItemMetadata);
            }
        }

        return $this->handleNotFound($parentItemMetadata, $resourceClass, $property);
    }

    /**
     * Returns the metadata from the decorated factory if available or throws an exception.
     *
     * @param ItemMetadata|null $parentItemMetadata
     * @param string            $resourceClass
     * @param string            $property
     *
     * @return ItemMetadata
     *
     * @throws PropertyNotFoundException
     */
    private function handleNotFound(ItemMetadata $parentItemMetadata = null, string $resourceClass, string $property) : ItemMetadata
    {
        if (isset($parentItemMetadata)) {
            return $parentItemMetadata;
        }

        throw new PropertyNotFoundException(sprintf('Property "%s" of class "%s" not found.', $property, $resourceClass));
    }

    private function createMetadata(Property $annotation, ItemMetadata $parentItemMetadata = null) : ItemMetadata
    {
        if (!$parentItemMetadata) {
            return new ItemMetadata(
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

        $itemMetadata = $parentItemMetadata;
        foreach (['description', 'readable', 'writable', 'readableLink', 'writableLink', 'required', 'iri', 'identifier', 'attributes'] as $property) {
            $this->createWith($itemMetadata, $property, $annotation->$property);
        }

        return $itemMetadata;
    }

    private function createWith(ItemMetadata $itemMetadata, string $property, $value) : ItemMetadata
    {
        $ucfirstedProperty = ucfirst($property);
        $getter = 'get'.$ucfirstedProperty;

        if (null !== $itemMetadata->$getter()) {
            return $itemMetadata;
        }

        $wither = 'with'.$ucfirstedProperty;

        return $itemMetadata->$wither($value);
    }
}
