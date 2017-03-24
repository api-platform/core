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

namespace ApiPlatform\Core\Hal\Serializer;

use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Serializer\AbstractItemNormalizer;
use ApiPlatform\Core\Serializer\ContextTrait;

/**
 * Converts between objects and array including HAL metadata.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ItemNormalizer extends AbstractItemNormalizer
{
    use ContextTrait;

    const FORMAT = 'jsonhal';

    private $componentsCache = [];

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
        $context['cache_key'] = $this->getHalCacheKey($format, $context);

        $rawData = parent::normalize($object, $format, $context);
        if (!is_array($rawData)) {
            return $rawData;
        }

        $data = ['_links' => ['self' => ['href' => $this->iriConverter->getIriFromItem($object)]]];
        $components = $this->getComponents($object, $format, $context);
        $data = $this->populateRelation($data, $object, $format, $context, $components, 'links');
        $data = $this->populateRelation($data, $object, $format, $context, $components, 'embedded');

        return $data + $rawData;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        throw new RuntimeException(sprintf('%s is a read-only format.', self::FORMAT));
    }

    /**
     * {@inheritdoc}
     */
    protected function getAttributes($object, $format, array $context)
    {
        return $this->getComponents($object, $format, $context)['states'];
    }

    /**
     * Gets HAL components of the resource: states, links and embedded.
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
            'states' => [],
            'links' => [],
            'embedded' => [],
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
                    $isOne = $className && $this->resourceClassResolver->isResourceClass($className);
                }
            }

            if (!$isOne && !$isMany) {
                $components['states'][] = $attribute;
                continue;
            }

            $relation = ['name' => $attribute, 'cardinality' => $isOne ? 'one' : 'many'];
            if ($propertyMetadata->isReadableLink()) {
                $components['embedded'][] = $relation;
            }

            $components['links'][] = $relation;
        }

        return $this->componentsCache[$context['cache_key']] = $components;
    }

    /**
     * Populates _links and _embedded keys.
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
        $key = '_'.$type;
        foreach ($components[$type] as $relation) {
            $attributeValue = $this->getAttributeValue($object, $relation['name'], $format, $context);
            if (empty($attributeValue)) {
                continue;
            }

            if ('one' === $relation['cardinality']) {
                if ('links' === $type) {
                    $data[$key][$relation['name']]['href'] = $this->getRelationIri($attributeValue);
                    continue;
                }

                $data[$key][$relation['name']] = $attributeValue;
                continue;
            }

            // many
            $data[$key][$relation['name']] = [];
            foreach ($attributeValue as $rel) {
                if ('links' === $type) {
                    $rel = ['href' => $this->getRelationIri($rel)];
                }

                $data[$key][$relation['name']][] = $rel;
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
        return $rel['_links']['self']['href'] ?? $rel;
    }

    /**
     * Gets the cache key to use.
     *
     * @param string|null $format
     * @param array       $context
     *
     * @return bool|string
     */
    private function getHalCacheKey(string $format = null, array $context)
    {
        try {
            return md5($format.serialize($context));
        } catch (\Exception $exception) {
            // The context cannot be serialized, skip the cache
            return false;
        }
    }
}
