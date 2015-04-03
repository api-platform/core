<?php

/*
 * This file is part of the DunglasJsonLdApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\JsonLdApiBundle\Mapping\Loader;

use Dunglas\JsonLdApiBundle\JsonLd\ResourceCollectionInterface;
use Dunglas\JsonLdApiBundle\Mapping\AttributeMetadata;
use Dunglas\JsonLdApiBundle\Mapping\ClassMetadata;
use Dunglas\JsonLdApiBundle\Util\Reflection;
use PropertyInfo\PropertyInfoInterface;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;

/**
 * Uses serialization groups or alternatively reflection to populate attributes.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class AttributesLoader implements LoaderInterface
{
    use Reflection;

    /**
     * @var PropertyInfoInterface
     */
    private $propertyInfo;
    /**
     * @var ResourceCollectionInterface
     */
    private $resourceCollection;
    /**
     * @var ClassMetadataFactoryInterface|null
     */
    private $serializerClassMetadataFactory;

    public function __construct(
        PropertyInfoInterface $propertyInfo,
        ResourceCollectionInterface $resourceCollection,
        ClassMetadataFactoryInterface $serializerClassMetadataFactory = null
    ) {
        $this->propertyInfo = $propertyInfo;
        $this->resourceCollection = $resourceCollection;
        $this->serializerClassMetadataFactory = $serializerClassMetadataFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function loadClassMetadata(
        ClassMetadata $classMetadata,
        array $normalizationGroups = null,
        array $denormalizationGroups = null,
        array $validationGroups = null
    ) {
        $serializerClassMetadata = $this->serializerClassMetadataFactory ? $this->serializerClassMetadataFactory->getMetadataFor($classMetadata->getName()) : null;

        // Use Serializer metadata if applicable
        if ($serializerClassMetadata && (null !== $normalizationGroups || null !== $denormalizationGroups)) {
            foreach ($serializerClassMetadata->getAttributesMetadata() as $normalizationAttribute) {
                if ('id' === $name = $normalizationAttribute->getName()) {
                    continue;
                }

                if (null !== $normalizationGroups && count(array_intersect($normalizationAttribute->getGroups(), $normalizationGroups))) {
                    $attribute = $this->getOrCreateAttribute($classMetadata, $name, $normalizationGroups);
                    $attribute->setReadable(true);
                }

                if (null !== $denormalizationGroups && count(array_intersect($normalizationAttribute->getGroups(), $denormalizationGroups))) {
                    $attribute = $this->getOrCreateAttribute($classMetadata, $name, $normalizationGroups);
                    $attribute->setWritable(true);
                }
            }
        }

        // Fallback to reflection
        if (null === $normalizationGroups || null === $denormalizationGroups) {
            $reflectionClass = $classMetadata->getReflectionClass();

            // Methods
            foreach ($reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $reflectionMethod) {
                if ('getId' === $reflectionMethod->name || 'setId' === $reflectionMethod->name) {
                    continue;
                }

                $numberOfRequiredParameters = $reflectionMethod->getNumberOfRequiredParameters();

                // Setters
                if (
                    null === $denormalizationGroups &&
                    $numberOfRequiredParameters <= 1 &&
                    strpos($reflectionMethod->name, 'set') === 0
                ) {
                    $attribute = $this->getOrCreateAttribute($classMetadata, lcfirst(substr($reflectionMethod->name, 3)), $normalizationGroups);
                    $attribute->setWritable(true);

                    continue;
                }

                if (0 !== $numberOfRequiredParameters) {
                    continue;
                }

                // Getters and hassers
                if (
                    null === $normalizationGroups &&
                    (strpos($reflectionMethod->name, 'get') === 0 || strpos($reflectionMethod->name, 'has') === 0)
                ) {
                    $attribute = $this->getOrCreateAttribute($classMetadata, lcfirst(substr($reflectionMethod->name, 3)), $normalizationGroups);
                    $attribute->setReadable(true);

                    continue;
                }

                // Issers
                if (null === $normalizationGroups && strpos($reflectionMethod->name, 'is') === 0) {
                    $attribute = $this->getOrCreateAttribute($classMetadata, lcfirst(substr($reflectionMethod->name, 2)), $normalizationGroups);
                    $attribute->setReadable(true);
                }
            }

            // Properties
            foreach ($reflectionClass->getProperties(\ReflectionProperty::IS_PUBLIC) as $reflectionProperty) {
                if ('id' === $reflectionProperty->name) {
                    continue;
                }

                $attribute = $this->getOrCreateAttribute($classMetadata, $reflectionProperty->name, $normalizationGroups);
                if (null === $normalizationGroups) {
                    $attribute->setReadable(true);
                }

                if (null === $denormalizationGroups) {
                    $attribute->setWritable(true);
                }
            }
        }

        return true;
    }

    /**
     * Gets or creates the {@see AttributeMetadata} of the given name.
     *
     * @param ClassMetadata $classMetadata
     * @param string        $attributeName
     * @param string[]|null $normalizationGroups
     *
     * @return AttributeMetadata
     */
    private function getOrCreateAttribute(ClassMetadata $classMetadata, $attributeName, array $normalizationGroups = null)
    {
        if (isset($classMetadata->getAttributes()[$attributeName])) {
            return $classMetadata->getAttributes()[$attributeName];
        }

        $attributeMetadata = new AttributeMetadata($attributeName);
        $classMetadata->addAttribute($attributeMetadata);

        $reflectionProperty = $this->getReflectionProperty($classMetadata->getReflectionClass(), $attributeName);

        if (!$reflectionProperty) {
            return $attributeMetadata;
        }

        $types = $this->propertyInfo->getTypes($reflectionProperty);
        if (null !== $types) {
            $attributeMetadata->setTypes($types);
        }

        if (!isset($types[0])) {
            return $attributeMetadata;
        }

        $class = $types[0]->getClass();

        if (!$this->resourceCollection->getResourceForEntity($class) && !(
            $types[0]->isCollection() &&
            $types[0]->getCollectionType() &&
            ($class = $types[0]->getCollectionType()->getClass()) &&
            $this->resourceCollection->getResourceForEntity($class)
        )) {
            return $attributeMetadata;
        }

        if (null === $normalizationGroups) {
            $attributeMetadata->setLink(true);

            return $attributeMetadata;
        }

        if (!$this->serializerClassMetadataFactory ||
            !($relationSerializerMetadata = $this->serializerClassMetadataFactory->getMetadataFor($class))
        ) {
            return $attributeMetadata;
        }

        foreach ($relationSerializerMetadata->getAttributesMetadata() as $serializerAttributeMetadata) {
            if (1 <= count(array_intersect($normalizationGroups, $serializerAttributeMetadata->getGroups()))) {
                return $attributeMetadata;
            }
        }

        $attributeMetadata->setLink(true);

        return $attributeMetadata;
    }
}
