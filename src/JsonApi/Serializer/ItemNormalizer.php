<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\JsonApi\Serializer;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Serializer\AbstractItemNormalizer;
use ApiPlatform\Core\Serializer\ContextTrait;
use ApiPlatform\Core\Util\ClassInfoTrait;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

/**
 * Converts between objects and array including HAL metadata.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
final class ItemNormalizer extends AbstractItemNormalizer
{
    use ContextTrait;
    use ClassInfoTrait;

    const FORMAT = 'jsonapi';

    private $componentsCache = [];

    private $resourceMetadataFactory;

    private $itemDataProvider;

    public function __construct(
        PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory,
        PropertyMetadataFactoryInterface $propertyMetadataFactory,
        IriConverterInterface $iriConverter,
        ResourceClassResolverInterface $resourceClassResolver,
        PropertyAccessorInterface $propertyAccessor = null,
        NameConverterInterface $nameConverter = null,
        ResourceMetadataFactoryInterface $resourceMetadataFactory,
        ItemDataProviderInterface $itemDataProvider
    ) {
        parent::__construct(
            $propertyNameCollectionFactory,
            $propertyMetadataFactory,
            $iriConverter,
            $resourceClassResolver,
            $propertyAccessor,
            $nameConverter
        );

        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->itemDataProvider = $itemDataProvider;
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
        $context['cache_key'] = $this->getCacheKey($format, $context);

        // Get and populate attributes data
        $objectAttributesData = parent::normalize($object, $format, $context);

        if (!is_array($objectAttributesData)) {
            return $objectAttributesData;
        }

        // Get and populate identifier if existent
        $identifier = $this->getItemIdentifierValue($object, $context, $objectAttributesData);

        // Get and populate item type
        $resourceClass = $this->resourceClassResolver->getResourceClass(
            $object,
            $context['resource_class'] ?? null,
            true
        );
        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);

        // Get and populate relations
        $components = $this->getComponents($object, $format, $context);
        $objectRelationshipsData = $this->getPopulatedRelations(
            $object,
            $format,
            $context,
            $components
        );

        // TODO: Pending population of links
        // $item = $this->populateRelation($item, $object, $format, $context, $components, 'links');

        $item = [
            // The id attribute must be a string
            // See: http://jsonapi.org/format/#document-resource-object-identification
            'id' => (string) $identifier,
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
        // TODO: Test what is this about
        // Avoid issues with proxies if we populated the object
        // if (isset($data['data']['id']) && !isset($context['object_to_populate'])) {
        //     if (isset($context['api_allow_update']) && true !== $context['api_allow_update']) {
        //         throw new InvalidArgumentException('Update is not allowed for this operation.');
        //     }

        //     $context['object_to_populate'] = $this->iriConverter->getItemFromIri(
        //         $data['data']['id'],
        //         $context + ['fetch_data' => false]
        //     );
        // }

        // Approach #1
        // Merge attributes and relations previous to apply parents denormalizing
        $dataToDenormalize = array_merge(
            isset($data['data']['attributes']) ?
                $data['data']['attributes'] : [],
            isset($data['data']['relationships']) ?
                $data['data']['relationships'] : []
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

        $shortName = $className = '';

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
                    $valueType = $type->getCollectionValueType();

                    $isMany = null !== $valueType
                        && ($className = $valueType->getClassName())
                        && $this->resourceClassResolver->isResourceClass($className);
                } else {
                    $className = $type->getClassName();

                    $isOne = null !== $className
                        && $this->resourceClassResolver->isResourceClass($className);
                }

                $shortName =
                    (
                        null !== $className
                            && $this->resourceClassResolver->isResourceClass($className)
                                ? $this->resourceMetadataFactory->create($className)->getShortName() :
                                ''
                    );
            }

            if (!$isOne && !$isMany) {
                $components['attributes'][] = $attribute;

                continue;
            }

            $relation = [
                'name' => $attribute,
                'type' => $shortName,
                'cardinality' => $isOne ? 'one' : 'many',
            ];

            $components['relationships'][] = $relation;
        }

        return $this->componentsCache[$context['cache_key']] = $components;
    }

    /**
     * Populates links and relationships keys.
     *
     * @param array       $data
     * @param object      $object
     * @param string|null $format
     * @param array       $context
     * @param array       $components
     * @param string      $type
     *
     * @return array
     */
    private function getPopulatedRelations(
        $object,
        string $format = null,
        array $context,
        array $components,
        string $type = 'relationships'
    ): array {
        $data = [];

        $identifier = '';
        foreach ($components[$type] as $relation) {
            $attributeValue = $this->getAttributeValue(
                $object,
                $relation['name'],
                $format,
                $context
            );

            if (!$attributeValue) {
                continue;
            }

            $data[$relation['name']] = [
                // TODO: Pending review
                // 'links' => ['self' => $this->iriConverter->getIriFromItem($object)],
                'data' => [],
            ];

            // Many to one relationship
            if ('one' === $relation['cardinality']) {
                // TODO: Pending review
                // if ('links' === $type) {
                //     $data[$relation['name']]['data'][] = ['id' => $this->getRelationIri($attributeValue)];

                //     continue;
                // }

                $data[$relation['name']] = $attributeValue;

                continue;
            }

            // TODO: Pending review
            // Many to many relationship
            foreach ($attributeValue as $rel) {
                if ('links' === $type) {
                    $rel = $this->getRelationIri($rel);
                }
                $id = ['id' => $rel];

                if (!is_string($rel)) {
                    foreach ($rel as $property => $value) {
                        $propertyMetadata = $this->propertyMetadataFactory->create($context['resource_class'], $property);
                        if ($propertyMetadata->isIdentifier()) {
                            $identifier = $rel[$property];
                        }
                    }
                    $id = ['id' => $identifier] + $rel;
                }

                if ($relation['type']) {
                    $data[$relation['name']]['data'][] = $id + ['type' => $relation['type']];
                } else {
                    $data[$relation['name']]['data'][] = $id;
                }
            }
        }

        return $data;
    }

    /**
     * Gets the IRI of the given relation.
     *
     * @param array|string $rel
     *
     * @return string
     */
    private function getRelationIri($rel): string
    {
        return isset($rel['links']['self']) ? $rel['links']['self'] : $rel;
    }

    /**
     * Gets the cache key to use.
     *
     * @param string|null $format
     * @param array       $context
     *
     * @return bool|string
     */
    private function getCacheKey(string $format = null, array $context)
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
     * See: http://jsonapi.org/format/#document-resource-object-linkage
     *
     * @param string           $attributeName    [description]
     * @param PropertyMetadata $propertyMetadata [description]
     * @param string           $className        [description]
     * @param [type]           $data             [description]
     * @param string|null      $format           [description]
     * @param array            $context          [description]
     *
     * @return [type] [description]
     */
    protected function denormalizeRelation(
        string $attributeName,
        PropertyMetadata $propertyMetadata,
        string $className,
        $data,
        string $format = null,
        array $context
    ) {
        if (!isset($data['data'])) {
            throw new InvalidArgumentException(
                'Key \'data\' expected. Only resource linkage currently supported, see: http://jsonapi.org/format/#document-resource-object-linkage'
            );
        }

        $data = $data['data'];

        if (!is_array($data) || 2 !== count($data)) {
            throw new InvalidArgumentException(
                'Only resource linkage supported currently supported, see: http://jsonapi.org/format/#document-resource-object-linkage'
            );
        }

        if (!isset($data['id'])) {
            throw new InvalidArgumentException(
                'Only resource linkage supported currently supported, see: http://jsonapi.org/format/#document-resource-object-linkage'
            );
        }

        return $this->itemDataProvider->getItem(
            $this->resourceClassResolver->getResourceClass(null, $className),
            $data['id']
        );
    }

    /**
     * Normalizes a relation as resource linkage relation.
     *
     * See: http://jsonapi.org/format/#document-resource-object-linkage
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
     * @param PropertyMetadata $propertyMetadata
     * @param mixed            $relatedObject
     * @param string           $resourceClass
     * @param string|null      $format
     * @param array            $context
     *
     * @return string|array
     */
    protected function normalizeRelation(
        PropertyMetadata $propertyMetadata,
        $relatedObject,
        string $resourceClass,
        string $format = null,
        array $context
    ) {
        $resourceClass = $this->resourceClassResolver->getResourceClass(
            $relatedObject,
            null,
            true
        );

        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);

        $identifiers = $this->getIdentifiersFromItem($relatedObject);

        if (count($identifiers) > 1) {
            throw new RuntimeException(sprintf(
                'Multiple identifiers are not supported during serialization of relationships (Entity: \'%s\')',
                $resourceClass
            ));
        }

        return ['data' => [
            'type' => $resourceMetadata->getShortName(),
            'id' => (string) reset($identifiers),
        ]];
    }

    private function getItemIdentifierValue($object, $context, $objectAttributesData)
    {
        $resourceClass = $this->resourceClassResolver->getResourceClass(
            $object,
            $context['resource_class'] ?? null,
            true
        );

        foreach ($objectAttributesData as $attributeName => $value) {
            $propertyMetadata = $this
                ->propertyMetadataFactory
                ->create($resourceClass, $attributeName);

            if ($propertyMetadata->isIdentifier()) {
                return $objectAttributesData[$attributeName];
            }
        }

        return null;
    }

    /**
     * Find identifiers from an Item (Object).
     *
     * Taken from ApiPlatform\Core\Bridge\Symfony\Routing\IriConverter
     *
     * TODO: Review if this would be useful if defined somewhere else
     *
     * @param object $item
     *
     * @throws RuntimeException
     *
     * @return array
     */
    private function getIdentifiersFromItem($item): array
    {
        $identifiers = [];
        $resourceClass = $this->getObjectClass($item);

        foreach ($this->propertyNameCollectionFactory->create($resourceClass) as $propertyName) {
            $propertyMetadata = $this->propertyMetadataFactory->create($resourceClass, $propertyName);

            $identifier = $propertyMetadata->isIdentifier();
            if (null === $identifier || false === $identifier) {
                continue;
            }

            $identifiers[$propertyName] = $this->propertyAccessor->getValue($item, $propertyName);

            if (!is_object($identifiers[$propertyName])) {
                continue;
            }

            $relatedResourceClass = $this->getObjectClass($identifiers[$propertyName]);
            $relatedItem = $identifiers[$propertyName];

            unset($identifiers[$propertyName]);

            foreach ($this->propertyNameCollectionFactory->create($relatedResourceClass) as $relatedPropertyName) {
                $propertyMetadata = $this->propertyMetadataFactory->create($relatedResourceClass, $relatedPropertyName);

                if ($propertyMetadata->isIdentifier()) {
                    if (isset($identifiers[$propertyName])) {
                        throw new RuntimeException(sprintf(
                            'Composite identifiers not supported in "%s" through relation "%s" of "%s" used as identifier',
                            $relatedResourceClass,
                            $propertyName,
                            $resourceClass
                        ));
                    }

                    $identifiers[$propertyName] = $this->propertyAccessor->getValue(
                        $relatedItem,
                        $relatedPropertyName
                    );
                }
            }

            if (!isset($identifiers[$propertyName])) {
                throw new RuntimeException(sprintf(
                    'No identifier found in "%s" through relation "%s" of "%s" used as identifier',
                    $relatedResourceClass,
                    $propertyName,
                    $resourceClass
                ));
            }
        }

        return $identifiers;
    }
}
