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

use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\Metadata\Property\ItemMetadata;
use ApiPlatform\Core\Metadata\Resource\Factory\ItemMetadataFactoryInterface as ResourceItemMetadataFactoryInterface;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;

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
final class ItemMetadataSerializerFactory implements ItemMetadataFactoryInterface
{
    private $resourceItemMetadataFactory;
    private $classMetadataFactory;
    private $decorated;

    public function __construct(ResourceItemMetadataFactoryInterface $resourceItemMetadataFactory, ClassMetadataFactoryInterface $classMetadataFactory, ItemMetadataFactoryInterface $decorated)
    {
        $this->resourceItemMetadataFactory = $resourceItemMetadataFactory;
        $this->classMetadataFactory = $classMetadataFactory;
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass, string $property, array $options = []) : ItemMetadata
    {
        $propertyItemMetadata = $this->decorated->create($resourceClass, $property, $options);

        $propertyItemMetadata = $propertyItemMetadata->withReadableLink(true);
        $propertyItemMetadata = $propertyItemMetadata->withWritableLink(true);

        $type = $propertyItemMetadata->getType();
        if (null === $type) {
            return $propertyItemMetadata;
        }

        $relatedClass = $type->isCollection() && ($collectionValueType = $type->getCollectionValueType()) ? $collectionValueType->getClassName() : $type->getClassName();

        if (null === $relatedClass) {
            return $propertyItemMetadata;
        }

        try {
            $this->resourceItemMetadataFactory->create($relatedClass);
        } catch (ResourceClassNotFoundException $e) {
            return $propertyItemMetadata;
        }

        $serializerClassMetadata = $this->classMetadataFactory->getMetadataFor($resourceClass);
        $groups = [];
        foreach ($serializerClassMetadata->getAttributesMetadata() as $serializerAttributeMetadata) {
            if ($property === $serializerAttributeMetadata->getName()) {
                $groups = $serializerAttributeMetadata->getGroups();
                break;
            }
        }

        list($normalizationGroups, $denormalizationGroups) = $this->getGroups($options, $resourceClass);
        $propertyItemMetadata = $propertyItemMetadata->withReadableLink(!empty(array_intersect($normalizationGroups, $groups)));
        $propertyItemMetadata = $propertyItemMetadata->withWritableLink(!empty(array_intersect($denormalizationGroups, $groups)));

        return $propertyItemMetadata;
    }

    private function getGroups(array $options, string $resourceClass) : array
    {
        if (isset($options['serializer_groups'])) {
            return [$options['serializer_groups'], $options['serializer_groups']];
        }

        $resourceItemMetadata = $this->resourceItemMetadataFactory->create($resourceClass);
        if (isset($options['collection_operation_name'])) {
            $normalizationContext = $resourceItemMetadata->getCollectionOperationAttribute($options['collection_operation_name'], 'normalization_context', null, true);
            $denormalizationContext = $resourceItemMetadata->getCollectionOperationAttribute($options['collection_operation_name'], 'denormalization_context', null, true);
        } elseif (isset($options['item_operation_name'])) {
            $normalizationContext = $resourceItemMetadata->getItemOperationAttribute($options['item_operation_name'], 'normalization_context', null, true);
            $denormalizationContext = $resourceItemMetadata->getItemOperationAttribute($options['item_operation_name'], 'denormalization_context', null, true);
        } else {
            $normalizationContext = $resourceItemMetadata->getAttribute('normalization_context');
            $denormalizationContext = $resourceItemMetadata->getAttribute('denormalization_context');
        }

        return [$normalizationContext['groups'] ?? [], $denormalizationContext['groups'] ?? []];
    }
}
