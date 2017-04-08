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
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Serializer\AbstractItemNormalizer;
use ApiPlatform\Core\Serializer\ContextTrait;
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
        $context['cache_key'] = $this->getCacheKey($format, $context);

        $rawData = parent::normalize($object, $format, $context);
        if (!is_array($rawData)) {
            return $rawData;
        }

        $data = [];
        $components = $this->getComponents($object, $format, $context);
        $data = $this->populateRelation($data, $object, $format, $context, $components, 'links');
        $data = $this->populateRelation($data, $object, $format, $context, $components, 'relationships');

        return $data + $rawData;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return true;
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

            $context['object_to_populate'] = $this->iriConverter->getItemFromIri($data['data']['id'], $context + ['fetch_data' => false]);
        }

        return parent::denormalize(isset($data['data']) ? $data['data']['attributes']: $data, $class, $format, $context);
    }

    /**
     * {@inheritdoc}
     */
    protected function getAttributes($object, $format, array $context)
    {
        return $this->getComponents($object, $format, $context)['attributes'];
    }

    /**
     * Gets HAL components of the resource: links and embedded.
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
            $propertyMetadata = $this->propertyMetadataFactory->create($context['resource_class'], $attribute, $options);
            $type = $propertyMetadata->getType();
            $isOne = $isMany = false;

            if (null !== $type) {
                if ($type->isCollection()) {
                    $valueType = $type->getCollectionValueType();
                    $isMany = null !== $valueType && ($className = $valueType->getClassName()) && $this->resourceClassResolver->isResourceClass($className);
                } else {
                    $className = $type->getClassName();
                    $isOne = null !== $className && $this->resourceClassResolver->isResourceClass($className);
                }
                $shortName = (null !== $className && $this->resourceClassResolver->isResourceClass($className) ? $this->resourceMetadataFactory->create($className)->getShortName() : '');
            }

            if (!$isOne && !$isMany) {
                $components['attributes'][] = $attribute;
                continue;
            }

            $relation = ['name' => $attribute, 'type' => $shortName, 'cardinality' => $isOne ? 'one' : 'many'];

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
    private function populateRelation(array $data, $object, string $format = null, array $context, array $components, string $type): array
    {
        $identifier = '';
        foreach ($components[$type] as $relation) {
            $attributeValue = $this->getAttributeValue($object, $relation['name'], $format, $context);
            if (empty($attributeValue)) {
                continue;
            }
            $data[$type][$relation['name']] = [];
            $data[$type][$relation['name']]['links'] = ['self' => $this->iriConverter->getIriFromItem($object)];
            $data[$type][$relation['name']]['data'] = [];
            if ('one' === $relation['cardinality']) {
                if ('links' === $type) {
                    $data[$type][$relation['name']]['data'][] = ['id' => $this->getRelationIri($attributeValue)];
                    continue;
                }

                $data[$type][$relation['name']] = $attributeValue;
                continue;
            }

            // many
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

                if (!empty($relation['type'])) {
                    $data[$type][$relation['name']]['data'][] = $id + ['type' => $relation['type']];
                } else {
                    $data[$type][$relation['name']]['data'][] = $id;
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
}
