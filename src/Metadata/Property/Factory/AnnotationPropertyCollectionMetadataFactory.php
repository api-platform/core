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
use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Core\Util\Reflection;
use Doctrine\Common\Annotations\Reader;

/**
 * Creates a property name collection from {@see Property} annotations.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class AnnotationPropertyCollectionMetadataFactory implements PropertyNameCollectionFactoryInterface
{
    private $reader;
    private $decorated;
    private $reflection;

    public function __construct(Reader $reader, PropertyNameCollectionFactoryInterface $decorated = null)
    {
        $this->reader = $reader;
        $this->decorated = $decorated;
        $this->reflection = new Reflection();
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass, array $options = []) : PropertyNameCollection
    {
        if ($this->decorated) {
            try {
                $propertyNameCollection = $this->decorated->create($resourceClass, $options);
            } catch (ResourceClassNotFoundException $resourceClassNotFoundException) {
                // Ignore not found exceptions from parent
            }
        }

        try {
            $reflectionClass = new \ReflectionClass($resourceClass);
        } catch (\ReflectionException $reflectionException) {
            if (isset($propertyNameCollection)) {
                return $propertyNameCollection;
            }

            throw new ResourceClassNotFoundException(sprintf('The resource class "%s" does not exist.', $resourceClass));
        }

        $propertyNames = [];

        // Properties
        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            if ($this->reader->getPropertyAnnotation($reflectionProperty, Property::class)) {
                $propertyNames[$reflectionProperty->name] = true;
            }
        }

        // Methods
        foreach ($reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $reflectionMethod) {
            $propertyName = $this->reflection->getProperty($reflectionMethod->name);

            if ($propertyName && $this->reader->getMethodAnnotation($reflectionMethod, Property::class)) {
                $propertyNames[$propertyName] = true;
            }
        }

        // Inherited from parent
        if (isset($propertyNameCollection)) {
            foreach ($propertyNameCollection as $propertyName) {
                $propertyNames[$propertyName] = $propertyName;
            }
        }

        return new PropertyNameCollection(array_keys($propertyNames));
    }
}
