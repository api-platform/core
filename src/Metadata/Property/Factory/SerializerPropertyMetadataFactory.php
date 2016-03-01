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

use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface as SerializerClassMetadataFactoryInterface;

/**
 * Populates links status using serialization groups.
 *
 * Extracts groups in the following order:
 *   * From the "groups" key of the $options array
 *   * From metadata of the given operation ("collection_operation_name" and "item_operation_name" keys)
 *   * From metadata of the current resource
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class SerializerPropertyMetadataFactory implements PropertyMetadataFactoryInterface
{
    private $resourceMetadataFactory;
    private $serializerClassMetadataFactory;
    private $decorated;

    public function __construct(ResourceMetadataFactoryInterface $resourceMetadataFactory, SerializerClassMetadataFactoryInterface $serializerClassMetadataFactory, PropertyMetadataFactoryInterface $decorated)
    {
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->serializerClassMetadataFactory = $serializerClassMetadataFactory;
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass, string $property, array $options = []) : PropertyMetadata
    {
        $propertyMetadata = $this->decorated->create($resourceClass, $property, $options);

        $propertyMetadata = $propertyMetadata->withReadableLink(true);
        $propertyMetadata = $propertyMetadata->withWritableLink(true);

        $type = $propertyMetadata->getType();
        if (null === $type) {
            return $propertyMetadata;
        }

        $relatedClass = $type->isCollection() && ($collectionValueType = $type->getCollectionValueType()) ? $collectionValueType->getClassName() : $type->getClassName();

        if (null === $relatedClass) {
            return $propertyMetadata;
        }

        try {
            $this->resourceMetadataFactory->create($relatedClass);
        } catch (ResourceClassNotFoundException $e) {
            return $propertyMetadata;
        }

        $serializerClassMetadata = $this->serializerClassMetadataFactory->getMetadataFor($resourceClass);
        $groups = [];
        foreach ($serializerClassMetadata->getAttributesMetadata() as $serializerAttributeMetadata) {
            if ($property === $serializerAttributeMetadata->getName()) {
                $groups = $serializerAttributeMetadata->getGroups();
                break;
            }
        }

        list($normalizationGroups, $denormalizationGroups) = $this->getGroups($options, $resourceClass);
        $propertyMetadata = $propertyMetadata->withReadableLink(!empty(array_intersect($normalizationGroups, $groups)));
        $propertyMetadata = $propertyMetadata->withWritableLink(!empty(array_intersect($denormalizationGroups, $groups)));

        return $propertyMetadata;
    }

    private function getGroups(array $options, string $resourceClass) : array
    {
        if (isset($options['serializer_groups'])) {
            return [$options['serializer_groups'], $options['serializer_groups']];
        }

        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
        if (isset($options['collection_operation_name'])) {
            $normalizationContext = $resourceMetadata->getCollectionOperationAttribute($options['collection_operation_name'], 'normalization_context', null, true);
            $denormalizationContext = $resourceMetadata->getCollectionOperationAttribute($options['collection_operation_name'], 'denormalization_context', null, true);
        } elseif (isset($options['item_operation_name'])) {
            $normalizationContext = $resourceMetadata->getItemOperationAttribute($options['item_operation_name'], 'normalization_context', null, true);
            $denormalizationContext = $resourceMetadata->getItemOperationAttribute($options['item_operation_name'], 'denormalization_context', null, true);
        } else {
            $normalizationContext = $resourceMetadata->getAttribute('normalization_context');
            $denormalizationContext = $resourceMetadata->getAttribute('denormalization_context');
        }

        return [$normalizationContext['groups'] ?? [], $denormalizationContext['groups'] ?? []];
    }
}
