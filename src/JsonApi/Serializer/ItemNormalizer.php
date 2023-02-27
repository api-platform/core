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

namespace ApiPlatform\JsonApi\Serializer;

use ApiPlatform\Api\ResourceClassResolverInterface;
use ApiPlatform\Api\UrlGeneratorInterface;
use ApiPlatform\Core\Api\IriConverterInterface as LegacyIriConverterInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Exception\ItemNotFoundException;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Serializer\AbstractItemNormalizer;
use ApiPlatform\Serializer\CacheKeyTrait;
use ApiPlatform\Serializer\ContextTrait;
use ApiPlatform\Symfony\Security\ResourceAccessCheckerInterface;
use ApiPlatform\Util\ClassInfoTrait;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Exception\RuntimeException;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
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
    use CacheKeyTrait;
    use ClassInfoTrait;
    use ContextTrait;

    public const FORMAT = 'jsonapi';

    private $componentsCache = [];

    public function __construct(PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, $propertyMetadataFactory, $iriConverter, ResourceClassResolverInterface $resourceClassResolver, ?PropertyAccessorInterface $propertyAccessor, ?NameConverterInterface $nameConverter, $resourceMetadataFactory, array $defaultContext = [], iterable $dataTransformers = [], ResourceAccessCheckerInterface $resourceAccessChecker = null)
    {
        parent::__construct($propertyNameCollectionFactory, $propertyMetadataFactory, $iriConverter, $resourceClassResolver, $propertyAccessor, $nameConverter, null, null, false, $defaultContext, $dataTransformers, $resourceMetadataFactory, $resourceAccessChecker);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null, array $context = []): bool
    {
        return self::FORMAT === $format && parent::supportsNormalization($data, $format, $context);
    }

    /**
     * {@inheritdoc}
     *
     * @return array|string|int|float|bool|\ArrayObject|null
     */
    public function normalize($object, $format = null, array $context = [])
    {
        $resourceClass = $this->getObjectClass($object);
        if ($this->getOutputClass($resourceClass, $context)) {
            return parent::normalize($object, $format, $context);
        }

        if ($this->resourceClassResolver->isResourceClass($resourceClass)) {
            $resourceClass = $this->resourceClassResolver->getResourceClass($object, $context['resource_class'] ?? null);
        }

        $context = $this->initContext($resourceClass, $context);
        $iri = $this->iriConverter instanceof LegacyIriConverterInterface ? $this->iriConverter->getIriFromItem($object) : $this->iriConverter->getIriFromResource($object, UrlGeneratorInterface::ABS_PATH, $context['operation'] ?? null, $context);
        $context['iri'] = $iri;
        $context['api_normalize'] = true;

        if (!isset($context['cache_key'])) {
            $context['cache_key'] = $this->getCacheKey($format, $context);
        }

        $data = parent::normalize($object, $format, $context);
        if (!\is_array($data)) {
            return $data;
        }

        // Get and populate relations
        $allRelationshipsData = $this->getComponents($object, $format, $context)['relationships'];
        $populatedRelationContext = $context;
        $relationshipsData = $this->getPopulatedRelations($object, $format, $populatedRelationContext, $allRelationshipsData);

        // Do not include primary resources
        $context['api_included_resources'] = [$context['iri']];

        $includedResourcesData = $this->getRelatedResources($object, $format, $context, $allRelationshipsData);

        $resourceData = [
            'id' => $context['iri'],
            'type' => $this->getResourceShortName($resourceClass),
        ];

        if ($data) {
            $resourceData['attributes'] = $data;
        }

        if ($relationshipsData) {
            $resourceData['relationships'] = $relationshipsData;
        }

        $document = ['data' => $resourceData];

        if ($includedResourcesData) {
            $document['included'] = $includedResourcesData;
        }

        return $document;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null, array $context = []): bool
    {
        return self::FORMAT === $format && parent::supportsDenormalization($data, $type, $format, $context);
    }

    /**
     * {@inheritdoc}
     *
     * @throws NotNormalizableValueException
     *
     * @return mixed
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        // Avoid issues with proxies if we populated the object
        if (!isset($context[self::OBJECT_TO_POPULATE]) && isset($data['data']['id'])) {
            if (true !== ($context['api_allow_update'] ?? true)) {
                throw new NotNormalizableValueException('Update is not allowed for this operation.');
            }

            $context[self::OBJECT_TO_POPULATE] = $this->iriConverter instanceof LegacyIriConverterInterface ? $this->iriConverter->getItemFromIri(
                $data['data']['id'],
                $context + ['fetch_data' => false]
            ) : $this->iriConverter->getResourceFromIri(
                $data['data']['id'],
                $context + ['fetch_data' => false]
            );
        }

        // Merge attributes and relationships, into format expected by the parent normalizer
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
    protected function getAttributes($object, $format = null, array $context = []): array
    {
        return $this->getComponents($object, $format, $context)['attributes'];
    }

    /**
     * {@inheritdoc}
     */
    protected function setAttributeValue($object, $attribute, $value, $format = null, array $context = []): void
    {
        parent::setAttributeValue($object, $attribute, \is_array($value) && \array_key_exists('data', $value) ? $value['data'] : $value, $format, $context);
    }

    /**
     * {@inheritdoc}
     *
     * @see http://jsonapi.org/format/#document-resource-object-linkage
     *
     * @param ApiProperty|PropertyMetadata $propertyMetadata
     *
     * @throws RuntimeException
     * @throws NotNormalizableValueException
     */
    protected function denormalizeRelation(string $attributeName, $propertyMetadata, string $className, $value, ?string $format, array $context)
    {
        if (!\is_array($value) || !isset($value['id'], $value['type'])) {
            throw new NotNormalizableValueException('Only resource linkage supported currently, see: http://jsonapi.org/format/#document-resource-object-linkage.');
        }

        try {
            return $this->iriConverter instanceof LegacyIriConverterInterface ? $this->iriConverter->getItemFromIri($value['id'], $context + ['fetch_data' => true]) : $this->iriConverter->getResourceFromIri($value['id'], $context + ['fetch_data' => true]);
        } catch (ItemNotFoundException $e) {
            throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @param ApiProperty|PropertyMetadata $propertyMetadata
     *
     * @see http://jsonapi.org/format/#document-resource-object-linkage
     */
    protected function normalizeRelation($propertyMetadata, $relatedObject, string $resourceClass, ?string $format, array $context)
    {
        if (null !== $relatedObject) {
            $iri = $this->iriConverter instanceof LegacyIriConverterInterface ? $this->iriConverter->getIriFromItem($relatedObject) : $this->iriConverter->getIriFromResource($relatedObject);
            $context['iri'] = $iri;

            if (isset($context['resources'])) {
                $context['resources'][$iri] = $iri;
            }
        }

        if (null === $relatedObject || isset($context['api_included'])) {
            if (!$this->serializer instanceof NormalizerInterface) {
                throw new LogicException(sprintf('The injected serializer must be an instance of "%s".', NormalizerInterface::class));
            }

            $normalizedRelatedObject = $this->serializer->normalize($relatedObject, $format, $context);
            if (!\is_string($normalizedRelatedObject) && !\is_array($normalizedRelatedObject) && !$normalizedRelatedObject instanceof \ArrayObject && null !== $normalizedRelatedObject) {
                throw new UnexpectedValueException('Expected normalized relation to be an IRI, array, \ArrayObject or null');
            }

            return $normalizedRelatedObject;
        }

        return [
            'data' => [
                'type' => $this->getResourceShortName($resourceClass),
                'id' => $iri,
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function isAllowedAttribute($classOrObject, $attribute, $format = null, array $context = []): bool
    {
        return preg_match('/^\\w[-\\w_]*$/', $attribute) && parent::isAllowedAttribute($classOrObject, $attribute, $format, $context);
    }

    /**
     * Gets JSON API components of the resource: attributes, relationships, meta and links.
     *
     * @param object $object
     */
    private function getComponents($object, ?string $format, array $context): array
    {
        $cacheKey = $this->getObjectClass($object).'-'.$context['cache_key'];

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
            /** @var ApiProperty|PropertyMetadata */
            $propertyMetadata = $this
                ->propertyMetadataFactory
                ->create($context['resource_class'], $attribute, $options);

            // TODO: 3.0 support multiple types, default value of types will be [] instead of null
            $type = $propertyMetadata instanceof PropertyMetadata ? $propertyMetadata->getType() : ($propertyMetadata->getBuiltinTypes()[0] ?? null);
            $isOne = $isMany = false;

            if (null !== $type) {
                if ($type->isCollection()) {
                    $collectionValueType = method_exists(Type::class, 'getCollectionValueTypes') ? ($type->getCollectionValueTypes()[0] ?? null) : $type->getCollectionValueType();
                    $isMany = ($collectionValueType && $className = $collectionValueType->getClassName()) ? $this->resourceClassResolver->isResourceClass($className) : false;
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
                'type' => $this->getResourceShortName($className),
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
     * @throws UnexpectedValueException
     */
    private function getPopulatedRelations($object, ?string $format, array $context, array $relationships): array
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
                $relationshipName = $this->nameConverter->normalize($relationshipName, $context['resource_class'], self::FORMAT, $context);
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
                    throw new UnexpectedValueException(sprintf('The JSON API attribute \'%s\' must contain a "data" key.', $relationshipName));
                }
                unset($attributeValueElement['data']['attributes']);
                $data[$relationshipName]['data'][] = $attributeValueElement['data'];
            }
        }

        return $data;
    }

    /**
     * Populates included keys.
     *
     * @param mixed $object
     */
    private function getRelatedResources($object, ?string $format, array $context, array $relationships): array
    {
        if (!isset($context['api_included'])) {
            return [];
        }

        $included = [];
        foreach ($relationships as $relationshipDataArray) {
            $relationshipName = $relationshipDataArray['name'];

            if (!$this->shouldIncludeRelation($relationshipName, $context)) {
                continue;
            }

            $relationContext = $context;
            $relationContext['api_included'] = $this->getIncludedNestedResources($relationshipName, $context);

            $attributeValue = $this->getAttributeValue($object, $relationshipName, $format, $relationContext);

            if (!$attributeValue) {
                continue;
            }

            // Many to many relationship
            $attributeValues = $attributeValue;
            // Many to one relationship
            if ('one' === $relationshipDataArray['cardinality']) {
                $attributeValues = [$attributeValue];
            }

            foreach ($attributeValues as $attributeValueElement) {
                if (isset($attributeValueElement['data'])) {
                    $this->addIncluded($attributeValueElement['data'], $included, $context);
                    if (isset($attributeValueElement['included']) && \is_array($attributeValueElement['included'])) {
                        foreach ($attributeValueElement['included'] as $include) {
                            $this->addIncluded($include, $included, $context);
                        }
                    }
                }
            }
        }

        return $included;
    }

    /**
     * Add data to included array if it's not already included.
     */
    private function addIncluded(array $data, array &$included, array &$context): void
    {
        if (isset($data['id']) && !\in_array($data['id'], $context['api_included_resources'], true)) {
            $included[] = $data;
            // Track already included resources
            $context['api_included_resources'][] = $data['id'];
        }
    }

    /**
     * Figures out if the relationship is in the api_included hash or has included nested resources (path).
     */
    private function shouldIncludeRelation(string $relationshipName, array $context): bool
    {
        $normalizedName = $this->nameConverter ? $this->nameConverter->normalize($relationshipName, $context['resource_class'], self::FORMAT, $context) : $relationshipName;

        return \in_array($normalizedName, $context['api_included'], true) || \count($this->getIncludedNestedResources($relationshipName, $context)) > 0;
    }

    /**
     * Returns the names of the nested resources from a path relationship.
     */
    private function getIncludedNestedResources(string $relationshipName, array $context): array
    {
        $normalizedName = $this->nameConverter ? $this->nameConverter->normalize($relationshipName, $context['resource_class'], self::FORMAT, $context) : $relationshipName;

        $filtered = array_filter($context['api_included'] ?? [], static function (string $included) use ($normalizedName) {
            return 0 === strpos($included, $normalizedName.'.');
        });

        return array_map(static function (string $nested) {
            return substr($nested, strpos($nested, '.') + 1);
        }, $filtered);
    }

    // TODO: 3.0 remove
    private function getResourceShortName(string $resourceClass): string
    {
        /** @var ResourceMetadata|ResourceMetadataCollection */
        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);

        if ($resourceMetadata instanceof ResourceMetadata) {
            return $resourceMetadata->getShortName();
        }

        return $resourceMetadata->getOperation()->getShortName();
    }
}

class_alias(ItemNormalizer::class, \ApiPlatform\Core\JsonApi\Serializer\ItemNormalizer::class);
