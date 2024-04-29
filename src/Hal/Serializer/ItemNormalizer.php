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

namespace ApiPlatform\Hal\Serializer;

use ApiPlatform\Metadata\UrlGeneratorInterface;
use ApiPlatform\Metadata\Util\ClassInfoTrait;
use ApiPlatform\Serializer\AbstractItemNormalizer;
use ApiPlatform\Serializer\CacheKeyTrait;
use ApiPlatform\Serializer\ContextTrait;
use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Mapping\AttributeMetadataInterface;

/**
 * Converts between objects and array including HAL metadata.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ItemNormalizer extends AbstractItemNormalizer
{
    use CacheKeyTrait;
    use ClassInfoTrait;
    use ContextTrait;

    public const FORMAT = 'jsonhal';

    private array $componentsCache = [];
    private array $attributesMetadataCache = [];

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return self::FORMAT === $format && parent::supportsNormalization($data, $format, $context);
    }

    public function getSupportedTypes($format): array
    {
        return self::FORMAT === $format ? parent::getSupportedTypes($format) : [];
    }

    /**
     * {@inheritdoc}
     */
    public function normalize(mixed $object, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        $resourceClass = $this->getObjectClass($object);
        if ($this->getOutputClass($context)) {
            return parent::normalize($object, $format, $context);
        }

        $previousResourceClass = $context['resource_class'] ?? null;
        if ($this->resourceClassResolver->isResourceClass($resourceClass) && (null === $previousResourceClass || $this->resourceClassResolver->isResourceClass($previousResourceClass))) {
            $resourceClass = $this->resourceClassResolver->getResourceClass($object, $previousResourceClass);
        }

        $context = $this->initContext($resourceClass, $context);
        $iri = $this->iriConverter->getIriFromResource($object, UrlGeneratorInterface::ABS_PATH, $context['operation'] ?? null, $context);

        $context['iri'] = $iri;
        $context['object'] = $object;
        $context['format'] = $format;
        $context['api_normalize'] = true;

        if (!isset($context['cache_key'])) {
            $context['cache_key'] = $this->getCacheKey($format, $context);
        }

        $data = parent::normalize($object, $format, $context);

        if (!\is_array($data)) {
            return $data;
        }

        $metadata = [
            '_links' => [
                'self' => [
                    'href' => $iri,
                ],
            ],
        ];
        $components = $this->getComponents($object, $format, $context);
        $metadata = $this->populateRelation($metadata, $object, $format, $context, $components, 'links');
        $metadata = $this->populateRelation($metadata, $object, $format, $context, $components, 'embedded');

        return $metadata + $data;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        // prevent the use of lower priority normalizers (e.g. serializer.normalizer.object) for this format
        return self::FORMAT === $format;
    }

    /**
     * {@inheritdoc}
     *
     * @throws LogicException
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): never
    {
        throw new LogicException(sprintf('%s is a read-only format.', self::FORMAT));
    }

    /**
     * {@inheritdoc}
     */
    protected function getAttributes($object, $format = null, array $context = []): array
    {
        return $this->getComponents($object, $format, $context)['states'];
    }

    /**
     * Gets HAL components of the resource: states, links and embedded.
     */
    private function getComponents(object $object, ?string $format, array $context): array
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

            $types = $propertyMetadata->getBuiltinTypes() ?? [];

            // prevent declaring $attribute as attribute if it's already declared as relationship
            $isRelationship = false;

            foreach ($types as $type) {
                $isOne = $isMany = false;

                if (null !== $type) {
                    if ($type->isCollection()) {
                        $valueType = $type->getCollectionValueTypes()[0] ?? null;
                        $isMany = null !== $valueType && ($className = $valueType->getClassName()) && $this->resourceClassResolver->isResourceClass($className);
                    } else {
                        $className = $type->getClassName();
                        $isOne = $className && $this->resourceClassResolver->isResourceClass($className);
                    }
                }

                if (!$isOne && !$isMany) {
                    // don't declare it as an attribute too quick: maybe the next type is a valid resource
                    continue;
                }

                $relation = ['name' => $attribute, 'cardinality' => $isOne ? 'one' : 'many', 'iri' => null, 'operation' => null];

                // if we specify the uriTemplate, generates its value for link definition
                // @see ApiPlatform\Serializer\AbstractItemNormalizer:getAttributeValue logic for intentional duplicate content
                if (($className ?? false) && $uriTemplate = $propertyMetadata->getUriTemplate()) {
                    $childContext = $this->createChildContext($context, $attribute, $format);
                    unset($childContext['iri'], $childContext['uri_variables'], $childContext['resource_class'], $childContext['operation'], $childContext['operation_name']);

                    $operation = $this->resourceMetadataCollectionFactory->create($className)->getOperation(
                        operationName: $uriTemplate,
                        httpOperation: true
                    );

                    $relation['iri'] = $this->iriConverter->getIriFromResource($object, UrlGeneratorInterface::ABS_PATH, $operation, $childContext);
                    $relation['operation'] = $operation;
                    $cacheKey = null;
                }

                if ($propertyMetadata->isReadableLink()) {
                    $components['embedded'][] = $relation;
                }

                $components['links'][] = $relation;
                $isRelationship = true;
            }

            // if all types are not relationships, declare it as an attribute
            if (!$isRelationship) {
                $components['states'][] = $attribute;
            }
        }

        if ($cacheKey && false !== $context['cache_key']) {
            $this->componentsCache[$cacheKey] = $components;
        }

        return $components;
    }

    /**
     * Populates _links and _embedded keys.
     */
    private function populateRelation(array $data, object $object, ?string $format, array $context, array $components, string $type): array
    {
        $class = $this->getObjectClass($object);

        $attributesMetadata = \array_key_exists($class, $this->attributesMetadataCache) ?
            $this->attributesMetadataCache[$class] :
            $this->attributesMetadataCache[$class] = $this->classMetadataFactory ? $this->classMetadataFactory->getMetadataFor($class)->getAttributesMetadata() : null;

        $key = '_'.$type;
        foreach ($components[$type] as $relation) {
            if (null !== $attributesMetadata && $this->isMaxDepthReached($attributesMetadata, $class, $relation['name'], $context)) {
                continue;
            }

            $relationName = $relation['name'];
            if ($this->nameConverter) {
                $relationName = $this->nameConverter->normalize($relationName, $class, $format, $context);
            }

            // if we specify the uriTemplate, then the link takes the uriTemplate defined.
            if ('links' === $type && $iri = $relation['iri']) {
                $data[$key][$relationName]['href'] = $iri;
                continue;
            }

            $childContext = $this->createChildContext($context, $relationName, $format);
            unset($childContext['iri'], $childContext['uri_variables'], $childContext['operation'], $childContext['operation_name']);

            if ($operation = $relation['operation']) {
                $childContext['operation'] = $operation;
                $childContext['operation_name'] = $operation->getName();
            }

            $attributeValue = $this->getAttributeValue($object, $relation['name'], $format, $childContext);

            if (empty($attributeValue)) {
                continue;
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
     * @throws UnexpectedValueException
     */
    private function getRelationIri(mixed $rel): string
    {
        if (!(\is_array($rel) || \is_string($rel))) {
            throw new UnexpectedValueException('Expected relation to be an IRI or array');
        }

        return \is_string($rel) ? $rel : $rel['_links']['self']['href'];
    }

    /**
     * Is the max depth reached for the given attribute?
     *
     * @param AttributeMetadataInterface[] $attributesMetadata
     */
    private function isMaxDepthReached(array $attributesMetadata, string $class, string $attribute, array &$context): bool
    {
        if (
            !($context[self::ENABLE_MAX_DEPTH] ?? false)
            || !isset($attributesMetadata[$attribute])
            || null === $maxDepth = $attributesMetadata[$attribute]->getMaxDepth()
        ) {
            return false;
        }

        $key = sprintf(self::DEPTH_KEY_PATTERN, $class, $attribute);
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
