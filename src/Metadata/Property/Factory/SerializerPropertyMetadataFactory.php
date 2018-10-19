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

use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Serializer\SerializerContextBuilderInterface;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface as SerializerClassMetadataFactoryInterface;

/**
 * Populates read/write and link status using serialization groups.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Teoh Han Hui <teohhanhui@gmail.com>
 */
final class SerializerPropertyMetadataFactory implements PropertyMetadataFactoryInterface
{
    private $resourceMetadataFactory;
    private $serializerClassMetadataFactory;
    private $decorated;
    private $contextBuilder;

    public function __construct(ResourceMetadataFactoryInterface $resourceMetadataFactory, SerializerClassMetadataFactoryInterface $serializerClassMetadataFactory, PropertyMetadataFactoryInterface $decorated, SerializerContextBuilderInterface $contextBuilder)
    {
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->serializerClassMetadataFactory = $serializerClassMetadataFactory;
        $this->decorated = $decorated;
        $this->contextBuilder = $contextBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass, string $property, array $options = []): PropertyMetadata
    {
        $propertyMetadata = $this->decorated->create($resourceClass, $property, $options);

        // in case of a property inherited (in a child class), we need it's properties
        // to be mapped against serialization groups instead of the parent ones.
        if (null !== ($childResourceClass = $propertyMetadata->isChildInherited())) {
            $resourceClass = $childResourceClass;
        }

        list($normalizationGroups, $denormalizationGroups) = $this->contextBuilder->createFromResourceClass($options, $resourceClass);

        $propertyMetadata = $this->transformReadWrite($propertyMetadata, $resourceClass, $property, $normalizationGroups, $denormalizationGroups);
        $propertyMetadata = $this->transformLinkStatus($propertyMetadata, $normalizationGroups, $denormalizationGroups);

        return $propertyMetadata;
    }

    /**
     * Sets readable/writable based on matching normalization/denormalization groups.
     *
     * A false value is never reset as it could be unreadable/unwritable for other reasons.
     * If normalization/denormalization groups are not specified, the property is implicitly readable/writable.
     *
     * @param string[]|null $normalizationGroups
     * @param string[]|null $denormalizationGroups
     */
    private function transformReadWrite(PropertyMetadata $propertyMetadata, string $resourceClass, string $propertyName, array $normalizationGroups = null, array $denormalizationGroups = null): PropertyMetadata
    {
        $groups = $this->getPropertySerializerGroups($resourceClass, $propertyName);

        if (false !== $propertyMetadata->isReadable()) {
            $propertyMetadata = $propertyMetadata->withReadable(null === $normalizationGroups || !empty(array_intersect($normalizationGroups, $groups)));
        }

        if (false !== $propertyMetadata->isWritable()) {
            $propertyMetadata = $propertyMetadata->withWritable(null === $denormalizationGroups || !empty(array_intersect($denormalizationGroups, $groups)));
        }

        return $propertyMetadata;
    }

    /**
     * Sets readableLink/writableLink based on matching normalization/denormalization groups.
     *
     * If normalization/denormalization groups are not specified,
     * set link status to false since embedding of resource must be explicitly enabled
     *
     * @param string[]|null $normalizationGroups
     * @param string[]|null $denormalizationGroups
     */
    private function transformLinkStatus(PropertyMetadata $propertyMetadata, array $normalizationGroups = null, array $denormalizationGroups = null): PropertyMetadata
    {
        // No need to check link status if property is not readable and not writable
        if (false === $propertyMetadata->isReadable() && false === $propertyMetadata->isWritable()) {
            return $propertyMetadata;
        }

        $type = $propertyMetadata->getType();
        if (null === $type) {
            return $propertyMetadata;
        }

        $relatedClass = $type->isCollection() && ($collectionValueType = $type->getCollectionValueType()) ? $collectionValueType->getClassName() : $type->getClassName();

        if (null === $relatedClass) {
            return $propertyMetadata->withReadableLink(true)->withWritableLink(true);
        }

        // No need to check link status if related class is not a resource
        try {
            $this->resourceMetadataFactory->create($relatedClass);
        } catch (ResourceClassNotFoundException $e) {
            return $propertyMetadata;
        }

        $relatedGroups = $this->getResourceSerializerGroups($relatedClass);

        if (null === $propertyMetadata->isReadableLink()) {
            $propertyMetadata = $propertyMetadata->withReadableLink(null !== $normalizationGroups && !empty(array_intersect($normalizationGroups, $relatedGroups)));
        }

        if (null === $propertyMetadata->isWritableLink()) {
            $propertyMetadata = $propertyMetadata->withWritableLink(null !== $denormalizationGroups && !empty(array_intersect($denormalizationGroups, $relatedGroups)));
        }

        return $propertyMetadata;
    }

    /**
     * Gets the serializer groups defined on a property.
     *
     *
     * @return string[]
     */
    private function getPropertySerializerGroups(string $resourceClass, string $property): array
    {
        $serializerClassMetadata = $this->serializerClassMetadataFactory->getMetadataFor($resourceClass);

        foreach ($serializerClassMetadata->getAttributesMetadata() as $serializerAttributeMetadata) {
            if ($property === $serializerAttributeMetadata->getName()) {
                return $serializerAttributeMetadata->getGroups();
            }
        }

        return [];
    }

    /**
     * Gets the serializer groups defined in a resource.
     *
     *
     * @return string[]
     */
    private function getResourceSerializerGroups(string $resourceClass): array
    {
        $serializerClassMetadata = $this->serializerClassMetadataFactory->getMetadataFor($resourceClass);

        $groups = [];

        foreach ($serializerClassMetadata->getAttributesMetadata() as $serializerAttributeMetadata) {
            $groups = array_merge($groups, $serializerAttributeMetadata->getGroups());
        }

        return array_unique($groups);
    }
}
