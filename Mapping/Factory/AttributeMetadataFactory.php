<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Mapping\Factory;

use Dunglas\ApiBundle\Api\ResourceCollectionInterface;
use Dunglas\ApiBundle\Mapping\AttributeMetadata;
use Dunglas\ApiBundle\Mapping\ClassMetadataInterface;
use Dunglas\ApiBundle\Util\ReflectionTrait;
use PropertyInfo\PropertyInfoInterface;

/**
 * {@inheritdoc}
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class AttributeMetadataFactory implements AttributeMetadataFactoryInterface
{
    use ReflectionTrait;

    /**
     * @var PropertyInfoInterface
     */
    private $propertyInfo;
    /**
     * @var ResourceCollectionInterface
     */
    private $resourceCollection;

    public function __construct(PropertyInfoInterface $propertyInfo, ResourceCollectionInterface $resourceCollection)
    {
        $this->propertyInfo = $propertyInfo;
        $this->resourceCollection = $resourceCollection;
    }

    public function getAttributeMetadataFor(
        ClassMetadataInterface $classMetadata,
        $attributeName,
        array $normalizationGroups = null,
        array $denormalizationGroups = null
    ) {
        if ($classMetadata->hasAttributeMetadata($attributeName)) {
            return $classMetadata->getAttributeMetadata($attributeName);
        }

        $attributeMetadata = new AttributeMetadata();
        $reflectionProperty = $this->getReflectionProperty($classMetadata->getReflectionClass(), $attributeName);

        if (!$reflectionProperty) {
            return $attributeMetadata;
        }

        $types = $this->propertyInfo->getTypes($reflectionProperty);
        if (!isset($types[0])) {
            return $attributeMetadata;
        }

        $type = $types[0];
        $attributeMetadata = $attributeMetadata->withType($type);

        $class = $type->getClass();
        $link = $this->resourceCollection->getResourceForEntity($class) ||
            (
                $type->isCollection() &&
                $type->getCollectionType() &&
                ($class = $type->getCollectionType()->getClass()) &&
                $this->resourceCollection->getResourceForEntity($class)
            );

        $attributeMetadata = $attributeMetadata
            ->withLink($link)
            ->withLinkClass($class)
        ;

        if (!$link) {
            return $attributeMetadata;
        }

        if (null === $normalizationGroups) {
            $attributeMetadata = $attributeMetadata->withNormalizationLink(true);
        }

        if (null === $denormalizationGroups) {
            $attributeMetadata = $attributeMetadata->withDenormalizationLink(true);
        }

        return $attributeMetadata;
    }
}
