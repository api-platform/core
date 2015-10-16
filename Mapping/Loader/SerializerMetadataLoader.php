<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Mapping\Loader;

use Dunglas\ApiBundle\Mapping\Factory\AttributeMetadataFactoryInterface;
use Dunglas\ApiBundle\Mapping\AttributeMetadataInterface;
use Dunglas\ApiBundle\Mapping\ClassMetadataInterface;
use Symfony\Component\Serializer\Mapping\AttributeMetadataInterface as SerializerAttributeMetadataInterface;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;

/**
 * Loads attributes and normalization links using serializer metadata.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class SerializerMetadataLoader implements LoaderInterface
{
    /**
     * @var AttributeMetadataFactoryInterface
     */
    private $attributeMetadataFactory;
    /**
     * @var ClassMetadataFactoryInterface
     */
    private $serializerClassMetadataFactory;

    public function __construct(AttributeMetadataFactoryInterface $attributeMetadataFactory, ClassMetadataFactoryInterface $serializerClassMetadataFactory)
    {
        $this->attributeMetadataFactory = $attributeMetadataFactory;
        $this->serializerClassMetadataFactory = $serializerClassMetadataFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function loadClassMetadata(
        ClassMetadataInterface $classMetadata,
        array $normalizationGroups = null,
        array $denormalizationGroups = null,
        array $validationGroups = null
    ) {
        if (null === $normalizationGroups && null === $denormalizationGroups) {
            return $classMetadata;
        }

        $serializerClassMetadata = $this->serializerClassMetadataFactory->getMetadataFor($classMetadata->getName());

        foreach ($serializerClassMetadata->getAttributesMetadata() as $serializerAttributeMetadata) {
            $attributeName = $serializerAttributeMetadata->getName();
            $attributeMetadata = $this->attributeMetadataFactory->getAttributeMetadataFor(
                $classMetadata, $attributeName, $normalizationGroups, $denormalizationGroups
            );

            list($classMetadata, $attributeMetadata) = $this->populateAttributeMetadata(
                $classMetadata, $attributeName, $attributeMetadata, $serializerAttributeMetadata, $normalizationGroups, $denormalizationGroups
            );

            $classMetadata = $this->populateNormalizationLinks(
                $classMetadata, $attributeName, $attributeMetadata, $normalizationGroups, $denormalizationGroups
            );
        }

        return $classMetadata;
    }

    /**
     * Populates attributes metadata of the given class metadata using serializer metadata.
     *
     * @param ClassMetadataInterface               $classMetadata
     * @param string                               $attributeName
     * @param AttributeMetadataInterface           $attributeMetadata
     * @param SerializerAttributeMetadataInterface $serializerAttributeMetadata
     * @param array|null                           $normalizationGroups
     * @param array|null                           $denormalizationGroups
     *
     * @return ClassMetadataInterface
     */
    private function populateAttributeMetadata(
        ClassMetadataInterface $classMetadata,
        $attributeName,
        AttributeMetadataInterface $attributeMetadata,
        SerializerAttributeMetadataInterface $serializerAttributeMetadata,
        array $normalizationGroups = null,
        array $denormalizationGroups = null
    ) {
        $groups = $serializerAttributeMetadata->getGroups();

        if ($this->hasGroups($groups, $normalizationGroups)) {
            $attributeMetadata = $attributeMetadata->withReadable(true);
            $classMetadata = $classMetadata->withAttributeMetadata($attributeName, $attributeMetadata);
        }

        if ($this->hasGroups($groups, $denormalizationGroups)) {
            $attributeMetadata = $attributeMetadata->withWritable(true);
            $classMetadata = $classMetadata->withAttributeMetadata($attributeName, $attributeMetadata);
        }

        return [$classMetadata, $attributeMetadata];
    }

    /**
     * Checks if an attribute has the passed groups.
     *
     * @param array      $groups
     * @param array|null $currentGroups
     *
     * @return bool
     */
    private function hasGroups(array $groups, array $currentGroups = null)
    {
        return null !== $currentGroups && 0 < count(array_intersect($groups, $currentGroups));
    }

    /**
     * Populates normalization and denormalization links.
     *
     * @param ClassMetadataInterface     $classMetadata
     * @param string                     $attributeName
     * @param AttributeMetadataInterface $attributeMetadata
     * @param array|null                 $normalizationGroups
     * @param array|null                 $denormalizationGroups
     *
     * @return ClassMetadataInterface
     */
    private function populateNormalizationLinks(
        ClassMetadataInterface $classMetadata,
        $attributeName,
        AttributeMetadataInterface $attributeMetadata,
        array $normalizationGroups = null,
        array $denormalizationGroups = null
    ) {
        if (
            !$classMetadata->hasAttributeMetadata($attributeName) ||
            !$attributeMetadata->isLink() ||
            ($attributeMetadata->isNormalizationLink() && $attributeMetadata->isDenormalizationLink())
        ) {
            return $classMetadata;
        }

        $relationSerializerMetadata = $this->serializerClassMetadataFactory->getMetadataFor($attributeMetadata->getLinkClass());
        if (!$relationSerializerMetadata) {
            $attributeMetadata = $attributeMetadata->withNormalizationLink(true)->withDenormalizationLink(true);

            return $classMetadata->withAttributeMetadata($attributeName, $attributeMetadata);
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
                $classMetadata = $classMetadata->withAttributeMetadata($attributeName, $attributeMetadata);
            }
        }

        if (!isset($normalizationLink)) {
            $attributeMetadata = $attributeMetadata->withNormalizationLink(true);
        }

        if (!isset($denormalizationLink)) {
            $attributeMetadata = $attributeMetadata->withDenormalizationLink(true);
        }

        return $classMetadata->withAttributeMetadata($attributeName, $attributeMetadata);
    }
}
