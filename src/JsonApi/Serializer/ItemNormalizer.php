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

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Api\OperationType;
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Exception\ItemNotFoundException;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Serializer\AbstractItemNormalizer;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Converts between objects and array.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class ItemNormalizer extends AbstractItemNormalizer
{
    const FORMAT = 'jsonapi';

    private $componentsCache = [];
    private $resourceMetadataFactory;

    public function __construct(PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, IriConverterInterface $iriConverter, ResourceClassResolverInterface $resourceClassResolver, PropertyAccessorInterface $propertyAccessor = null, NameConverterInterface $nameConverter = null, ResourceMetadataFactoryInterface $resourceMetadataFactory)
    {
        parent::__construct($propertyNameCollectionFactory, $propertyMetadataFactory, $iriConverter, $resourceClassResolver, $propertyAccessor, $nameConverter);

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
        if (!isset($context['cache_key'])) {
            $context['cache_key'] = $this->getJsonApiCacheKey($format, $context);
        }

        // Get and populate attributes data
        $objectAttributesData = parent::normalize($object, $format, $context);

        if (!\is_array($objectAttributesData)) {
            return $objectAttributesData;
        }

        // Get and populate item type
        $resourceClass = $this->resourceClassResolver->getResourceClass($object, $context['resource_class'] ?? null, true);
        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);

        // Get and populate relations
        $components = $this->getComponents($object, $format, $context);
        $populatedRelationContext = $context;
        $objectRelationshipsData = $this->getPopulatedRelations($object, $format, $populatedRelationContext, $components['relationships']);
        $objectRelatedResources = $this->getRelatedResources($object, $format, $context, $components['relationships']);

        $item = [
            'id' => $this->iriConverter->getIriFromItem($object),
            'type' => $resourceMetadata->getShortName(),
        ];

        if ($objectAttributesData) {
            $item['attributes'] = $objectAttributesData;
        }

        if ($objectRelationshipsData) {
            $item['relationships'] = $objectRelationshipsData;
        }

        $data = ['data' => $item];

        if ($objectRelatedResources) {
            $data['included'] = $objectRelatedResources;
        }

        return $data;
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
        if (!isset($context[self::OBJECT_TO_POPULATE]) && isset($data['data']['id'])) {
            if (isset($context['api_allow_update']) && true !== $context['api_allow_update']) {
                throw new InvalidArgumentException('Update is not allowed for this operation.');
            }

            $context[self::OBJECT_TO_POPULATE] = $this->iriConverter->getItemFromIri(
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
    protected function getAttributes($object, $format = null, array $context)
    {
        return $this->getComponents($object, $format, $context)['attributes'];
    }

    /**
     * {@inheritdoc}
     */
    protected function setAttributeValue($object, $attribute, $value, $format = null, array $context = [])
    {
        parent::setAttributeValue($object, $attribute, \is_array($value) && array_key_exists('data', $value) ? $value['data'] : $value, $format, $context);
    }

    /**
     * {@inheritdoc}
     *
     * @see http://jsonapi.org/format/#document-resource-object-linkage
     */
    protected function denormalizeRelation(string $attributeName, PropertyMetadata $propertyMetadata, string $className, $value, string $format = null, array $context)
    {
        // Give a chance to other normalizers (e.g.: DateTimeNormalizer)
        if (!$this->resourceClassResolver->isResourceClass($className)) {
            $context['resource_class'] = $className;

            if ($this->serializer instanceof DenormalizerInterface) {
                return $this->serializer->denormalize($value, $className, $format, $context);
            }
            throw new InvalidArgumentException(sprintf('The injected serializer must be an instance of "%s".', DenormalizerInterface::class));
        }

        if (!\is_array($value) || !isset($value['id'], $value['type'])) {
            throw new InvalidArgumentException('Only resource linkage supported currently, see: http://jsonapi.org/format/#document-resource-object-linkage.');
        }

        try {
            return $this->iriConverter->getItemFromIri($value['id'], $context + ['fetch_data' => true]);
        } catch (ItemNotFoundException $e) {
            throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @see http://jsonapi.org/format/#document-resource-object-linkage
     */
    protected function normalizeRelation(PropertyMetadata $propertyMetadata, $relatedObject, string $resourceClass, string $format = null, array $context)
    {
        if (null === $relatedObject) {
            if (isset($context['operation_type'], $context['subresource_resources'][$resourceClass]) && OperationType::SUBRESOURCE === $context['operation_type']) {
                $iri = $this->iriConverter->getItemIriFromResourceClass($resourceClass, $context['subresource_resources'][$resourceClass]);
            } else {
                unset($context['resource_class']);

                if ($this->serializer instanceof NormalizerInterface) {
                    return $this->serializer->normalize($relatedObject, $format, $context);
                }
                throw new InvalidArgumentException(sprintf('The injected serializer must be an instance of "%s".', NormalizerInterface::class));
            }
        } else {
            $iri = $this->iriConverter->getIriFromItem($relatedObject);

            if (isset($context['resources'])) {
                $context['resources'][$iri] = $iri;
            }
            if (isset($context['api_included'])) {
                $context['api_sub_level'] = true;

                if (!$this->serializer instanceof NormalizerInterface) {
                    throw new InvalidArgumentException(sprintf('The injected serializer must be an instance of "%s".', NormalizerInterface::class));
                }
                $data = $this->serializer->normalize($relatedObject, $format, $context);
                unset($context['api_sub_level']);

                return $data;
            }
        }

        return ['data' => [
            'type' => $this->resourceMetadataFactory->create($resourceClass)->getShortName(),
            'id' => $iri,
        ]];
    }

    /**
     * {@inheritdoc}
     */
    protected function isAllowedAttribute($classOrObject, $attribute, $format = null, array $context = [])
    {
        return preg_match('/^\\w[-\\w_]*$/', $attribute) && parent::isAllowedAttribute($classOrObject, $attribute, $format, $context);
    }

    /**
     * Gets JSON API components of the resource: attributes, relationships, meta and links.
     *
     * @param object $object
     *
     * @return array
     */
    private function getComponents($object, string $format = null, array $context)
    {
        $cacheKey = \get_class($object).'-'.$context['cache_key'];

        if (isset($this->componentsCache[$cacheKey])) {
            return $this->componentsCache[$cacheKey];
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

        if (false !== $context['cache_key']) {
            $this->componentsCache[$cacheKey] = $components;
        }

        return $components;
    }

    /**
     * Populates relationships keys.
     *
     * @param object $object
     *
     * @throws InvalidArgumentException
     */
    private function getPopulatedRelations($object, string $format = null, array $context, array $relationships): array
    {
        $data = [];

        if (!isset($context['resource_class'])) {
            return $data;
        }

        unset($context['api_included']);
        foreach ($relationships as $relationshipDataArray) {
            $relationshipName = $relationshipDataArray['name'];

            $attributeValue = $this->getAttributeValue($object, $relationshipName, $format, $context);

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
                unset($attributeValue['data']['attributes']);
                $data[$relationshipName] = $attributeValue;

                continue;
            }

            // Many to many relationship
            foreach ($attributeValue as $attributeValueElement) {
                if (!isset($attributeValueElement['data'])) {
                    throw new InvalidArgumentException(sprintf('The JSON API attribute \'%s\' must contain a "data" key.', $relationshipName));
                }
                unset($attributeValueElement['data']['attributes']);
                $data[$relationshipName]['data'][] = $attributeValueElement['data'];
            }
        }

        return $data;
    }

    /**
     * Populates included keys.
     */
    private function getRelatedResources($object, string $format = null, array $context, array $relationships): array
    {
        if (!isset($context['api_included'])) {
            return [];
        }

        $included = [];
        foreach ($relationships as $relationshipDataArray) {
            if (!\in_array($relationshipDataArray['name'], $context['api_included'], true)) {
                continue;
            }

            $relationshipName = $relationshipDataArray['name'];
            $relationContext = $context;
            $attributeValue = $this->getAttributeValue($object, $relationshipName, $format, $relationContext);

            if (!$attributeValue) {
                continue;
            }

            // Many to one relationship
            if ('one' === $relationshipDataArray['cardinality']) {
                $included[] = $attributeValue['data'];

                continue;
            }
            // Many to many relationship
            foreach ($attributeValue as $attributeValueElement) {
                if (isset($attributeValueElement['data'])) {
                    $included[] = $attributeValueElement['data'];
                }
            }
        }

        return $included;
    }

    /**
     * Gets the cache key to use.
     *
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
}
