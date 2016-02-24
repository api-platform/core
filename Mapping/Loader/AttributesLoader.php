<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Mapping\Loader;

use Dunglas\ApiBundle\Api\ResourceCollectionInterface;
use Dunglas\ApiBundle\Mapping\AttributeMetadata;
use Dunglas\ApiBundle\Mapping\ClassMetadata;
use Dunglas\ApiBundle\Util\ReflectionTrait;
use PropertyInfo\PropertyInfoInterface;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;

/**
 * Uses serialization groups or alternatively reflection to populate attributes.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class AttributesLoader implements LoaderInterface
{
    use ReflectionTrait;

    /**
     * @var ResourceCollectionInterface
     */
    private $resourceCollection;
    /**
     * @var PropertyInfoInterface
     */
    private $propertyInfo;
    /**
     * @var ClassMetadataFactoryInterface|null
     */
    private $serializerClassMetadataFactory;

    public function __construct(
        ResourceCollectionInterface $resourceCollection,
        PropertyInfoInterface $propertyInfo,
        ClassMetadataFactoryInterface $serializerClassMetadataFactory = null
    ) {
        $this->resourceCollection = $resourceCollection;
        $this->propertyInfo = $propertyInfo;
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
        $reflectionClass = $classMetadata->getReflectionClass();

        // Methods
        foreach ($reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $reflectionMethod) {
            $numberOfRequiredParameters = $reflectionMethod->getNumberOfRequiredParameters();

            // Setters
            if (
                $numberOfRequiredParameters <= 1 &&
                preg_match('/^(set|add|remove)(.+)$/i', $reflectionMethod->name, $matches)
            ) {
                $attribute = $this->getOrCreateAttribute(
                    $classMetadata,
                    lcfirst($matches[2]),
                    $normalizationGroups,
                    $denormalizationGroups
                );
                $attribute->setWritable(true);

                continue;
            }

            if (0 !== $numberOfRequiredParameters) {
                continue;
            }

            // Getters and hassers
            if (
                (strpos($reflectionMethod->name, 'get') === 0 || strpos($reflectionMethod->name, 'has') === 0)
            ) {
                $attribute = $this->getOrCreateAttribute(
                    $classMetadata,
                    lcfirst(substr($reflectionMethod->name, 3)),
                    $normalizationGroups,
                    $denormalizationGroups
                );
                $attribute->setReadable(true);

                continue;
            }

            // Issers
            if (strpos($reflectionMethod->name, 'is') === 0) {
                $attribute = $this->getOrCreateAttribute(
                    $classMetadata,
                    lcfirst(substr($reflectionMethod->name, 2)),
                    $normalizationGroups,
                    $denormalizationGroups
                );

                $attribute->setReadable(true);
            }
        }

        // Properties
        foreach ($reflectionClass->getProperties(\ReflectionProperty::IS_PUBLIC) as $reflectionProperty) {
            $attribute = $this->getOrCreateAttribute(
                $classMetadata,
                $reflectionProperty->name,
                $normalizationGroups,
                $denormalizationGroups
            );

            $attribute->setReadable(true);
            $attribute->setWritable(true);
        }

        if (null === $normalizationGroups && null === $denormalizationGroups) {
            return true;
        }

        $serializerClassMetadata = $this->serializerClassMetadataFactory ? $this->serializerClassMetadataFactory->getMetadataFor($classMetadata->getName()) : null;

        if ($serializerClassMetadata) {
            $normalizationAttributes = [];
            foreach ($serializerClassMetadata->getAttributesMetadata() as $normalizationAttribute) {
                $normalizationAttributes[$normalizationAttribute->getName()] = $normalizationAttribute;
            }

            foreach ($classMetadata->getAttributes() as $attribute) {
                if ($attribute->isIdentifier()) {
                    continue;
                }
                $attributeGroups = isset($normalizationAttributes[$attribute->getName()]) ? $normalizationAttributes[$attribute->getName()]->getGroups() : [];
                $readable = true;
                $writable = true;

                if (null !== $normalizationGroups) {
                    $readable = count(array_intersect($attributeGroups, $normalizationGroups)) > 0;
                }
                if (null !== $denormalizationGroups) {
                    $writable = count(array_intersect($attributeGroups, $denormalizationGroups)) > 0;
                }

                if (!$readable && !$writable) {
                    $classMetadata->removeAttribute($attribute);
                } else {
                    $attribute->setReadable($readable);
                    $attribute->setWritable($writable);
                }
            }
        } else {
            throw new \Exception('Cannot apply normalization settings, serializer class metadata factory not found');
        }

        return true;
    }

    /**
     * Gets or creates the {@see AttributeMetadata} of the given name.
     *
     * @param ClassMetadata $classMetadata
     * @param string        $attributeName
     * @param array|null    $normalizationGroups
     *
     * @return AttributeMetadata
     */
    private function getOrCreateAttribute(
        ClassMetadata $classMetadata,
        $attributeName,
        array $normalizationGroups = null,
        array $denormalizationGroups = null
    ) {
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
            $attributeMetadata->setNormalizationLink(true);
        }

        if (null === $denormalizationGroups) {
            $attributeMetadata->setDenormalizationLink(true);
        }

        if ($attributeMetadata->isNormalizationLink() && $attributeMetadata->isDenormalizationLink()) {
            return $attributeMetadata;
        }

        if (!$this->serializerClassMetadataFactory ||
            !($relationSerializerMetadata = $this->serializerClassMetadataFactory->getMetadataFor($class))
        ) {
            $attributeMetadata->setNormalizationLink(true);
            $attributeMetadata->setDenormalizationLink(true);

            return $attributeMetadata;
        }

        foreach ($relationSerializerMetadata->getAttributesMetadata() as $serializerAttributeMetadata) {
            $serializerAttributeGroups = $serializerAttributeMetadata->getGroups();

            if (null !== $normalizationGroups && 1 <= count(array_intersect($normalizationGroups, $serializerAttributeGroups))) {
                $normalizationLink = false;
            }

            if (null !== $denormalizationGroups && 1 <= count(array_intersect($denormalizationGroups, $serializerAttributeGroups))) {
                $denormalizationLink = false;
            }

            if (isset($normalizationLink) && isset($denormalizationLink)) {
                return $attributeMetadata;
            }
        }

        if (!isset($normalizationLink)) {
            $attributeMetadata->setNormalizationLink(true);
        }

        if (!isset($denormalizationLink)) {
            $attributeMetadata->setDenormalizationLink(true);
        }

        return $attributeMetadata;
    }
}
