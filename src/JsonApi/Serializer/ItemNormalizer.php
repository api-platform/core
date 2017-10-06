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

namespace ApiPlatform\Core\JsonApi\Serializer;

use ApiPlatform\Core\Api\IdentifiersExtractorInterface;
use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Serializer\AbstractItemNormalizer;
use ApiPlatform\Core\Serializer\ContextTrait;
use ApiPlatform\Core\Util\ClassInfoTrait;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

/**
 * Converts between objects and array.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class ItemNormalizer extends AbstractItemNormalizer
{
    use ClassInfoTrait;
    use ContextTrait;

    const FORMAT = 'jsonapi';

    private $componentsCache = [];
    private $resourceMetadataFactory;
    private $itemDataProvider;
    private $identifiersExtractor;

    public function __construct(PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, IriConverterInterface $iriConverter, ResourceClassResolverInterface $resourceClassResolver, PropertyAccessorInterface $propertyAccessor = null, NameConverterInterface $nameConverter = null, ResourceMetadataFactoryInterface $resourceMetadataFactory, ItemDataProviderInterface $itemDataProvider, IdentifiersExtractorInterface $identifiersExtractor)
    {
        parent::__construct($propertyNameCollectionFactory, $propertyMetadataFactory, $iriConverter, $resourceClassResolver, $propertyAccessor, $nameConverter);

        $this->identifiersExtractor = $identifiersExtractor;
        $this->itemDataProvider = $itemDataProvider;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return self::FORMAT === $format && parent::supportsNormalization($data, $format);
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        $context['cache_key'] = $this->getJsonApiCacheKey($format, $context);

        // Get and populate attributes data
        $objectAttributesData = parent::normalize($object, $format, $context);

        if (!is_array($objectAttributesData)) {
            return $objectAttributesData;
        }

        // Get and populate identifier if existent
        $identifier = $this->getIdentifierFromItem($object);

        // Get and populate item type
        $resourceClass = $this->resourceClassResolver->getResourceClass($object, $context['resource_class'] ?? null, true);
        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);

        // Get and populate relations
        $components = $this->getComponents($object, $format, $context);
        $objectRelationshipsData = $this->getPopulatedRelations($object, $format, $context, $components['relationships']);

        $item = [
            'id' => $identifier,
            'type' => $resourceMetadata->getShortName(),
        ];

        if ($objectAttributesData) {
            $item['attributes'] = $objectAttributesData;
        }

        if ($objectRelationshipsData) {
            $item['relationships'] = $objectRelationshipsData;
        }

        return ['data' => $item];
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return self::FORMAT === $format && parent::supportsDenormalization($data, $type, $format);
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        // Avoid issues with proxies if we populated the object
        if (isset($data['data']['id']) && !isset($context['object_to_populate'])) {
            if (isset($context['api_allow_update']) && true !== $context['api_allow_update']) {
                throw new InvalidArgumentException('Update is not allowed for this operation.');
            }

            $context['object_to_populate'] = $this->iriConverter->getItemFromIri(
                $data['data']['id'],
                $context + ['fetch_data' => false]
            );
        }

        // Merge attributes and relations previous to apply parents denormalizing
        $dataToDenormalize = array_merge(
            $data['data']['attributes'] ?? [],
            $data['data']['relationships'] ?? []
        );

        return parent::denormalize(
            $dataToDenormalize,
            $class,
            $format,
            $context
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getAttributes($object, $format, array $context)
    {
        return $this->getComponents($object, $format, $context)['attributes'];
    }

    /**
     * {@inheritdoc}
     */
    protected function setAttributeValue($object, $attribute, $value, $format = null, array $context = [])
    {
        $propertyMetadata = $this->propertyMetadataFactory->create(
            $context['resource_class'],
            $attribute,
            $this->getFactoryOptions($context)
        );
        $type = $propertyMetadata->getType();

        if (null === $type) {
            // No type provided, blindly set the value
            $this->setValue($object, $attribute, $value);

            return;
        }

        if (null === $value && $type->isNullable()) {
            $this->setValue($object, $attribute, $value);

            return;
        }

        if (
            $type->isCollection() &&
            null !== ($collectionValueType = $type->getCollectionValueType()) &&
            null !== $className = $collectionValueType->getClassName()
        ) {
            $this->setValue(
                $object,
                $attribute,
                $this->denormalizeCollectionFromArray($attribute, $type, $className, $value, $format, $context)
            );

            return;
        }

        if (null !== $className = $type->getClassName()) {
            $this->setValue(
                $object,
                $attribute,
                $this->denormalizeRelationFromArray($className, $value, $context, $format)
            );

            return;
        }

        $this->validateType($attribute, $type, $value, $format);
        $this->setValue($object, $attribute, $value);
    }

    /**
     * {@inheritdoc}
     *
     * @throws NoSuchPropertyException
     */
    protected function getAttributeValue($object, $attribute, $format = null, array $context = [])
    {
        $propertyMetadata = $this->propertyMetadataFactory->create($context['resource_class'], $attribute, $this->getFactoryOptions($context));

        try {
            $attributeValue = $this->propertyAccessor->getValue($object, $attribute);
        } catch (NoSuchPropertyException $e) {
            if (null === $propertyMetadata->isChildInherited()) {
                throw $e;
            }

            $attributeValue = null;
        }

        $type = $propertyMetadata->getType();

        if (
            (is_array($attributeValue) || $attributeValue instanceof \Traversable) &&
            $type &&
            $type->isCollection() &&
            ($collectionValueType = $type->getCollectionValueType()) &&
            ($className = $collectionValueType->getClassName()) &&
            $this->resourceClassResolver->isResourceClass($className)
        ) {
            $value = [];
            foreach ($attributeValue as $index => $obj) {
                $value[$index] = $this->normalizeRelationToArray($obj);
            }

            return $value;
        }

        if (
            $attributeValue &&
            $type &&
            ($className = $type->getClassName()) &&
            $this->resourceClassResolver->isResourceClass($className)
        ) {
            return $this->normalizeRelationToArray($attributeValue);
        }

        return $this->serializer->normalize($attributeValue, $format, $context);
    }

    /**
     * {@inheritdoc}
     */
    protected function isAllowedAttribute($classOrObject, $attribute, $format = null, array $context = [])
    {
        if (!preg_match('/^\\w[-\\w_]*$/', $attribute)) {
            return false;
        }

        return parent::isAllowedAttribute($classOrObject, $attribute, $format, $context);
    }

    /**
     * Sets a value of the object using the PropertyAccess component.
     *
     * @param object $object
     * @param string $attributeName
     * @param mixed  $value
     */
    private function setValue($object, string $attributeName, $value)
    {
        try {
            $this->propertyAccessor->setValue($object, $attributeName, $value);
        } catch (NoSuchPropertyException $exception) {
            // Properties not found are ignored
        }
    }

    /**
     * Denormalizes a collection of objects.
     *
     * @param string      $attributeName
     * @param Type        $type
     * @param string      $className
     * @param mixed       $rawData
     * @param string|null $format
     * @param array       $context
     *
     * @throws InvalidArgumentException
     *
     * @return array
     */
    private function denormalizeCollectionFromArray(string $attributeName, Type $type, string $className, $rawData, string $format = null, array $context): array
    {
        // A 'data' key is expected as the first level of the array
        $data = $rawData['data'];

        if (!is_array($data)) {
            throw new InvalidArgumentException(sprintf(
                'The type of the "%s" attribute must be "array", "%s" given.', $attributeName, gettype($data)
            ));
        }

        $collectionKeyType = $type->getCollectionKeyType();
        $collectionKeyBuiltinType = null === $collectionKeyType ? null : $collectionKeyType->getBuiltinType();

        $values = [];
        foreach ($data as $rawIndex => $obj) {
            $index = $rawIndex;

            // Given JSON API forces ids to be strings, we might need to cast stuff
            if (null !== $collectionKeyBuiltinType && 'int' === $collectionKeyBuiltinType) {
                $index = (int) $index;
            } elseif (null !== $collectionKeyBuiltinType && !call_user_func('is_'.$collectionKeyBuiltinType, $index)) {
                throw new InvalidArgumentException(sprintf(
                    'The type of the key "%s" must be "%s", "%s" given.',
                    $index,
                    $collectionKeyBuiltinType,
                    gettype($index)
                ));
            }

            $values[$index] = $this->denormalizeRelationFromArray($className, $obj, $context, $format);
        }

        return $values;
    }

    /**
     * Gets JSON API components of the resource: attributes, relationships, meta and links.
     *
     * @param object      $object
     * @param string|null $format
     * @param array       $context
     *
     * @return array
     */
    private function getComponents($object, string $format = null, array $context)
    {
        if (isset($this->componentsCache[$context['cache_key']])) {
            return $this->componentsCache[$context['cache_key']];
        }

        $attributes = parent::getAttributes($object, $format, $context);

        $options = $this->getFactoryOptions($context);

        $components = [
            'links' => [],
            'relationships' => [],
            'attributes' => [],
            'meta' => [],
        ];

        foreach ($attributes as $attribute) {
            $propertyMetadata = $this
                ->propertyMetadataFactory
                ->create($context['resource_class'], $attribute, $options);

            $type = $propertyMetadata->getType();
            $isOne = $isMany = false;

            if (null !== $type) {
                if ($type->isCollection()) {
                    $isMany = ($type->getCollectionValueType() && $className = $type->getCollectionValueType()->getClassName()) ? $this->resourceClassResolver->isResourceClass($className) : false;
                } else {
                    $isOne = ($className = $type->getClassName()) ? $this->resourceClassResolver->isResourceClass($className) : false;
                }
            }

            if (!isset($className) || !$isOne && !$isMany) {
                $components['attributes'][] = $attribute;

                continue;
            }

            $relation = [
                'name' => $attribute,
                'type' => $this->resourceMetadataFactory->create($className)->getShortName(),
                'cardinality' => $isOne ? 'one' : 'many',
            ];

            $components['relationships'][] = $relation;
        }

        return $this->componentsCache[$context['cache_key']] = $components;
    }

    /**
     * Populates relationships keys.
     *
     * @param object      $object
     * @param string|null $format
     * @param array       $context
     * @param array       $relationships
     *
     * @return array
     */
    private function getPopulatedRelations($object, string $format = null, array $context, array $relationships): array
    {
        $data = [];

        foreach ($relationships as $relationshipDataArray) {
            $relationshipName = $relationshipDataArray['name'];

            $attributeValue = $this->getAttributeValue(
                $object,
                $relationshipName,
                $format,
                $context
            );

            if ($this->nameConverter) {
                $relationshipName = $this->nameConverter->normalize($relationshipName);
            }

            if (!$attributeValue) {
                continue;
            }

            $data[$relationshipName] = [
                'data' => [],
            ];

            // Many to one relationship
            if ('one' === $relationshipDataArray['cardinality']) {
                $data[$relationshipName] = $attributeValue;

                continue;
            }

            // Many to many relationship
            foreach ($attributeValue as $attributeValueElement) {
                if (!isset($attributeValueElement['data'])) {
                    throw new RuntimeException(sprintf(
                        'The JSON API attribute \'%s\' must contain a "data" key.',
                        $relationshipName
                    ));
                }

                $data[$relationshipName]['data'][] = $attributeValueElement['data'];
            }
        }

        return $data;
    }

    /**
     * Gets the cache key to use.
     *
     * @param string|null $format
     * @param array       $context
     *
     * @return bool|string
     */
    private function getJsonApiCacheKey(string $format = null, array $context)
    {
        try {
            return md5($format.serialize($context));
        } catch (\Exception $exception) {
            // The context cannot be serialized, skip the cache
            return false;
        }
    }

    /**
     * Denormalizes a resource linkage relation.
     *
     * @see http://jsonapi.org/format/#document-resource-object-linkage
     *
     * @param string      $className
     * @param mixed       $rawData
     * @param array       $context
     * @param string|null $format
     *
     * @return object|null
     */
    private function denormalizeRelationFromArray(string $className, $rawData, array $context, string $format = null)
    {
        // Give a chance to other normalizers (e.g.: DateTimeNormalizer)
        if (!is_array($rawData) || !$this->resourceClassResolver->isResourceClass($className)) {
            return $this->serializer->denormalize($rawData, $className, $format, $this->createRelationSerializationContext($className, $context));
        }

        $dataToDenormalize = $rawData;

        if (array_key_exists('data', $rawData)) {
            $dataToDenormalize = $rawData['data'];
        }

        // Null is allowed for empty to-one relationships, see http://jsonapi.org/format/#document-resource-object-linkage
        // An empty array is allowed for empty to-many relationships, see http://jsonapi.org/format/#document-resource-object-linkage
        if (!$dataToDenormalize) {
            return;
        }

        if (!is_array($dataToDenormalize) && 2 !== count($dataToDenormalize) && !isset($dataToDenormalize['id'])) {
            throw new InvalidArgumentException('Only resource linkage supported currently, see: http://jsonapi.org/format/#document-resource-object-linkage.');
        }

        return $this->itemDataProvider->getItem(
            $this->resourceClassResolver->getResourceClass(null, $className),
            $dataToDenormalize['id'],
            null,
            $context + ['fetch_data' => true]
        );
    }

    /**
     * Normalizes a relation as resource linkage relation.
     *
     * For example, it may return the following array:
     *
     * [
     *     'data' => [
     *         'type' => 'dummy',
     *         'id' => '1'
     *     ]
     * ]
     *
     * @see http://jsonapi.org/format/#document-resource-object-linkage
     *
     * @param object $relatedObject
     *
     * @return string|array
     */
    private function normalizeRelationToArray($relatedObject)
    {
        $resourceClass = $this->resourceClassResolver->getResourceClass(
            $relatedObject,
            null,
            true
        );

        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
        $identifier = $this->getIdentifierFromItem($relatedObject);

        return ['data' => [
            'type' => $resourceMetadata->getShortName(),
            'id' => $identifier,
        ]];
    }

    /**
     * Find identifier from an Item (Object).
     *
     * @param object $item
     *
     * @return string {@see http://jsonapi.org/format/#document-resource-object-identification}
     */
    private function getIdentifierFromItem($item): string
    {
        if (1 !== count($identifiers = $this->identifiersExtractor->getIdentifiersFromItem($item))) {
            throw new InvalidArgumentException();
        }

        return (string) array_values($identifiers)[0];
    }
}
