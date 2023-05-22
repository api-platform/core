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

namespace ApiPlatform\Serializer;

use ApiPlatform\Api\IriConverterInterface;
use ApiPlatform\Api\ResourceClassResolverInterface;
use ApiPlatform\Api\UrlGeneratorInterface;
use ApiPlatform\Exception\InvalidArgumentException;
use ApiPlatform\Exception\ItemNotFoundException;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Util\ClassInfoTrait;
use ApiPlatform\Symfony\Security\ResourceAccessCheckerInterface;
use ApiPlatform\Util\CloneTrait;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Exception\MissingConstructorArgumentsException;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Exception\RuntimeException;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
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
    use CloneTrait;
    use ContextTrait;
    use InputOutputMetadataTrait;

    protected PropertyAccessorInterface $propertyAccessor;
    protected array $localCache = [];
    protected array $localFactoryOptionsCache = [];

    public function __construct(protected PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, protected PropertyMetadataFactoryInterface $propertyMetadataFactory, protected IriConverterInterface $iriConverter, protected ResourceClassResolverInterface $resourceClassResolver, PropertyAccessorInterface $propertyAccessor = null, NameConverterInterface $nameConverter = null, ClassMetadataFactoryInterface $classMetadataFactory = null, array $defaultContext = [], ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory = null, protected ?ResourceAccessCheckerInterface $resourceAccessChecker = null)
    {
        if (!isset($defaultContext['circular_reference_handler'])) {
            $defaultContext['circular_reference_handler'] = fn ($object): ?string => $this->iriConverter->getIriFromResource($object);
        }

        parent::__construct($classMetadataFactory, $nameConverter, null, null, \Closure::fromCallable($this->getObjectClass(...)), $defaultContext);
        $this->propertyAccessor = $propertyAccessor ?: PropertyAccess::createPropertyAccessor();
        $this->resourceMetadataCollectionFactory = $resourceMetadataCollectionFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization(mixed $data, string $format = null, array $context = []): bool
    {
        if (!\is_object($data) || is_iterable($data)) {
            return false;
        }

        $class = $context['force_resource_class'] ?? $this->getObjectClass($data);
        if (($context['output']['class'] ?? null) === $class) {
            return true;
        }

        return $this->resourceClassResolver->isResourceClass($class);
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
    public function normalize(mixed $object, string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        $resourceClass = $context['force_resource_class'] ?? $this->getObjectClass($object);
        if ($outputClass = $this->getOutputClass($context)) {
            if (!$this->serializer instanceof NormalizerInterface) {
                throw new LogicException('Cannot normalize the output because the injected serializer is not a normalizer');
            }

            unset($context['output'], $context['operation'], $context['operation_name']);
            $context['resource_class'] = $outputClass;
            $context['api_sub_level'] = true;
            $context[self::ALLOW_EXTRA_ATTRIBUTES] = false;

            return $this->serializer->normalize($object, $format, $context);
        }

        if (isset($context['operation']) && $context['operation'] instanceof CollectionOperationInterface) {
            unset($context['operation_name']);
            unset($context['operation']);
            unset($context['iri']);
        }

        if ($this->resourceClassResolver->isResourceClass($resourceClass)) {
            $context = $this->initContext($resourceClass, $context);
        }

        $context['api_normalize'] = true;
        $iri = $context['iri'] ??= $this->iriConverter->getIriFromResource($object, UrlGeneratorInterface::ABS_URL, $context['operation'] ?? null, $context);

        /*
         * When true, converts the normalized data array of a resource into an
         * IRI, if the normalized data array is empty.
         *
         * This is useful when traversing from a non-resource towards an attribute
         * which is a resource, as we do not have the benefit of {@see ApiProperty::isReadableLink}.
         *
         * It must not be propagated to resources, as {@see ApiProperty::isReadableLink}
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
    public function supportsDenormalization(mixed $data, string $type, string $format = null, array $context = []): bool
    {
        if (($context['input']['class'] ?? null) === $type) {
            return true;
        }

        return $this->localCache[$type] ?? $this->localCache[$type] = $this->resourceClassResolver->isResourceClass($type);
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize(mixed $data, string $class, string $format = null, array $context = []): mixed
    {
        $resourceClass = $class;

        if ($inputClass = $this->getInputClass($context)) {
            if (!$this->serializer instanceof DenormalizerInterface) {
                throw new LogicException('Cannot normalize the output because the injected serializer is not a normalizer');
            }

            unset($context['input'], $context['operation'], $context['operation_name']);
            $context['resource_class'] = $inputClass;

            try {
                return $this->serializer->denormalize($data, $inputClass, $format, $context);
            } catch (NotNormalizableValueException $e) {
                throw new UnexpectedValueException('The input data is misformatted.', $e->getCode(), $e);
            }
        }

        if (null === $objectToPopulate = $this->extractObjectToPopulate($resourceClass, $context, static::OBJECT_TO_POPULATE)) {
            $normalizedData = \is_scalar($data) ? [$data] : $this->prepareForDenormalization($data);
            $class = $this->getClassDiscriminatorResolvedClass($normalizedData, $class, $context);
        }

        $context['api_denormalize'] = true;

        if ($this->resourceClassResolver->isResourceClass($class)) {
            $resourceClass = $this->resourceClassResolver->getResourceClass($objectToPopulate, $class);
            $context['resource_class'] = $resourceClass;
        }

        if (\is_string($data)) {
            try {
                return $this->iriConverter->getResourceFromIri($data, $context + ['fetch_data' => true]);
            } catch (ItemNotFoundException $e) {
                throw new UnexpectedValueException($e->getMessage(), $e->getCode(), $e);
            } catch (InvalidArgumentException $e) {
                throw new UnexpectedValueException(sprintf('Invalid IRI "%s".', $data), $e->getCode(), $e);
            }
        }

        if (!\is_array($data)) {
            throw NotNormalizableValueException::createForUnexpectedDataType(sprintf('The type of the "%s" resource must be "array" (nested document) or "string" (IRI), "%s" given.', $resourceClass, \gettype($data)), $data, [Type::BUILTIN_TYPE_ARRAY, Type::BUILTIN_TYPE_STRING], $context['deserialization_path'] ?? null);
        }

        $previousObject = $this->clone($objectToPopulate);
        $object = parent::denormalize($data, $class, $format, $context);

        if (!$this->resourceClassResolver->isResourceClass($class)) {
            return $object;
        }

        // Bypass the post-denormalize attribute revert logic if the object could not be
        // cloned since we cannot possibly revert any changes made to it.
        if (null !== $objectToPopulate && null === $previousObject) {
            return $object;
        }

        $options = $this->getFactoryOptions($context);
        $propertyNames = iterator_to_array($this->propertyNameCollectionFactory->create($resourceClass, $options));

        // Revert attributes that aren't allowed to be changed after a post-denormalize check
        foreach (array_keys($data) as $attribute) {
            $attribute = $this->nameConverter ? $this->nameConverter->denormalize((string) $attribute) : $attribute;
            if (!\in_array($attribute, $propertyNames, true)) {
                continue;
            }

            if (!$this->canAccessAttributePostDenormalize($object, $previousObject, $attribute, $context)) {
                if (null !== $previousObject) {
                    $this->setValue($object, $attribute, $this->propertyAccessor->getValue($previousObject, $attribute));
                } else {
                    $propertyMetadata = $this->propertyMetadataFactory->create($resourceClass, $attribute, $options);
                    $this->setValue($object, $attribute, $propertyMetadata->getDefault());
                }
            }
        }

        return $object;
    }

    /**
     * Method copy-pasted from symfony/serializer.
     * Remove it after symfony/serializer version update @see https://github.com/symfony/symfony/pull/28263.
     *
     * {@inheritdoc}
     *
     * @internal
     */
    protected function instantiateObject(array &$data, string $class, array &$context, \ReflectionClass $reflectionClass, array|bool $allowedAttributes, string $format = null): object
    {
        if (null !== $object = $this->extractObjectToPopulate($class, $context, static::OBJECT_TO_POPULATE)) {
            unset($context[static::OBJECT_TO_POPULATE]);

            return $object;
        }

        $class = $this->getClassDiscriminatorResolvedClass($data, $class, $context);
        $reflectionClass = new \ReflectionClass($class);

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

                        $params[] = $data[$paramName];
                    }
                } elseif ($allowed && !$ignored && (isset($data[$key]) || \array_key_exists($key, $data))) {
                    $constructorContext = $context;
                    $constructorContext['deserialization_path'] = $context['deserialization_path'] ?? $key;
                    try {
                        $params[] = $this->createConstructorArgument($data[$key], $key, $constructorParameter, $constructorContext, $format);
                    } catch (NotNormalizableValueException $exception) {
                        if (!isset($context['not_normalizable_value_exceptions'])) {
                            throw $exception;
                        }
                        $context['not_normalizable_value_exceptions'][] = $exception;
                    }

                    // Don't run set for a parameter passed to the constructor
                    unset($data[$key]);
                } elseif (isset($context[static::DEFAULT_CONSTRUCTOR_ARGUMENTS][$class][$key])) {
                    $params[] = $context[static::DEFAULT_CONSTRUCTOR_ARGUMENTS][$class][$key];
                } elseif ($constructorParameter->isDefaultValueAvailable()) {
                    $params[] = $constructorParameter->getDefaultValue();
                } else {
                    if (!isset($context['not_normalizable_value_exceptions'])) {
                        throw new MissingConstructorArgumentsException(sprintf('Cannot create an instance of "%s" from serialized data because its constructor requires parameter "%s" to be present.', $class, $constructorParameter->name), 0, null, [$constructorParameter->name]);
                    }

                    $exception = NotNormalizableValueException::createForUnexpectedDataType(sprintf('Failed to create object because the class misses the "%s" property.', $constructorParameter->name), $data, ['unknown'], $context['deserialization_path'] ?? null, true);
                    $context['not_normalizable_value_exceptions'][] = $exception;
                }
            }

            if (\count($context['not_normalizable_value_exceptions'] ?? []) > 0) {
                return $reflectionClass->newInstanceWithoutConstructor();
            }

            if ($constructor->isConstructor()) {
                return $reflectionClass->newInstanceArgs($params);
            }

            return $constructor->invokeArgs(null, $params);
        }

        return new $class();
    }

    protected function getClassDiscriminatorResolvedClass(array $data, string $class, array $context = []): string
    {
        if (null === $this->classDiscriminatorResolver || (null === $mapping = $this->classDiscriminatorResolver->getMappingForClass($class))) {
            return $class;
        }

        if (!isset($data[$mapping->getTypeProperty()])) {
            throw NotNormalizableValueException::createForUnexpectedDataType(sprintf('Type property "%s" not found for the abstract object "%s".', $mapping->getTypeProperty(), $class), null, ['string'], isset($context['deserialization_path']) ? $context['deserialization_path'].'.'.$mapping->getTypeProperty() : $mapping->getTypeProperty());
        }

        $type = $data[$mapping->getTypeProperty()];
        if (null === ($mappedClass = $mapping->getClassForType($type))) {
            throw NotNormalizableValueException::createForUnexpectedDataType(sprintf('The type "%s" is not a valid value.', $type), $type, ['string'], isset($context['deserialization_path']) ? $context['deserialization_path'].'.'.$mapping->getTypeProperty() : $mapping->getTypeProperty(), true);
        }

        return $mappedClass;
    }

    protected function createConstructorArgument($parameterData, string $key, \ReflectionParameter $constructorParameter, array &$context, string $format = null): mixed
    {
        return $this->createAndValidateAttributeValue($constructorParameter->name, $parameterData, $format, $context);
    }

    /**
     * {@inheritdoc}
     *
     * Unused in this context.
     *
     * @return string[]
     */
    protected function extractAttributes($object, $format = null, array $context = []): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    protected function getAllowedAttributes(string|object $classOrObject, array $context, bool $attributesAsString = false): array|bool
    {
        if (!$this->resourceClassResolver->isResourceClass($context['resource_class'])) {
            return parent::getAllowedAttributes($classOrObject, $context, $attributesAsString);
        }

        $resourceClass = $this->resourceClassResolver->getResourceClass(null, $context['resource_class']); // fix for abstract classes and interfaces
        $options = $this->getFactoryOptions($context);
        $propertyNames = $this->propertyNameCollectionFactory->create($resourceClass, $options);

        $allowedAttributes = [];
        foreach ($propertyNames as $propertyName) {
            $propertyMetadata = $this->propertyMetadataFactory->create($resourceClass, $propertyName, $options);

            if (
                $this->isAllowedAttribute($classOrObject, $propertyName, null, $context)
                && (
                    isset($context['api_normalize']) && $propertyMetadata->isReadable()
                    || isset($context['api_denormalize']) && ($propertyMetadata->isWritable() || !\is_object($classOrObject) && $propertyMetadata->isInitializable())
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
    protected function isAllowedAttribute(object|string $classOrObject, string $attribute, string $format = null, array $context = []): bool
    {
        if (!parent::isAllowedAttribute($classOrObject, $attribute, $format, $context)) {
            return false;
        }

        return $this->canAccessAttribute(\is_object($classOrObject) ? $classOrObject : null, $attribute, $context);
    }

    /**
     * Check if access to the attribute is granted.
     */
    protected function canAccessAttribute(?object $object, string $attribute, array $context = []): bool
    {
        if (!$this->resourceClassResolver->isResourceClass($context['resource_class'])) {
            return true;
        }

        $options = $this->getFactoryOptions($context);
        $propertyMetadata = $this->propertyMetadataFactory->create($context['resource_class'], $attribute, $options);
        $security = $propertyMetadata->getSecurity();
        if (null !== $this->resourceAccessChecker && $security) {
            return $this->resourceAccessChecker->isGranted($context['resource_class'], $security, [
                'object' => $object,
            ]);
        }

        return true;
    }

    /**
     * Check if access to the attribute is granted.
     */
    protected function canAccessAttributePostDenormalize(?object $object, ?object $previousObject, string $attribute, array $context = []): bool
    {
        $options = $this->getFactoryOptions($context);
        $propertyMetadata = $this->propertyMetadataFactory->create($context['resource_class'], $attribute, $options);
        $security = $propertyMetadata->getSecurityPostDenormalize();
        if ($this->resourceAccessChecker && $security) {
            return $this->resourceAccessChecker->isGranted($context['resource_class'], $security, [
                'object' => $object,
                'previous_object' => $previousObject,
            ]);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function setAttributeValue(object $object, string $attribute, mixed $value, string $format = null, array $context = []): void
    {
        try {
            $this->setValue($object, $attribute, $this->createAttributeValue($attribute, $value, $format, $context));
        } catch (NotNormalizableValueException $exception) {
            // Only throw if collecting denormalization errors is disabled.
            if (!isset($context['not_normalizable_value_exceptions'])) {
                throw $exception;
            }
        }
    }

    /**
     * Validates the type of the value. Allows using integers as floats for JSON formats.
     *
     * @throws NotNormalizableValueException
     */
    protected function validateType(string $attribute, Type $type, mixed $value, string $format = null, array $context = []): void
    {
        $builtinType = $type->getBuiltinType();
        if (Type::BUILTIN_TYPE_FLOAT === $builtinType && null !== $format && str_contains($format, 'json')) {
            $isValid = \is_float($value) || \is_int($value);
        } else {
            $isValid = \call_user_func('is_'.$builtinType, $value);
        }

        if (!$isValid) {
            throw NotNormalizableValueException::createForUnexpectedDataType(sprintf('The type of the "%s" attribute must be "%s", "%s" given.', $attribute, $builtinType, \gettype($value)), $value, [$builtinType], $context['deserialization_path'] ?? null);
        }
    }

    /**
     * Denormalizes a collection of objects.
     *
     * @throws NotNormalizableValueException
     */
    protected function denormalizeCollection(string $attribute, ApiProperty $propertyMetadata, Type $type, string $className, mixed $value, ?string $format, array $context): array
    {
        if (!\is_array($value)) {
            throw NotNormalizableValueException::createForUnexpectedDataType(sprintf('The type of the "%s" attribute must be "array", "%s" given.', $attribute, \gettype($value)), $value, [Type::BUILTIN_TYPE_ARRAY], $context['deserialization_path'] ?? null);
        }

        $collectionKeyType = $type->getCollectionKeyTypes()[0] ?? null;
        $collectionKeyBuiltinType = $collectionKeyType?->getBuiltinType();

        $values = [];
        foreach ($value as $index => $obj) {
            if (null !== $collectionKeyBuiltinType && !\call_user_func('is_'.$collectionKeyBuiltinType, $index)) {
                throw NotNormalizableValueException::createForUnexpectedDataType(sprintf('The type of the key "%s" must be "%s", "%s" given.', $index, $collectionKeyBuiltinType, \gettype($index)), $index, [$collectionKeyBuiltinType], ($context['deserialization_path'] ?? false) ? sprintf('key(%s)', $context['deserialization_path']) : null, true);
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
     * @throws NotNormalizableValueException
     */
    protected function denormalizeRelation(string $attributeName, ApiProperty $propertyMetadata, string $className, mixed $value, ?string $format, array $context): ?object
    {
        if (\is_string($value)) {
            try {
                return $this->iriConverter->getResourceFromIri($value, $context + ['fetch_data' => true]);
            } catch (ItemNotFoundException $e) {
                throw new UnexpectedValueException($e->getMessage(), $e->getCode(), $e);
            } catch (InvalidArgumentException $e) {
                throw new UnexpectedValueException(sprintf('Invalid IRI "%s".', $value), $e->getCode(), $e);
            }
        }

        if ($propertyMetadata->isWritableLink()) {
            $context['api_allow_update'] = true;

            if (!$this->serializer instanceof DenormalizerInterface) {
                throw new LogicException(sprintf('The injected serializer must be an instance of "%s".', DenormalizerInterface::class));
            }

            $item = $this->serializer->denormalize($value, $className, $format, $context);
            if (!\is_object($item) && null !== $item) {
                throw new \UnexpectedValueException('Expected item to be an object or null.');
            }

            return $item;
        }

        if (!\is_array($value)) {
            throw NotNormalizableValueException::createForUnexpectedDataType(sprintf('The type of the "%s" attribute must be "array" (nested document) or "string" (IRI), "%s" given.', $attributeName, \gettype($value)), $value, [Type::BUILTIN_TYPE_ARRAY, Type::BUILTIN_TYPE_STRING], $context['deserialization_path'] ?? null);
        }

        throw new UnexpectedValueException(sprintf('Nested documents for attribute "%s" are not allowed. Use IRIs instead.', $attributeName));
    }

    /**
     * Gets the options for the property name collection / property metadata factories.
     */
    protected function getFactoryOptions(array $context): array
    {
        $options = [];
        if (isset($context[self::GROUPS])) {
            /* @see https://github.com/symfony/symfony/blob/v4.2.6/src/Symfony/Component/PropertyInfo/Extractor/SerializerExtractor.php */
            $options['serializer_groups'] = (array) $context[self::GROUPS];
        }

        $operationCacheKey = ($context['resource_class'] ?? '').($context['operation_name'] ?? '').($context['api_normalize'] ?? '');
        if ($operationCacheKey && isset($this->localFactoryOptionsCache[$operationCacheKey])) {
            return $options + $this->localFactoryOptionsCache[$operationCacheKey];
        }

        // This is a hot spot
        if (isset($context['resource_class'])) {
            // Note that the groups need to be read on the root operation
            $operation = $context['root_operation'] ?? $context['operation'] ?? null;

            if (!$operation && $this->resourceMetadataCollectionFactory && $this->resourceClassResolver->isResourceClass($context['resource_class'])) {
                $resourceClass = $this->resourceClassResolver->getResourceClass(null, $context['resource_class']); // fix for abstract classes and interfaces
                $operation = $this->resourceMetadataCollectionFactory->create($resourceClass)->getOperation($context['root_operation_name'] ?? $context['operation_name'] ?? null);
            }

            if ($operation) {
                $options['normalization_groups'] = $operation->getNormalizationContext()['groups'] ?? null;
                $options['denormalization_groups'] = $operation->getDenormalizationContext()['groups'] ?? null;
                $options['operation_name'] = $operation->getName();
            }
        }

        return $options + $this->localFactoryOptionsCache[$operationCacheKey] = $options;
    }

    /**
     * {@inheritdoc}
     *
     * @throws UnexpectedValueException
     */
    protected function getAttributeValue(object $object, string $attribute, string $format = null, array $context = []): mixed
    {
        $context['api_attribute'] = $attribute;
        $propertyMetadata = $this->propertyMetadataFactory->create($context['resource_class'], $attribute, $this->getFactoryOptions($context));

        $attributeValue = $this->propertyAccessor->getValue($object, $attribute);

        if ($context['api_denormalize'] ?? false) {
            return $attributeValue;
        }

        $type = $propertyMetadata->getBuiltinTypes()[0] ?? null;

        if (
            $type
            && $type->isCollection()
            && ($collectionValueType = $type->getCollectionValueTypes()[0] ?? null)
            && ($className = $collectionValueType->getClassName())
            && $this->resourceClassResolver->isResourceClass($className)
        ) {
            if (!is_iterable($attributeValue)) {
                throw new UnexpectedValueException('Unexpected non-iterable value for to-many relation.');
            }

            $resourceClass = $this->resourceClassResolver->getResourceClass($attributeValue, $className);
            $childContext = $this->createChildContext($context, $attribute, $format);
            unset($childContext['iri'], $childContext['uri_variables'], $childContext['resource_class'], $childContext['operation']);

            return $this->normalizeCollectionOfRelations($propertyMetadata, $attributeValue, $resourceClass, $format, $childContext);
        }

        if (
            $type
            && ($className = $type->getClassName())
            && $this->resourceClassResolver->isResourceClass($className)
        ) {
            if (!\is_object($attributeValue) && null !== $attributeValue) {
                throw new UnexpectedValueException('Unexpected non-object value for to-one relation.');
            }

            $resourceClass = $this->resourceClassResolver->getResourceClass($attributeValue, $className);
            $childContext = $this->createChildContext($context, $attribute, $format);
            $childContext['resource_class'] = $resourceClass;
            if ($this->resourceMetadataCollectionFactory) {
                $childContext['operation'] = $this->resourceMetadataCollectionFactory->create($resourceClass)->getOperation();
            }
            unset($childContext['iri'], $childContext['uri_variables']);

            return $this->normalizeRelation($propertyMetadata, $attributeValue, $resourceClass, $format, $childContext);
        }

        if (!$this->serializer instanceof NormalizerInterface) {
            throw new LogicException(sprintf('The injected serializer must be an instance of "%s".', NormalizerInterface::class));
        }

        unset($context['resource_class']);
        unset($context['force_resource_class']);

        if ($type && $type->getClassName()) {
            $childContext = $this->createChildContext($context, $attribute, $format);
            unset($childContext['iri'], $childContext['uri_variables']);
            $childContext['output']['gen_id'] = $propertyMetadata->getGenId() ?? true;

            return $this->serializer->normalize($attributeValue, $format, $childContext);
        }

        if ($type && 'array' === $type->getBuiltinType()) {
            $childContext = $this->createChildContext($context, $attribute, $format);
            unset($childContext['iri'], $childContext['uri_variables']);

            return $this->serializer->normalize($attributeValue, $format, $childContext);
        }

        return $this->serializer->normalize($attributeValue, $format, $context);
    }

    /**
     * Normalizes a collection of relations (to-many).
     *
     * @throws UnexpectedValueException
     */
    protected function normalizeCollectionOfRelations(ApiProperty $propertyMetadata, iterable $attributeValue, string $resourceClass, ?string $format, array $context): array
    {
        $value = [];
        foreach ($attributeValue as $index => $obj) {
            if (!\is_object($obj) && null !== $obj) {
                throw new UnexpectedValueException('Unexpected non-object element in to-many relation.');
            }

            // update context, if concrete object class deviates from general relation class (e.g. in case of polymorphic resources)
            $objResourceClass = $this->resourceClassResolver->getResourceClass($obj, $resourceClass);
            $context['resource_class'] = $objResourceClass;
            if ($this->resourceMetadataCollectionFactory) {
                $context['operation'] = $this->resourceMetadataCollectionFactory->create($objResourceClass)->getOperation();
            }

            $value[$index] = $this->normalizeRelation($propertyMetadata, $obj, $resourceClass, $format, $context);
        }

        return $value;
    }

    /**
     * Normalizes a relation.
     *
     * @throws LogicException
     * @throws UnexpectedValueException
     */
    protected function normalizeRelation(ApiProperty $propertyMetadata, ?object $relatedObject, string $resourceClass, ?string $format, array $context): \ArrayObject|array|string|null
    {
        if (null === $relatedObject || !empty($context['attributes']) || $propertyMetadata->isReadableLink()) {
            if (!$this->serializer instanceof NormalizerInterface) {
                throw new LogicException(sprintf('The injected serializer must be an instance of "%s".', NormalizerInterface::class));
            }

            $relatedContext = $context;
            unset($relatedContext['force_resource_class']);
            $normalizedRelatedObject = $this->serializer->normalize($relatedObject, $format, $relatedContext);
            if (!\is_string($normalizedRelatedObject) && !\is_array($normalizedRelatedObject) && !$normalizedRelatedObject instanceof \ArrayObject && null !== $normalizedRelatedObject) {
                throw new UnexpectedValueException('Expected normalized relation to be an IRI, array, \ArrayObject or null');
            }

            return $normalizedRelatedObject;
        }

        $iri = $this->iriConverter->getIriFromResource($relatedObject);

        if (isset($context['resources'])) {
            $context['resources'][$iri] = $iri;
        }

        $push = $propertyMetadata->getPush() ?? false;
        if (isset($context['resources_to_push']) && $push) {
            $context['resources_to_push'][$iri] = $iri;
        }

        return $iri;
    }

    private function createAttributeValue(string $attribute, mixed $value, string $format = null, array &$context = []): mixed
    {
        try {
            return $this->createAndValidateAttributeValue($attribute, $value, $format, $context);
        } catch (NotNormalizableValueException $exception) {
            if (!isset($context['not_normalizable_value_exceptions'])) {
                throw $exception;
            }
            $context['not_normalizable_value_exceptions'][] = $exception;

            throw $exception;
        }
    }

    private function createAndValidateAttributeValue(string $attribute, mixed $value, string $format = null, array $context = []): mixed
    {
        $propertyMetadata = $this->propertyMetadataFactory->create($context['resource_class'], $attribute, $this->getFactoryOptions($context));
        $type = $propertyMetadata->getBuiltinTypes()[0] ?? null;

        if (null === $type) {
            // No type provided, blindly return the value
            return $value;
        }

        if (null === $value && ($type->isNullable() || ($context[static::DISABLE_TYPE_ENFORCEMENT] ?? false))) {
            return $value;
        }

        $collectionValueType = $type->getCollectionValueTypes()[0] ?? null;

        /* From @see AbstractObjectNormalizer::validateAndDenormalize() */
        // Fix a collection that contains the only one element
        // This is special to xml format only
        if ('xml' === $format && null !== $collectionValueType && (!\is_array($value) || !\is_int(key($value)))) {
            $value = [$value];
        }

        if (
            $type->isCollection()
            && null !== $collectionValueType
            && null !== ($className = $collectionValueType->getClassName())
            && $this->resourceClassResolver->isResourceClass($className)
        ) {
            $resourceClass = $this->resourceClassResolver->getResourceClass(null, $className);
            $context['resource_class'] = $resourceClass;

            return $this->denormalizeCollection($attribute, $propertyMetadata, $type, $resourceClass, $value, $format, $context);
        }

        if (
            null !== ($className = $type->getClassName())
            && $this->resourceClassResolver->isResourceClass($className)
        ) {
            $resourceClass = $this->resourceClassResolver->getResourceClass(null, $className);
            $childContext = $this->createChildContext($context, $attribute, $format);
            $childContext['resource_class'] = $resourceClass;
            if ($this->resourceMetadataCollectionFactory) {
                $childContext['operation'] = $this->resourceMetadataCollectionFactory->create($resourceClass)->getOperation();
            }

            return $this->denormalizeRelation($attribute, $propertyMetadata, $resourceClass, $value, $format, $childContext);
        }

        if (
            $type->isCollection()
            && null !== $collectionValueType
            && null !== ($className = $collectionValueType->getClassName())
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

        /* From @see AbstractObjectNormalizer::validateAndDenormalize() */
        // In XML and CSV all basic datatypes are represented as strings, it is e.g. not possible to determine,
        // if a value is meant to be a string, float, int or a boolean value from the serialized representation.
        // That's why we have to transform the values, if one of these non-string basic datatypes is expected.
        if (\is_string($value) && (XmlEncoder::FORMAT === $format || CsvEncoder::FORMAT === $format)) {
            if ('' === $value && $type->isNullable() && \in_array($type->getBuiltinType(), [Type::BUILTIN_TYPE_BOOL, Type::BUILTIN_TYPE_INT, Type::BUILTIN_TYPE_FLOAT], true)) {
                return null;
            }

            switch ($type->getBuiltinType()) {
                case Type::BUILTIN_TYPE_BOOL:
                    // according to http://www.w3.org/TR/xmlschema-2/#boolean, valid representations are "false", "true", "0" and "1"
                    if ('false' === $value || '0' === $value) {
                        $value = false;
                    } elseif ('true' === $value || '1' === $value) {
                        $value = true;
                    } else {
                        throw NotNormalizableValueException::createForUnexpectedDataType(sprintf('The type of the "%s" attribute for class "%s" must be bool ("%s" given).', $attribute, $className, $value), $value, [Type::BUILTIN_TYPE_BOOL], $context['deserialization_path'] ?? null);
                    }
                    break;
                case Type::BUILTIN_TYPE_INT:
                    if (ctype_digit($value) || ('-' === $value[0] && ctype_digit(substr($value, 1)))) {
                        $value = (int) $value;
                    } else {
                        throw NotNormalizableValueException::createForUnexpectedDataType(sprintf('The type of the "%s" attribute for class "%s" must be int ("%s" given).', $attribute, $className, $value), $value, [Type::BUILTIN_TYPE_INT], $context['deserialization_path'] ?? null);
                    }
                    break;
                case Type::BUILTIN_TYPE_FLOAT:
                    if (is_numeric($value)) {
                        return (float) $value;
                    }

                    return match ($value) {
                        'NaN' => \NAN,
                        'INF' => \INF,
                        '-INF' => -\INF,
                        default => throw NotNormalizableValueException::createForUnexpectedDataType(sprintf('The type of the "%s" attribute for class "%s" must be float ("%s" given).', $attribute, $className, $value), $value, [Type::BUILTIN_TYPE_FLOAT], $context['deserialization_path'] ?? null),
                    };
            }
        }

        if ($context[static::DISABLE_TYPE_ENFORCEMENT] ?? false) {
            return $value;
        }

        $this->validateType($attribute, $type, $value, $format, $context);

        return $value;
    }

    /**
     * Sets a value of the object using the PropertyAccess component.
     */
    private function setValue(object $object, string $attributeName, mixed $value): void
    {
        try {
            $this->propertyAccessor->setValue($object, $attributeName, $value);
        } catch (NoSuchPropertyException) {
            // Properties not found are ignored
        }
    }
}
