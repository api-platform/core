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

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\IdentifiersExtractorInterface;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\ResourceAccessCheckerInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use ApiPlatform\Metadata\Util\ClassInfoTrait;
use ApiPlatform\Metadata\Util\CompositeIdentifierParser;
use ApiPlatform\Metadata\Util\TypeHelper;
use ApiPlatform\Serializer\AbstractItemNormalizer;
use ApiPlatform\Serializer\CacheKeyTrait;
use ApiPlatform\Serializer\ContextTrait;
use ApiPlatform\Serializer\OperationResourceClassResolverInterface;
use ApiPlatform\Serializer\TagCollectorInterface;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\CompositeTypeInterface;
use Symfony\Component\TypeInfo\Type\ObjectType;

/**
 * Converts objects to JSON:API documents (normalization only).
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
    use ItemNormalizerTrait {
        denormalize as private doDenormalize;
    }

    public const FORMAT = 'jsonapi';

    private array $componentsCache = [];

    public function __construct(
        PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory,
        PropertyMetadataFactoryInterface $propertyMetadataFactory,
        IriConverterInterface $iriConverter,
        ResourceClassResolverInterface $resourceClassResolver,
        ?PropertyAccessorInterface $propertyAccessor = null,
        ?NameConverterInterface $nameConverter = null,
        ?ClassMetadataFactoryInterface $classMetadataFactory = null,
        array $defaultContext = [],
        ?ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory = null,
        ?ResourceAccessCheckerInterface $resourceAccessChecker = null,
        protected ?TagCollectorInterface $tagCollector = null,
        ?OperationResourceClassResolverInterface $operationResourceResolver = null,
        private readonly ?IdentifiersExtractorInterface $identifiersExtractor = null,
        private readonly bool $useIriAsId = true,
    ) {
        parent::__construct($propertyNameCollectionFactory, $propertyMetadataFactory, $iriConverter, $resourceClassResolver, $propertyAccessor, $nameConverter, $classMetadataFactory, $defaultContext, $resourceMetadataCollectionFactory, $resourceAccessChecker, $tagCollector, $operationResourceResolver);
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return self::FORMAT === $format && parent::supportsNormalization($data, $format, $context) && !($data instanceof \Exception || $data instanceof FlattenException);
    }

    public function getSupportedTypes(?string $format): array
    {
        return self::FORMAT === $format ? parent::getSupportedTypes($format) : [];
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        trigger_deprecation('api-platform/core', '4.4', 'Calling "denormalize()" on "%s" is deprecated, use "%s" instead.', self::class, ItemDenormalizer::class);

        return $this->doDenormalize($data, $type, $format, $context);
    }

    public function normalize(mixed $data, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        $resourceClass = $this->getObjectClass($data);
        if ($this->getOutputClass($context)) {
            return parent::normalize($data, $format, $context);
        }

        $previousResourceClass = $context['resource_class'] ?? null;
        if ($this->resourceClassResolver->isResourceClass($resourceClass) && (null === $previousResourceClass || $this->resourceClassResolver->isResourceClass($previousResourceClass))) {
            $resourceClass = $this->resourceClassResolver->getResourceClass($data, $previousResourceClass);
        }

        if (($operation = $context['operation'] ?? null) && method_exists($operation, 'getItemUriTemplate')) {
            $context['item_uri_template'] = $operation->getItemUriTemplate();
        }

        $context = $this->initContext($resourceClass, $context);

        $iri = $context['iri'] ??= $this->iriConverter->getIriFromResource($data, UrlGeneratorInterface::ABS_PATH, $context['operation'] ?? null, $context);
        $context['object'] = $data;
        $context['format'] = $format;
        $context['api_normalize'] = true;

        if (!isset($context['cache_key'])) {
            $context['cache_key'] = $this->getCacheKey($format, $context);
        }

        $normalizedData = parent::normalize($data, $format, $context);
        if (!\is_array($normalizedData)) {
            return $normalizedData;
        }

        ['relationships' => $allRelationshipsData, 'links' => $links] = $this->getComponents($data, $format, $context);
        $populatedRelationContext = $context;
        $relationshipsData = $this->getPopulatedRelations($data, $format, $populatedRelationContext, $allRelationshipsData);

        $id = $iri;
        if (!$this->useIriAsId) {
            $identifiers = $this->identifiersExtractor->getIdentifiersFromItem($data, context: $context);
            $id = $this->getIdStringFromIdentifiers($identifiers);
        }

        $resourceShortName = $this->getResourceShortName($resourceClass);

        // Do not include primary resources — use type:id composite key to avoid cross-type collisions
        $context['api_included_resources'] = [$resourceShortName.':'.$id => true];

        $includedResourcesData = $this->getRelatedResources($data, $format, $context, $allRelationshipsData);

        $resourceData = [
            'id' => $id,
            'type' => $resourceShortName,
        ];

        if (!$this->useIriAsId) {
            $resourceData['links'] = ['self' => $iri];
        }

        if ($normalizedData) {
            $resourceData['attributes'] = $normalizedData;
        }

        if ($relationshipsData) {
            $resourceData['relationships'] = $relationshipsData;
        }

        $document = [];

        if ($links) {
            $document['links'] = $links;
        }

        $document['data'] = $resourceData;

        if ($includedResourcesData) {
            $document['included'] = $includedResourcesData;
        }

        return $document;
    }

    /**
     * {@inheritdoc}
     */
    protected function getAttributes(object $object, ?string $format = null, array $context = []): array
    {
        return $this->getComponents($object, $format, $context)['attributes'];
    }

    /**
     * @see http://jsonapi.org/format/#document-resource-object-linkage
     */
    protected function normalizeRelation(ApiProperty $propertyMetadata, ?object $relatedObject, string $resourceClass, ?string $format, array $context): \ArrayObject|array|string|null
    {
        if (null !== $relatedObject) {
            $iri = $this->iriConverter->getIriFromResource($relatedObject);
            $context['iri'] = $iri;

            if (!$this->tagCollector && isset($context['resources'])) {
                $context['resources'][$iri] = $iri;
            }
        }

        if (null === $relatedObject || isset($context['api_included'])) {
            if (!$this->serializer instanceof NormalizerInterface) {
                throw new LogicException(\sprintf('The injected serializer must be an instance of "%s".', NormalizerInterface::class));
            }

            $normalizedRelatedObject = $this->serializer->normalize($relatedObject, $format, $context);
            if (!\is_string($normalizedRelatedObject) && !\is_array($normalizedRelatedObject) && !$normalizedRelatedObject instanceof \ArrayObject && null !== $normalizedRelatedObject) {
                throw new UnexpectedValueException('Expected normalized relation to be an IRI, array, \ArrayObject or null');
            }

            return $normalizedRelatedObject;
        }

        $id = $iri;
        if (!$this->useIriAsId) {
            $identifiers = $this->identifiersExtractor->getIdentifiersFromItem($relatedObject);
            $id = $this->getIdStringFromIdentifiers($identifiers);
        }

        $context['data'] = [
            'data' => [
                'type' => $this->getResourceShortName($resourceClass),
                'id' => $id,
            ],
        ];

        $context['iri'] = $iri;
        $context['object'] = $relatedObject;
        unset($context['property_metadata']);
        unset($context['api_attribute']);

        if ($this->tagCollector) {
            $this->tagCollector->collect($context);
        }

        return $context['data'];
    }

    /**
     * Gets JSON API components of the resource: attributes, relationships, meta and links.
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
            'links' => [],
            'relationships' => [],
            'attributes' => [],
            'meta' => [],
        ];

        foreach ($attributes as $attribute) {
            $propertyMetadata = $this
                ->propertyMetadataFactory
                ->create($context['resource_class'], $attribute, $options);

            $isRelationship = false;

            if (!method_exists(PropertyInfoExtractor::class, 'getType')) {
                $types = $propertyMetadata->getBuiltinTypes() ?? [];

                foreach ($types as $type) {
                    $isOne = $isMany = false;

                    if ($type->isCollection()) {
                        $collectionValueType = $type->getCollectionValueTypes()[0] ?? null;
                        $isMany = $collectionValueType && ($className = $collectionValueType->getClassName()) && $this->resourceClassResolver->isResourceClass($className);
                    } else {
                        $isOne = ($className = $type->getClassName()) && $this->resourceClassResolver->isResourceClass($className);
                    }

                    if (!isset($className) || !$isOne && !$isMany) {
                        continue;
                    }

                    $relation = [
                        'name' => $attribute,
                        'type' => $this->getResourceShortName($className),
                        'cardinality' => $isOne ? 'one' : 'many',
                    ];

                    if ($itemUriTemplate = $propertyMetadata->getUriTemplate()) {
                        $attributeValue = $this->propertyAccessor->getValue($object, $attribute);
                        $resourceClass = $this->resourceClassResolver->getResourceClass($attributeValue, $className);
                        $childContext = $this->createChildContext($context, $attribute, $format);
                        unset($childContext['iri'], $childContext['uri_variables'], $childContext['resource_class'], $childContext['operation']);

                        $operation = $this->resourceMetadataCollectionFactory->create($resourceClass)->getOperation(
                            operationName: $itemUriTemplate,
                            httpOperation: true
                        );

                        $components['links'][$attribute] = $this->iriConverter->getIriFromResource($object, UrlGeneratorInterface::ABS_PATH, $operation, $childContext);
                    }

                    $components['relationships'][] = $relation;
                    $isRelationship = true;
                }
            } else {
                if ($type = $propertyMetadata->getNativeType()) {
                    /** @var class-string|null $className */
                    $className = null;

                    $typeIsResourceClass = function (Type $type) use (&$className): bool {
                        return $type instanceof ObjectType && $this->resourceClassResolver->isResourceClass($className = $type->getClassName());
                    };

                    foreach ($type instanceof CompositeTypeInterface ? $type->getTypes() : [$type] as $t) {
                        $isOne = $isMany = false;

                        if (TypeHelper::getCollectionValueType($t)?->isSatisfiedBy($typeIsResourceClass)) {
                            $isMany = true;
                        } elseif ($t->isSatisfiedBy($typeIsResourceClass)) {
                            $isOne = true;
                        }

                        if (!$className || (!$isOne && !$isMany)) {
                            continue;
                        }

                        $relation = [
                            'name' => $attribute,
                            'type' => $this->getResourceShortName($className),
                            'cardinality' => $isOne ? 'one' : 'many',
                        ];

                        if ($itemUriTemplate = $propertyMetadata->getUriTemplate()) {
                            $attributeValue = $this->propertyAccessor->getValue($object, $attribute);
                            $resourceClass = $this->resourceClassResolver->getResourceClass($attributeValue, $className);
                            $childContext = $this->createChildContext($context, $attribute, $format);
                            unset($childContext['iri'], $childContext['uri_variables'], $childContext['resource_class'], $childContext['operation']);

                            $operation = $this->resourceMetadataCollectionFactory->create($resourceClass)->getOperation(
                                operationName: $itemUriTemplate,
                                httpOperation: true
                            );

                            $components['links'][$attribute] = $this->iriConverter->getIriFromResource($object, UrlGeneratorInterface::ABS_PATH, $operation, $childContext);
                        }

                        $components['relationships'][] = $relation;
                        $isRelationship = true;
                    }
                }
            }

            if (!$isRelationship) {
                $components['attributes'][] = $attribute;
            }
        }

        if (false !== $context['cache_key']) {
            $this->componentsCache[$cacheKey] = $components;
        }

        return $components;
    }

    /**
     * @throws UnexpectedValueException
     */
    private function getPopulatedRelations(object $object, ?string $format, array $context, array $relationships): array
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

            if ('one' === $relationshipDataArray['cardinality']) {
                $data[$relationshipName] = ['data' => null];

                if (!$attributeValue) {
                    continue;
                }

                unset($attributeValue['data']['attributes']);
                $data[$relationshipName] = $attributeValue;

                continue;
            }

            $data[$relationshipName] = ['data' => []];

            if (!$attributeValue) {
                continue;
            }

            foreach ($attributeValue as $attributeValueElement) {
                if (!isset($attributeValueElement['data'])) {
                    throw new UnexpectedValueException(\sprintf('The JSON API attribute \'%s\' must contain a "data" key.', $relationshipName));
                }
                unset($attributeValueElement['data']['attributes']);
                $data[$relationshipName]['data'][] = $attributeValueElement['data'];
            }
        }

        return $data;
    }

    private function getRelatedResources(object $object, ?string $format, array $context, array $relationships): array
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

            $attributeValues = $attributeValue;
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

    private function addIncluded(array $data, array &$included, array &$context): void
    {
        $trackingKey = ($data['type'] ?? '').':'.($data['id'] ?? '');
        if (isset($data['id']) && !isset($context['api_included_resources'][$trackingKey])) {
            $included[] = $data;
            $context['api_included_resources'][$trackingKey] = true;
        }
    }

    private function shouldIncludeRelation(string $relationshipName, array $context): bool
    {
        $normalizedName = $this->nameConverter ? $this->nameConverter->normalize($relationshipName, $context['resource_class'], self::FORMAT, $context) : $relationshipName;

        return \in_array($normalizedName, $context['api_included'], true) || \count($this->getIncludedNestedResources($relationshipName, $context)) > 0;
    }

    private function getIncludedNestedResources(string $relationshipName, array $context): array
    {
        $normalizedName = $this->nameConverter ? $this->nameConverter->normalize($relationshipName, $context['resource_class'], self::FORMAT, $context) : $relationshipName;

        $filtered = array_filter($context['api_included'] ?? [], static fn (string $included): bool => str_starts_with($included, $normalizedName.'.'));

        return array_map(static fn (string $nested): string => substr($nested, strpos($nested, '.') + 1), $filtered);
    }

    private function getIdStringFromIdentifiers(array $identifiers): string
    {
        if (1 === \count($identifiers)) {
            return (string) array_values($identifiers)[0];
        }

        return CompositeIdentifierParser::stringify($identifiers);
    }

    private function getResourceShortName(string $resourceClass): string
    {
        if ($this->resourceClassResolver->isResourceClass($resourceClass)) {
            $resourceMetadata = $this->resourceMetadataCollectionFactory->create($resourceClass);

            return $resourceMetadata->getOperation()->getShortName();
        }

        return (new \ReflectionClass($resourceClass))->getShortName();
    }
}
