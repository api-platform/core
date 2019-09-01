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

namespace ApiPlatform\Core\Serializer;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Exception\InvalidValueException;
use ApiPlatform\Core\Exception\ItemNotFoundException;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Util\ClassInfoTrait;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Exception\MissingConstructorArgumentsException;
use Symfony\Component\Serializer\Exception\RuntimeException;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\NameConverter\AdvancedNameConverterInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Base item normalizer.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
abstract class AbstractItemNormalizer extends AbstractObjectNormalizer
{
    use ClassInfoTrait;
    use ContextTrait;
    use InputOutputMetadataTrait;

    protected $propertyNameCollectionFactory;
    protected $propertyMetadataFactory;
    protected $iriConverter;
    protected $resourceClassResolver;
    protected $propertyAccessor;
    protected $itemDataProvider;
    protected $allowPlainIdentifiers;
    protected $dataTransformers = [];
    protected $localCache = [];

    public function __construct(PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, IriConverterInterface $iriConverter, ResourceClassResolverInterface $resourceClassResolver, PropertyAccessorInterface $propertyAccessor = null, NameConverterInterface $nameConverter = null, ClassMetadataFactoryInterface $classMetadataFactory = null, ItemDataProviderInterface $itemDataProvider = null, bool $allowPlainIdentifiers = false, array $defaultContext = [], iterable $dataTransformers = [], ResourceMetadataFactoryInterface $resourceMetadataFactory = null)
    {
        if (!isset($defaultContext['circular_reference_handler'])) {
            $defaultContext['circular_reference_handler'] = function ($object) {
                return $this->iriConverter->getIriFromItem($object);
            };
        }
        if (!interface_exists(AdvancedNameConverterInterface::class)) {
            $this->setCircularReferenceHandler($defaultContext['circular_reference_handler']);
        }

        parent::__construct($classMetadataFactory, $nameConverter, null, null, \Closure::fromCallable([$this, 'getObjectClass']), $defaultContext);

        $this->propertyNameCollectionFactory = $propertyNameCollectionFactory;
        $this->propertyMetadataFactory = $propertyMetadataFactory;
        $this->iriConverter = $iriConverter;
        $this->resourceClassResolver = $resourceClassResolver;
        $this->propertyAccessor = $propertyAccessor ?: PropertyAccess::createPropertyAccessor();
        $this->itemDataProvider = $itemDataProvider;
        $this->allowPlainIdentifiers = $allowPlainIdentifiers;
        $this->dataTransformers = $dataTransformers;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        if (!\is_object($data) || $data instanceof \Traversable) {
            return false;
        }

        return $this->resourceClassResolver->isResourceClass($this->getObjectClass($data));
    }

    /**
     * {@inheritdoc}
     */
    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @throws LogicException
     */
    public function normalize($object, $format = null, array $context = [])
    {
        if ($object !== $transformed = $this->transformOutput($object, $context)) {
            if (!$this->serializer instanceof NormalizerInterface) {
                throw new LogicException('Cannot normalize the output because the injected serializer is not a normalizer');
            }

            $context['api_normalize'] = true;
            $context['api_resource'] = $object;
            unset($context['output']);
            unset($context['resource_class']);

            return $this->serializer->normalize($transformed, $format, $context);
        }

        $resourceClass = $this->resourceClassResolver->getResourceClass($object, $context['resource_class'] ?? null);
        $context = $this->initContext($resourceClass, $context);
        $iri = $context['iri'] ?? $this->iriConverter->getIriFromItem($object);
        $context['iri'] = $iri;
        $context['api_normalize'] = true;

        /*
         * When true, converts the normalized data array of a resource into an
         * IRI, if the normalized data array is empty.
         *
         * This is useful when traversing from a non-resource towards an attribute
         * which is a resource, as we do not have the benefit of {@see PropertyMetadata::isReadableLink}.
         *
         * It must not be propagated to subresources, as {@see PropertyMetadata::isReadableLink}
         * should take effect.
         */
        $emptyResourceAsIri = $context['api_empty_resource_as_iri'] ?? false;
        unset($context['api_empty_resource_as_iri']);

        if (isset($context['resources'])) {
            $context['resources'][$iri] = $iri;
        }

        $data = parent::normalize($object, $format, $context);
        if ($emptyResourceAsIri && \is_array($data) && 0 === \count($data)) {
            return $iri;
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return $this->localCache[$type] ?? $this->localCache[$type] = $this->resourceClassResolver->isResourceClass($type);
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        $resourceClass = $this->resourceClassResolver->getResourceClass(null, $class);
        $context['api_denormalize'] = true;
        $context['resource_class'] = $resourceClass;

        if (null !== ($inputClass = $this->getInputClass($resourceClass, $context)) && null !== ($dataTransformer = $this->getDataTransformer($data, $resourceClass, $context))) {
            $dataTransformerContext = $context;

            unset($context['input']);
            unset($context['resource_class']);

            if (!$this->serializer instanceof DenormalizerInterface) {
                throw new LogicException('Cannot denormalize the input because the injected serializer is not a denormalizer');
            }
            $denormalizedInput = $this->serializer->denormalize($data, $inputClass, $format, $context);

            return $dataTransformer->transform($denormalizedInput, $resourceClass, $dataTransformerContext);
        }

        $supportsPlainIdentifiers = $this->supportsPlainIdentifiers();

        if (\is_string($data)) {
            try {
                return $this->iriConverter->getItemFromIri($data, $context + ['fetch_data' => true]);
            } catch (ItemNotFoundException $e) {
                if (!$supportsPlainIdentifiers) {
                    throw new UnexpectedValueException($e->getMessage(), $e->getCode(), $e);
                }
            } catch (InvalidArgumentException $e) {
                if (!$supportsPlainIdentifiers) {
                    throw new UnexpectedValueException(sprintf('Invalid IRI "%s".', $data), $e->getCode(), $e);
                }
            }
        }

        if (!\is_array($data)) {
            if (!$supportsPlainIdentifiers) {
                throw new UnexpectedValueException(sprintf('Expected IRI or document for resource "%s", "%s" given.', $resourceClass, \gettype($data)));
            }

            $item = $this->itemDataProvider->getItem($resourceClass, $data, null, $context + ['fetch_data' => true]);
            if (null === $item) {
                throw new ItemNotFoundException(sprintf('Item not found for resource "%s" with id "%s".', $resourceClass, $data));
            }

            return $item;
        }

        return parent::denormalize($data, $resourceClass, $format, $context);
    }

    /**
     * Method copy-pasted from symfony/serializer.
     * Remove it after symfony/serializer version update @link https://github.com/symfony/symfony/pull/28263.
     *
     * {@inheritdoc}
     *
     * @internal
     */
    protected function instantiateObject(array &$data, $class, array &$context, \ReflectionClass $reflectionClass, $allowedAttributes, string $format = null)
    {
        if (null !== $object = $this->extractObjectToPopulate($class, $context, static::OBJECT_TO_POPULATE)) {
            unset($context[static::OBJECT_TO_POPULATE]);

            return $object;
        }

        if ($this->classDiscriminatorResolver && $mapping = $this->classDiscriminatorResolver->getMappingForClass($class)) {
            if (!isset($data[$mapping->getTypeProperty()])) {
                throw new RuntimeException(sprintf('Type property "%s" not found for the abstract object "%s"', $mapping->getTypeProperty(), $class));
            }

            $type = $data[$mapping->getTypeProperty()];
            if (null === ($mappedClass = $mapping->getClassForType($type))) {
                throw new RuntimeException(sprintf('The type "%s" has no mapped class for the abstract object "%s"', $type, $class));
            }

            $class = $mappedClass;
            $reflectionClass = new \ReflectionClass($class);
        }

        $constructor = $this->getConstructor($data, $class, $context, $reflectionClass, $allowedAttributes);
        if ($constructor) {
            $constructorParameters = $constructor->getParameters();

            $params = [];
            foreach ($constructorParameters as $constructorParameter) {
                $paramName = $constructorParameter->name;
                $key = $this->nameConverter ? $this->nameConverter->normalize($paramName, $class, $format, $context) : $paramName;

                $allowed = false === $allowedAttributes || (\is_array($allowedAttributes) && \in_array($paramName, $allowedAttributes, true));
                $ignored = !$this->isAllowedAttribute($class, $paramName, $format, $context);
                if ($constructorParameter->isVariadic()) {
                    if ($allowed && !$ignored && (isset($data[$key]) || \array_key_exists($key, $data))) {
                        if (!\is_array($data[$paramName])) {
                            throw new RuntimeException(sprintf('Cannot create an instance of %s from serialized data because the variadic parameter %s can only accept an array.', $class, $constructorParameter->name));
                        }

                        $params = array_merge($params, $data[$paramName]);
                    }
                } elseif ($allowed && !$ignored && (isset($data[$key]) || \array_key_exists($key, $data))) {
                    $params[] = $this->createConstructorArgument($data[$key], $key, $constructorParameter, $context, $format);

                    // Don't run set for a parameter passed to the constructor
                    unset($data[$key]);
                } elseif (isset($context[static::DEFAULT_CONSTRUCTOR_ARGUMENTS][$class][$key])) {
                    $params[] = $context[static::DEFAULT_CONSTRUCTOR_ARGUMENTS][$class][$key];
                } elseif ($constructorParameter->isDefaultValueAvailable()) {
                    $params[] = $constructorParameter->getDefaultValue();
                } else {
                    throw new MissingConstructorArgumentsException(
                        sprintf(
                            'Cannot create an instance of %s from serialized data because its constructor requires parameter "%s" to be present.',
                            $class,
                            $constructorParameter->name
                        )
                    );
                }
            }

            if ($constructor->isConstructor()) {
                return $reflectionClass->newInstanceArgs($params);
            }

            return $constructor->invokeArgs(null, $params);
        }

        return new $class();
    }

    /**
     * {@inheritdoc}
     */
    protected function createConstructorArgument($parameterData, string $key, \ReflectionParameter $constructorParameter, array &$context, string $format = null)
    {
        return $this->createAttributeValue($constructorParameter->name, $parameterData, $format, $context);
    }

    /**
     * {@inheritdoc}
     *
     * Unused in this context.
     */
    protected function extractAttributes($object, $format = null, array $context = [])
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    protected function getAllowedAttributes($classOrObject, array $context, $attributesAsString = false)
    {
        $options = $this->getFactoryOptions($context);
        $propertyNames = $this->propertyNameCollectionFactory->create($context['resource_class'], $options);

        $allowedAttributes = [];
        foreach ($propertyNames as $propertyName) {
            $propertyMetadata = $this->propertyMetadataFactory->create($context['resource_class'], $propertyName, $options);

            if (
                $this->isAllowedAttribute($classOrObject, $propertyName, null, $context) &&
                (
                    isset($context['api_normalize']) && $propertyMetadata->isReadable() ||
                    isset($context['api_denormalize']) && ($propertyMetadata->isWritable() || !\is_object($classOrObject) && $propertyMetadata->isInitializable())
                )
            ) {
                $allowedAttributes[] = $propertyName;
            }
        }

        return $allowedAttributes;
    }

    /**
     * {@inheritdoc}
     */
    protected function setAttributeValue($object, $attribute, $value, $format = null, array $context = [])
    {
        $this->setValue($object, $attribute, $this->createAttributeValue($attribute, $value, $format, $context));
    }

    /**
     * Validates the type of the value. Allows using integers as floats for JSON formats.
     *
     * @throws InvalidArgumentException
     */
    protected function validateType(string $attribute, Type $type, $value, string $format = null)
    {
        $builtinType = $type->getBuiltinType();
        if (Type::BUILTIN_TYPE_FLOAT === $builtinType && null !== $format && false !== strpos($format, 'json')) {
            $isValid = \is_float($value) || \is_int($value);
        } else {
            $isValid = \call_user_func('is_'.$builtinType, $value);
        }

        if (!$isValid) {
            throw new InvalidArgumentException(sprintf(
                'The type of the "%s" attribute must be "%s", "%s" given.', $attribute, $builtinType, \gettype($value)
            ));
        }
    }

    /**
     * Denormalizes a collection of objects.
     *
     * @throws InvalidArgumentException
     */
    protected function denormalizeCollection(string $attribute, PropertyMetadata $propertyMetadata, Type $type, string $className, $value, ?string $format, array $context): array
    {
        if (!\is_array($value)) {
            throw new InvalidArgumentException(sprintf(
                'The type of the "%s" attribute must be "array", "%s" given.', $attribute, \gettype($value)
            ));
        }

        $collectionKeyType = $type->getCollectionKeyType();
        $collectionKeyBuiltinType = null === $collectionKeyType ? null : $collectionKeyType->getBuiltinType();

        $values = [];
        foreach ($value as $index => $obj) {
            if (null !== $collectionKeyBuiltinType && !\call_user_func('is_'.$collectionKeyBuiltinType, $index)) {
                throw new InvalidArgumentException(sprintf(
                        'The type of the key "%s" must be "%s", "%s" given.',
                        $index, $collectionKeyBuiltinType, \gettype($index))
                );
            }

            $values[$index] = $this->denormalizeRelation($attribute, $propertyMetadata, $className, $obj, $format, $this->createChildContext($context, $attribute, $format));
        }

        return $values;
    }

    /**
     * Denormalizes a relation.
     *
     * @throws LogicException
     * @throws UnexpectedValueException
     * @throws ItemNotFoundException
     *
     * @return object|null
     */
    protected function denormalizeRelation(string $attributeName, PropertyMetadata $propertyMetadata, string $className, $value, ?string $format, array $context)
    {
        $supportsPlainIdentifiers = $this->supportsPlainIdentifiers();

        if (\is_string($value)) {
            try {
                return $this->iriConverter->getItemFromIri($value, $context + ['fetch_data' => true]);
            } catch (ItemNotFoundException $e) {
                if (!$supportsPlainIdentifiers) {
                    throw new UnexpectedValueException($e->getMessage(), $e->getCode(), $e);
                }
            } catch (InvalidArgumentException $e) {
                if (!$supportsPlainIdentifiers) {
                    throw new UnexpectedValueException(sprintf('Invalid IRI "%s".', $value), $e->getCode(), $e);
                }
            }
        }

        if ($propertyMetadata->isWritableLink()) {
            $context['api_allow_update'] = true;

            if (!$this->serializer instanceof DenormalizerInterface) {
                throw new LogicException(sprintf('The injected serializer must be an instance of "%s".', DenormalizerInterface::class));
            }

            try {
                return $this->serializer->denormalize($value, $className, $format, $context);
            } catch (InvalidValueException $e) {
                if (!$supportsPlainIdentifiers) {
                    throw $e;
                }
            }
        }

        if (!\is_array($value)) {
            if (!$supportsPlainIdentifiers) {
                throw new UnexpectedValueException(sprintf(
                    'Expected IRI or nested document for attribute "%s", "%s" given.', $attributeName, \gettype($value)
                ));
            }

            $item = $this->itemDataProvider->getItem($className, $value, null, $context + ['fetch_data' => true]);
            if (null === $item) {
                throw new ItemNotFoundException(sprintf('Item not found for resource "%s" with id "%s".', $className, $value));
            }

            return $item;
        }

        throw new UnexpectedValueException(sprintf('Nested documents for attribute "%s" are not allowed. Use IRIs instead.', $attributeName));
    }

    /**
     * Gets a valid context for property metadata factories.
     *
     * @see https://github.com/symfony/symfony/blob/master/src/Symfony/Component/PropertyInfo/Extractor/SerializerExtractor.php
     */
    protected function getFactoryOptions(array $context): array
    {
        $options = [];

        if (isset($context[self::GROUPS])) {
            $options['serializer_groups'] = $context[self::GROUPS];
        }

        if (isset($context['collection_operation_name'])) {
            $options['collection_operation_name'] = $context['collection_operation_name'];
        }

        if (isset($context['item_operation_name'])) {
            $options['item_operation_name'] = $context['item_operation_name'];
        }

        return $options;
    }

    /**
     * Creates the context to use when serializing a relation.
     *
     * @deprecated since version 2.1, to be removed in 3.0.
     */
    protected function createRelationSerializationContext(string $resourceClass, array $context): array
    {
        @trigger_error(sprintf('The method %s() is deprecated since 2.1 and will be removed in 3.0.', __METHOD__), E_USER_DEPRECATED);

        return $context;
    }

    /**
     * {@inheritdoc}
     *
     * @throws NoSuchPropertyException
     * @throws LogicException
     */
    protected function getAttributeValue($object, $attribute, $format = null, array $context = [])
    {
        $context['api_attribute'] = $attribute;
        $propertyMetadata = $this->propertyMetadataFactory->create($context['resource_class'], $attribute, $this->getFactoryOptions($context));

        try {
            $attributeValue = $this->propertyAccessor->getValue($object, $attribute);
        } catch (NoSuchPropertyException $e) {
            if (!$propertyMetadata->hasChildInherited()) {
                throw $e;
            }

            $attributeValue = null;
        }

        $type = $propertyMetadata->getType();

        if (
            is_iterable($attributeValue) &&
            $type &&
            $type->isCollection() &&
            ($collectionValueType = $type->getCollectionValueType()) &&
            ($className = $collectionValueType->getClassName()) &&
            $this->resourceClassResolver->isResourceClass($className)
        ) {
            $resourceClass = $this->resourceClassResolver->getResourceClass($attributeValue, $className);
            $childContext = $this->createChildContext($context, $attribute, $format);
            $childContext['resource_class'] = $resourceClass;
            unset($childContext['iri']);

            return $this->normalizeCollectionOfRelations($propertyMetadata, $attributeValue, $resourceClass, $format, $childContext);
        }

        if (
            $type &&
            ($className = $type->getClassName()) &&
            $this->resourceClassResolver->isResourceClass($className)
        ) {
            $resourceClass = $this->resourceClassResolver->getResourceClass($attributeValue, $className);
            $childContext = $this->createChildContext($context, $attribute, $format);
            $childContext['resource_class'] = $resourceClass;
            unset($childContext['iri']);

            return $this->normalizeRelation($propertyMetadata, $attributeValue, $resourceClass, $format, $childContext);
        }

        if (!$this->serializer instanceof NormalizerInterface) {
            throw new LogicException(sprintf('The injected serializer must be an instance of "%s".', NormalizerInterface::class));
        }

        unset($context['resource_class']);

        return $this->serializer->normalize($attributeValue, $format, $context);
    }

    /**
     * Normalizes a collection of relations (to-many).
     *
     * @param iterable $attributeValue
     */
    protected function normalizeCollectionOfRelations(PropertyMetadata $propertyMetadata, $attributeValue, string $resourceClass, ?string $format, array $context): array
    {
        $value = [];
        foreach ($attributeValue as $index => $obj) {
            $value[$index] = $this->normalizeRelation($propertyMetadata, $obj, $resourceClass, $format, $context);
        }

        return $value;
    }

    /**
     * Normalizes a relation as an object if is a Link or as an URI.
     *
     * @throws LogicException
     *
     * @return string|array
     */
    protected function normalizeRelation(PropertyMetadata $propertyMetadata, $relatedObject, string $resourceClass, ?string $format, array $context)
    {
        if (null === $relatedObject || !empty($context['attributes']) || $propertyMetadata->isReadableLink()) {
            if (!$this->serializer instanceof NormalizerInterface) {
                throw new LogicException(sprintf('The injected serializer must be an instance of "%s".', NormalizerInterface::class));
            }

            return $this->serializer->normalize($relatedObject, $format, $context);
        }

        $iri = $this->iriConverter->getIriFromItem($relatedObject);
        if (isset($context['resources'])) {
            $context['resources'][$iri] = $iri;
        }
        if (isset($context['resources_to_push']) && $propertyMetadata->getAttribute('push', false)) {
            $context['resources_to_push'][$iri] = $iri;
        }

        return $iri;
    }

    /**
     * Finds the first supported data transformer if any.
     *
     * @param object|array $data object on normalize / array on denormalize
     */
    protected function getDataTransformer($data, string $to, array $context = []): ?DataTransformerInterface
    {
        foreach ($this->dataTransformers as $dataTransformer) {
            if ($dataTransformer->supportsTransformation($data, $to, $context)) {
                return $dataTransformer;
            }
        }

        return null;
    }

    /**
     * For a given resource, it returns an output representation if any
     * If not, the resource is returned.
     */
    protected function transformOutput($object, array $context = [])
    {
        $outputClass = $this->getOutputClass($this->getObjectClass($object), $context);
        if (null !== $outputClass && null !== $dataTransformer = $this->getDataTransformer($object, $outputClass, $context)) {
            return $dataTransformer->transform($object, $outputClass, $context);
        }

        return $object;
    }

    private function createAttributeValue($attribute, $value, $format = null, array $context = [])
    {
        $propertyMetadata = $this->propertyMetadataFactory->create($context['resource_class'], $attribute, $this->getFactoryOptions($context));
        $type = $propertyMetadata->getType();

        if (null === $type) {
            // No type provided, blindly return the value
            return $value;
        }

        if (null === $value && $type->isNullable()) {
            return $value;
        }

        if (
            $type->isCollection() &&
            null !== ($collectionValueType = $type->getCollectionValueType()) &&
            null !== ($className = $collectionValueType->getClassName()) &&
            $this->resourceClassResolver->isResourceClass($className)
        ) {
            $resourceClass = $this->resourceClassResolver->getResourceClass(null, $className);
            $context['resource_class'] = $resourceClass;

            return $this->denormalizeCollection($attribute, $propertyMetadata, $type, $resourceClass, $value, $format, $context);
        }

        if (
            null !== ($className = $type->getClassName()) &&
            $this->resourceClassResolver->isResourceClass($className)
        ) {
            $resourceClass = $this->resourceClassResolver->getResourceClass(null, $className);
            $childContext = $this->createChildContext($context, $attribute, $format);
            $childContext['resource_class'] = $resourceClass;

            return $this->denormalizeRelation($attribute, $propertyMetadata, $resourceClass, $value, $format, $childContext);
        }

        if (
            $type->isCollection() &&
            null !== ($collectionValueType = $type->getCollectionValueType()) &&
            null !== ($className = $collectionValueType->getClassName())
        ) {
            if (!$this->serializer instanceof DenormalizerInterface) {
                throw new LogicException(sprintf('The injected serializer must be an instance of "%s".', DenormalizerInterface::class));
            }

            unset($context['resource_class']);

            return $this->serializer->denormalize($value, $className.'[]', $format, $context);
        }

        if (null !== $className = $type->getClassName()) {
            if (!$this->serializer instanceof DenormalizerInterface) {
                throw new LogicException(sprintf('The injected serializer must be an instance of "%s".', DenormalizerInterface::class));
            }

            unset($context['resource_class']);

            return $this->serializer->denormalize($value, $className, $format, $context);
        }

        if ($context[static::DISABLE_TYPE_ENFORCEMENT] ?? false) {
            return $value;
        }

        $this->validateType($attribute, $type, $value, $format);

        return $value;
    }

    /**
     * Sets a value of the object using the PropertyAccess component.
     *
     * @param object $object
     */
    private function setValue($object, string $attributeName, $value)
    {
        try {
            $this->propertyAccessor->setValue($object, $attributeName, $value);
        } catch (NoSuchPropertyException $exception) {
            // Properties not found are ignored
        }
    }

    private function supportsPlainIdentifiers(): bool
    {
        return $this->allowPlainIdentifiers && null !== $this->itemDataProvider;
    }
}
