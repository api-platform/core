<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Metadata\Property\Factory;

use Doctrine\Common\Annotations\Reader;
use Dunglas\ApiBundle\Annotation\Property;
use Dunglas\ApiBundle\Exception\ResourceClassNotFoundException;
use Dunglas\ApiBundle\Metadata\Property\CollectionMetadata;
use Dunglas\ApiBundle\Util\Reflection;

/**
 * Creates a property collection metadata from {@see Property} annotations.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class CollectionMetadataAnnotationFactory implements CollectionMetadataFactoryInterface
{
    /**
     * @var Reader
     */
    private $reader;

    /**
     * @var CollectionMetadataFactoryInterface|null
     */
    private $decorated;

    /**
     * @var Reflection
     */
    private $reflection;

    public function __construct(Reader $reader, CollectionMetadataFactoryInterface $decorated = null)
    {
        $this->reader = $reader;
        $this->decorated = $decorated;
        $this->reflection = new Reflection();
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass, array $options = []) : CollectionMetadata
    {
        if ($this->decorated) {
            try {
                $collectionMetadata = $this->decorated->create($resourceClass, $options);
            } catch (ResourceClassNotFoundException $resourceClassNotFoundException) {
                // Ignore not found exceptions from parent
            }
        }

        try {
            $reflectionClass = new \ReflectionClass($resourceClass);
        } catch (\ReflectionException $reflectionException) {
            if (isset($collectionMetadata)) {
                return $collectionMetadata;
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
        if (isset($collectionMetadata)) {
            foreach ($collectionMetadata as $propertyName) {
                $propertyNames[$propertyName] = $propertyName;
            }
        }

        return new CollectionMetadata(array_keys($propertyNames));
    }
}
