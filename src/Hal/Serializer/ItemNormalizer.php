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
use ApiPlatform\Core\Util\ClassInfoTrait;
use Symfony\Component\Serializer\Mapping\AttributeMetadataInterface;

/**
 * Converts between objects and array including HAL metadata.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ItemNormalizer extends AbstractItemNormalizer
{
    use ContextTrait;
    use ClassInfoTrait;

    const FORMAT = 'jsonhal';

    private $componentsCache = [];
    private $attributesMetadataCache = [];

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
            $context['cache_key'] = $this->getHalCacheKey($format, $context);
        }

        $resourceClass = $this->resourceClassResolver->getResourceClass($object, $context['resource_class'] ?? null, true);
        $context = $this->initContext($resourceClass, $context);
        $context['iri'] = $this->iriConverter->getIriFromItem($object);
        $context['api_normalize'] = true;

        $rawData = parent::normalize($object, $format, $context);
        if (!\is_array($rawData)) {
            return $rawData;
        }

        $data = ['_links' => ['self' => ['href' => $context['iri']]]];
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
    protected function getAttributes($object, $format = null, array $context)
    {
        return $this->getComponents($object, $format, $context)['states'];
    }

    /**
     * Gets HAL components of the resource: states, links and embedded.
     *
     * @param object $object
     *
     * @return array
     */
    private function getComponents($object, string $format = null, array $context)
    {
        $cacheKey = $this->getObjectClass($object).'-'.$context['cache_key'];

        if (isset($this->componentsCache[$cacheKey])) {
            return $this->componentsCache[$cacheKey];
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

        if (false !== $context['cache_key']) {
            $this->componentsCache[$cacheKey] = $components;
        }

        return $components;
    }

    /**
     * Populates _links and _embedded keys.
     *
     * @param object $object
     */
    private function populateRelation(array $data, $object, string $format = null, array $context, array $components, string $type): array
    {
        $class = $this->getObjectClass($object);

        $attributesMetadata = \array_key_exists($class, $this->attributesMetadataCache) ?
            $this->attributesMetadataCache[$class] :
            $this->attributesMetadataCache[$class] = $this->classMetadataFactory ? $this->classMetadataFactory->getMetadataFor($object)->getAttributesMetadata() : null;

        $key = '_'.$type;
        foreach ($components[$type] as $relation) {
            if (null !== $attributesMetadata && $this->isMaxDepthReached($attributesMetadata, $class, $relation['name'], $context)) {
                continue;
            }

            $attributeValue = $this->getAttributeValue($object, $relation['name'], $format, $context);
            if (empty($attributeValue)) {
                continue;
            }

            $relationName = $relation['name'];
            if ($this->nameConverter) {
                $relationName = $this->nameConverter->normalize($relationName);
            }

            if ('one' === $relation['cardinality']) {
                if ('links' === $type) {
                    $data[$key][$relationName]['href'] = $this->getRelationIri($attributeValue);
                    continue;
                }

                $data[$key][$relationName] = $attributeValue;
                continue;
            }

            // many
            $data[$key][$relationName] = [];
            foreach ($attributeValue as $rel) {
                if ('links' === $type) {
                    $rel = ['href' => $this->getRelationIri($rel)];
                }

                $data[$key][$relationName][] = $rel;
            }
        }

        return $data;
    }

    /**
     * Gets the IRI of the given relation.
     *
     * @param array|string $rel
     */
    private function getRelationIri($rel): string
    {
        return $rel['_links']['self']['href'] ?? $rel;
    }

    /**
     * Gets the cache key to use.
     *
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

    /**
     * Is the max depth reached for the given attribute?
     *
     * @param AttributeMetadataInterface[] $attributesMetadata
     */
    private function isMaxDepthReached(array $attributesMetadata, string $class, string $attribute, array &$context): bool
    {
        if (
            !($context[static::ENABLE_MAX_DEPTH] ?? false) ||
            !isset($attributesMetadata[$attribute]) ||
            null === $maxDepth = $attributesMetadata[$attribute]->getMaxDepth()
        ) {
            return false;
        }

        $key = sprintf(static::DEPTH_KEY_PATTERN, $class, $attribute);
        if (!isset($context[$key])) {
            $context[$key] = 1;

            return false;
        }

        if ($context[$key] === $maxDepth) {
            return true;
        }

        ++$context[$key];

        return false;
    }
}
