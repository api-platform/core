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

namespace ApiPlatform\Metadata\Property\Factory;

use ApiPlatform\Api\ResourceClassResolverInterface;
use ApiPlatform\Exception\ResourceClassNotFoundException;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Util\ResourceClassInfoTrait;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\Mapping\AttributeMetadataInterface;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface as SerializerClassMetadataFactoryInterface;

/**
 * Populates read/write and link status using serialization groups.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Teoh Han Hui <teohhanhui@gmail.com>
 */
final class SerializerPropertyMetadataFactory implements PropertyMetadataFactoryInterface
{
    use ResourceClassInfoTrait;

    private $serializerClassMetadataFactory;
    private $decorated;

    public function __construct(SerializerClassMetadataFactoryInterface $serializerClassMetadataFactory, PropertyMetadataFactoryInterface $decorated, ResourceClassResolverInterface $resourceClassResolver = null)
    {
        $this->serializerClassMetadataFactory = $serializerClassMetadataFactory;
        $this->decorated = $decorated;
        $this->resourceClassResolver = $resourceClassResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass, string $property, array $options = []): ApiProperty
    {
        $propertyMetadata = $this->decorated->create($resourceClass, $property, $options);

        try {
            [$normalizationGroups, $denormalizationGroups] = $this->getEffectiveSerializerGroups($options, $resourceClass);

            if ($normalizationGroups && !\is_array($normalizationGroups)) {
                $normalizationGroups = [$normalizationGroups];
            }

            if ($denormalizationGroups && !\is_array($denormalizationGroups)) {
                $denormalizationGroups = [$denormalizationGroups];
            }
        } catch (ResourceClassNotFoundException $e) {
            // TODO: for input/output classes, the serializer groups must be read from the actual resource class
            return $propertyMetadata;
        }

        $propertyMetadata = $this->transformReadWrite($propertyMetadata, $resourceClass, $property, $normalizationGroups, $denormalizationGroups);

        return $this->transformLinkStatus($propertyMetadata, $normalizationGroups, $denormalizationGroups);
    }

    /**
     * Sets readable/writable based on matching normalization/denormalization groups and property's ignorance.
     *
     * A false value is never reset as it could be unreadable/unwritable for other reasons.
     * If normalization/denormalization groups are not specified and the property is not ignored, the property is implicitly readable/writable.
     *
     * @param string[]|null $normalizationGroups
     * @param string[]|null $denormalizationGroups
     *
     * @return ApiProperty $propertyMetadata
     */
    private function transformReadWrite(ApiProperty $propertyMetadata, string $resourceClass, string $propertyName, array $normalizationGroups = null, array $denormalizationGroups = null)
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
    private function transformLinkStatus(ApiProperty $propertyMetadata, array $normalizationGroups = null, array $denormalizationGroups = null)
    {
        // No need to check link status if property is not readable and not writable
        if (false === $propertyMetadata->isReadable() && false === $propertyMetadata->isWritable()) {
            return $propertyMetadata;
        }

        // TODO: 3.0 support multiple types, default value of types will be [] instead of null
        $type = $propertyMetadata->getBuiltinTypes()[0] ?? null;

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
     * @return (string[]|null|string)[]
     */
    private function getEffectiveSerializerGroups(array $options, string $resourceClass): array
    {
        if (isset($options['serializer_groups'])) {
            $groups = (array) $options['serializer_groups'];

            return [$groups, $groups];
        }

        if (\array_key_exists('normalization_groups', $options) && \array_key_exists('denormalization_groups', $options)) {
            return [$options['normalization_groups'] ?? null, $options['denormalization_groups'] ?? null];
        }

        return [null, null];
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
        $serializerClassMetadata = $this->serializerClassMetadataFactory->getMetadataFor($class);

        $groups = [];
        foreach ($serializerClassMetadata->getAttributesMetadata() as $serializerAttributeMetadata) {
            $groups = array_merge($groups, $serializerAttributeMetadata->getGroups());
        }

        return array_unique($groups);
    }
}
