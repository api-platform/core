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

namespace ApiPlatform\Core\Bridge\Symfony\Metadata\ResourceMetadataCollection\Factory;

use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;

/**
 * @author Antoine Bluchet <soyuka@gmail.com>
 * @experimental
 */
final class SerializerResourceCollectionMetadataFactory implements ResourceMetadataCollectionFactoryInterface
{
    use ResourceClassInfoTrait;

    private $decorated;
    private $propertyNameCollectionFactory;
    private $propertyMetadataFactory;

    public function __construct(ResourceMetadataCollectionFactoryInterface $decorated, PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory)
    {
        $this->decorated = $decorated;
        $this->propertyNameCollectionFactory = $propertyNameCollectionFactory;
        $this->propertyMetadataFactory = $propertyMetadataFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass): ResourceMetadataCollection
    {
        $resourceMetadataCollection = new ResourceMetadataCollection();
        if ($this->decorated) {
            $resourceMetadataCollection = $this->decorated->create($resourceClass);
        }

        $identifiers = null;
        foreach ($resourceMetadataCollection as $i => $resource) {
            $resourceMetadataCollection[$i] = $resource;
        }

        return $resourceMetadataCollection;
    }

    /**
     * Sets readable/writable based on matching normalization/denormalization groups and property's ignorance.
     *
     * A false value is never reset as it could be unreadable/unwritable for other reasons.
     * If normalization/denormalization groups are not specified and the property is not ignored, the property is implicitly readable/writable.
     *
     * @param string[]|null $normalizationGroups
     * @param string[]|null $denormalizationGroups
     */
    private function transformReadWrite(PropertyMetadata $propertyMetadata, string $resourceClass, string $propertyName, array $normalizationGroups = null, array $denormalizationGroups = null): PropertyMetadata
    {
        $serializerAttributeMetadata = $this->getSerializerAttributeMetadata($resourceClass, $propertyName);
        $groups = $serializerAttributeMetadata ? $serializerAttributeMetadata->getGroups() : [];
        $ignored = $serializerAttributeMetadata && method_exists($serializerAttributeMetadata, 'isIgnored') ? $serializerAttributeMetadata->isIgnored() : false;

        if (false !== $propertyMetadata->isReadable()) {
            $propertyMetadata = $propertyMetadata->withReadable(!$ignored && (null === $normalizationGroups || array_intersect($normalizationGroups, $groups)));
        }

        if (false !== $propertyMetadata->isWritable()) {
            $propertyMetadata = $propertyMetadata->withWritable(!$ignored && (null === $denormalizationGroups || array_intersect($denormalizationGroups, $groups)));
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

        if (
            $type->isCollection() &&
            $collectionValueType = method_exists(Type::class, 'getCollectionValueTypes') ? ($type->getCollectionValueTypes()[0] ?? null) : $type->getCollectionValueType()
        ) {
            $relatedClass = $collectionValueType->getClassName();
        } else {
            $relatedClass = $type->getClassName();
        }

        // if property is not a resource relation, don't set link status (as it would have no meaning)
        if (null === $relatedClass || !$this->isResourceClass($relatedClass)) {
            return $propertyMetadata;
        }

        // find the resource class
        // this prevents serializer groups on non-resource child class from incorrectly influencing the decision
        if (null !== $this->resourceClassResolver) {
            $relatedClass = $this->resourceClassResolver->getResourceClass(null, $relatedClass);
        }

        $relatedGroups = $this->getClassSerializerGroups($relatedClass);

        if (null === $propertyMetadata->isReadableLink()) {
            $propertyMetadata = $propertyMetadata->withReadableLink(null !== $normalizationGroups && !empty(array_intersect($normalizationGroups, $relatedGroups)));
        }

        if (null === $propertyMetadata->isWritableLink()) {
            $propertyMetadata = $propertyMetadata->withWritableLink(null !== $denormalizationGroups && !empty(array_intersect($denormalizationGroups, $relatedGroups)));
        }

        return $propertyMetadata;
    }

    /**
     * Gets the effective serializer groups used in normalization/denormalization.
     *
     * Groups are extracted in the following order:
     *
     * - From the "serializer_groups" key of the $options array.
     * - From metadata of the given operation ("collection_operation_name" and "item_operation_name" keys).
     * - From metadata of the current resource.
     *
     * @throws ResourceClassNotFoundException
     *
     * @return (string[]|null)[]
     */
    private function getEffectiveSerializerGroups(array $options, string $resourceClass): array
    {
        if (isset($options['serializer_groups'])) {
            $groups = (array) $options['serializer_groups'];

            return [$groups, $groups];
        }

        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
        if (isset($options['collection_operation_name'])) {
            $normalizationContext = $resourceMetadata->getCollectionOperationAttribute($options['collection_operation_name'], 'normalization_context', null, true);
            $denormalizationContext = $resourceMetadata->getCollectionOperationAttribute($options['collection_operation_name'], 'denormalization_context', null, true);
        } elseif (isset($options['item_operation_name'])) {
            $normalizationContext = $resourceMetadata->getItemOperationAttribute($options['item_operation_name'], 'normalization_context', null, true);
            $denormalizationContext = $resourceMetadata->getItemOperationAttribute($options['item_operation_name'], 'denormalization_context', null, true);
        } elseif (isset($options['graphql_operation_name'])) {
            $normalizationContext = $resourceMetadata->getGraphqlAttribute($options['graphql_operation_name'], 'normalization_context', null, true);
            $denormalizationContext = $resourceMetadata->getGraphqlAttribute($options['graphql_operation_name'], 'denormalization_context', null, true);
        } else {
            $normalizationContext = $resourceMetadata->getAttribute('normalization_context');
            $denormalizationContext = $resourceMetadata->getAttribute('denormalization_context');
        }

        return [
            isset($normalizationContext['groups']) ? (array) $normalizationContext['groups'] : null,
            isset($denormalizationContext['groups']) ? (array) $denormalizationContext['groups'] : null,
        ];
    }

    private function getSerializerAttributeMetadata(string $class, string $attribute): ?AttributeMetadataInterface
    {
        $serializerClassMetadata = $this->serializerClassMetadataFactory->getMetadataFor($class);

        foreach ($serializerClassMetadata->getAttributesMetadata() as $serializerAttributeMetadata) {
            if ($attribute === $serializerAttributeMetadata->getName()) {
                return $serializerAttributeMetadata;
            }
        }

        return null;
    }

    /**
     * Gets all serializer groups used in a class.
     *
     * @return string[]
     */
    private function getClassSerializerGroups(string $class): array
    {
        $resourceMetadata = $this->resourceMetadataFactory->create($class);
        if ($outputClass = $resourceMetadata->getAttribute('output')['class'] ?? null) {
            $class = $outputClass;
        }

        $serializerClassMetadata = $this->serializerClassMetadataFactory->getMetadataFor($class);

        $groups = [];
        foreach ($serializerClassMetadata->getAttributesMetadata() as $serializerAttributeMetadata) {
            $groups = array_merge($groups, $serializerAttributeMetadata->getGroups());
        }

        return array_unique($groups);
    }
}
