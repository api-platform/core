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

namespace ApiPlatform\Serializer\Mapping\Loader;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Attribute\DiscriminatorMap;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Serializer\Attribute\MaxDepth;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Serializer\Attribute\SerializedPath;
use Symfony\Component\Serializer\Mapping\AttributeMetadata;
use Symfony\Component\Serializer\Mapping\AttributeMetadataInterface;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorMapping;
use Symfony\Component\Serializer\Mapping\ClassMetadataInterface;
use Symfony\Component\Serializer\Mapping\Loader\LoaderInterface;

/**
 * Loader for PHP attributes using ApiProperty.
 */
final class PropertyMetadataLoader implements LoaderInterface
{
    public function __construct(private readonly PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory)
    {
    }

    public function loadClassMetadata(ClassMetadataInterface $classMetadata): bool
    {
        $attributesMetadata = $classMetadata->getAttributesMetadata();
        // It's very weird to grab Eloquent's properties in that case as they're never serialized
        // the Serializer makes a call on the abstract class, let's save some unneeded work with a condition
        if (Model::class === $classMetadata->getName()) {
            return false;
        }

        $refl = $classMetadata->getReflectionClass();
        $attributes = [];
        $classGroups = [];
        $classContextAnnotation = null;

        foreach ($refl->getAttributes(ApiProperty::class) as $clAttr) {
            $this->addAttributeMetadata($clAttr->newInstance(), $attributes);
        }

        $attributesMetadata = $classMetadata->getAttributesMetadata();

        foreach ($refl->getAttributes() as $a) {
            $attribute = $a->newInstance();
            if ($attribute instanceof DiscriminatorMap) {
                $classMetadata->setClassDiscriminatorMapping(new ClassDiscriminatorMapping(
                    $attribute->getTypeProperty(),
                    $attribute->getMapping()
                ));
                continue;
            }

            if ($attribute instanceof Groups) {
                $classGroups = $attribute->getGroups();

                continue;
            }

            if ($attribute instanceof Context) {
                $classContextAnnotation = $attribute;
            }
        }

        foreach ($refl->getProperties() as $reflProperty) {
            foreach ($reflProperty->getAttributes(ApiProperty::class) as $propAttr) {
                $this->addAttributeMetadata($propAttr->newInstance()->withProperty($reflProperty->name), $attributes);
            }
        }

        foreach ($refl->getMethods() as $reflMethod) {
            foreach ($reflMethod->getAttributes(ApiProperty::class) as $methodAttr) {
                $this->addAttributeMetadata($methodAttr->newInstance()->withProperty($reflMethod->getName()), $attributes);
            }
        }

        foreach ($this->propertyNameCollectionFactory->create($classMetadata->getName()) as $propertyName) {
            if (!isset($attributesMetadata[$propertyName])) {
                $attributesMetadata[$propertyName] = new AttributeMetadata($propertyName);
                $classMetadata->addAttributeMetadata($attributesMetadata[$propertyName]);
            }

            foreach ($classGroups as $group) {
                $attributesMetadata[$propertyName]->addGroup($group);
            }

            if ($classContextAnnotation) {
                $this->setAttributeContextsForGroups($classContextAnnotation, $attributesMetadata[$propertyName]);
            }

            if (!isset($attributes[$propertyName])) {
                continue;
            }

            $attributeMetadata = $attributesMetadata[$propertyName];

            // This code is adapted from Symfony\Component\Serializer\Mapping\Loader\AttributeLoader
            foreach ($attributes[$propertyName] as $attr) {
                if ($attr instanceof Groups) {
                    foreach ($attr->getGroups() as $group) {
                        $attributeMetadata->addGroup($group);
                    }
                    continue;
                }

                match (true) {
                    $attr instanceof MaxDepth => $attributeMetadata->setMaxDepth($attr->getMaxDepth()),
                    $attr instanceof SerializedName => $attributeMetadata->setSerializedName($attr->getSerializedName()),
                    $attr instanceof SerializedPath => $attributeMetadata->setSerializedPath($attr->getSerializedPath()),
                    $attr instanceof Ignore => $attributeMetadata->setIgnore(true),
                    $attr instanceof Context => $this->setAttributeContextsForGroups($attr, $attributeMetadata),
                    default => null,
                };
            }
        }

        return true;
    }

    /**
     * @param ApiProperty[] $attributes
     */
    private function addAttributeMetadata(ApiProperty $attribute, array &$attributes): void
    {
        if (($prop = $attribute->getProperty()) && ($value = $attribute->getSerialize())) {
            $attributes[$prop] = $value;
        }
    }

    private function setAttributeContextsForGroups(Context $annotation, AttributeMetadataInterface $attributeMetadata): void
    {
        $context = $annotation->getContext();
        $groups = $annotation->getGroups();
        $normalizationContext = $annotation->getNormalizationContext();
        $denormalizationContext = $annotation->getDenormalizationContext();
        if ($normalizationContext || $context) {
            $attributeMetadata->setNormalizationContextForGroups($normalizationContext ?: $context, $groups);
        }

        if ($denormalizationContext || $context) {
            $attributeMetadata->setDenormalizationContextForGroups($denormalizationContext ?: $context, $groups);
        }
    }
}
