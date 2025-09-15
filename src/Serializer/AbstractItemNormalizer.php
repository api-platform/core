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

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Exception\AccessDeniedException;
use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\Exception\ItemNotFoundException;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\ResourceAccessCheckerInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use ApiPlatform\Metadata\Util\ClassInfoTrait;
use ApiPlatform\Metadata\Util\CloneTrait;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\PropertyInfo\Type as LegacyType;
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
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\Type\CollectionType;
use Symfony\Component\TypeInfo\Type\CompositeTypeInterface;
use Symfony\Component\TypeInfo\Type\NullableType;
use Symfony\Component\TypeInfo\Type\ObjectType;
use Symfony\Component\TypeInfo\Type\WrappingTypeInterface;
use Symfony\Component\TypeInfo\TypeIdentifier;

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
    use OperationContextTrait;
    /**
     * Flag to control whether to one relation with the value `null` should be output
     * when normalizing or omitted.
     */
    public const SKIP_NULL_TO_ONE_RELATIONS = 'skip_null_to_one_relations';

    protected PropertyAccessorInterface $propertyAccessor;
    protected array $localCache = [];
    protected array $localFactoryOptionsCache = [];
    protected ?ResourceAccessCheckerInterface $resourceAccessChecker;

    public function __construct(protected PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, protected PropertyMetadataFactoryInterface $propertyMetadataFactory, protected IriConverterInterface $iriConverter, protected ResourceClassResolverInterface $resourceClassResolver, ?PropertyAccessorInterface $propertyAccessor = null, ?NameConverterInterface $nameConverter = null, ?ClassMetadataFactoryInterface $classMetadataFactory = null, array $defaultContext = [], ?ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory = null, ?ResourceAccessCheckerInterface $resourceAccessChecker = null, protected ?TagCollectorInterface $tagCollector = null)
    {
        if (!isset($defaultContext['circular_reference_handler'])) {
            $defaultContext['circular_reference_handler'] = fn ($object): ?string => $this->iriConverter->getIriFromResource($object);
        }

        parent::__construct($classMetadataFactory, $nameConverter, null, null, \Closure::fromCallable($this->getObjectClass(...)), $defaultContext);
        $this->propertyAccessor = $propertyAccessor ?: PropertyAccess::createPropertyAccessor();
        $this->resourceAccessChecker = $resourceAccessChecker;
        $this->resourceMetadataCollectionFactory = $resourceMetadataCollectionFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
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

    public function getSupportedTypes(?string $format): array
    {
        return [
            'object' => true,
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @throws LogicException
     */
    public function normalize(mixed $object, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
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

        // Never remove this, with `application/json` we don't use our AbstractCollectionNormalizer and we need
        // to remove the collection operation from our context or we'll introduce security issues
        if (isset($context['operation']) && $context['operation'] instanceof CollectionOperationInterface) {
            unset($context['operation_name'], $context['operation'], $context['iri']);
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

        if (!$this->tagCollector && isset($context['resources'])) {
            $context['resources'][$iri] = $iri;
        }

        $context['object'] = $object;
        $context['format'] = $format;

        $data = parent::normalize($object, $format, $context);

        $context['data'] = $data;
        unset($context['property_metadata'], $context['api_attribute']);

        if ($emptyResourceAsIri && \is_array($data) && 0 === \count($data)) {
            $context['data'] = $iri;

            if ($this->tagCollector) {
                $this->tagCollector->collect($context);
            }

            return $iri;
        }

        if ($this->tagCollector) {
            $this->tagCollector->collect($context);
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        if (($context['input']['class'] ?? null) === $type) {
            return true;
        }

        return $this->localCache[$type] ?? $this->localCache[$type] = $this->resourceClassResolver->isResourceClass($type);
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize(mixed $data, string $class, ?string $format = null, array $context = []): mixed
    {
        $resourceClass = $class;

        if ($inputClass = $this->getInputClass($context)) {
            if (!$this->serializer instanceof DenormalizerInterface) {
                throw new LogicException('Cannot denormalize the input because the injected serializer is not a denormalizer');
            }

            unset($context['input'], $context['operation'], $context['operation_name'], $context['uri_variables']);
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
                if (!isset($context['not_normalizable_value_exceptions'])) {
                    throw new UnexpectedValueException($e->getMessage(), $e->getCode(), $e);
                }

                throw NotNormalizableValueException::createForUnexpectedDataType($e->getMessage(), $data, [$resourceClass], $context['deserialization_path'] ?? null, true, $e->getCode(), $e);
            } catch (InvalidArgumentException $e) {
                if (!isset($context['not_normalizable_value_exceptions'])) {
                    throw new UnexpectedValueException(\sprintf('Invalid IRI "%s".', $data), $e->getCode(), $e);
                }

                throw NotNormalizableValueException::createForUnexpectedDataType(\sprintf('Invalid IRI "%s".', $data), $data, [$resourceClass], $context['deserialization_path'] ?? null, true, $e->getCode(), $e);
            }
        }

        if (!\is_array($data)) {
            throw NotNormalizableValueException::createForUnexpectedDataType(\sprintf('The type of the "%s" resource must be "array" (nested document) or "string" (IRI), "%s" given.', $resourceClass, \gettype($data)), $data, ['array', 'string'], $context['deserialization_path'] ?? null);
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

        $operation = $context['operation'] ?? null;
        $throwOnAccessDenied = $operation?->getExtraProperties()['throw_on_access_denied'] ?? false;
        $securityMessage = $operation?->getSecurityMessage() ?? null;

        // Revert attributes that aren't allowed to be changed after a post-denormalize check
        foreach (array_keys($data) as $attribute) {
            $attribute = $this->nameConverter ? $this->nameConverter->denormalize((string) $attribute) : $attribute;
            $propertyMetadata = $this->propertyMetadataFactory->create($resourceClass, $attribute, $options);
            $attributeExtraProperties = $propertyMetadata->getExtraProperties();
            $throwOnPropertyAccessDenied = $attributeExtraProperties['throw_on_access_denied'] ?? $throwOnAccessDenied;
            if (!\in_array($attribute, $propertyNames, true)) {
                continue;
            }

            if (!$this->canAccessAttributePostDenormalize($object, $previousObject, $attribute, $context)) {
                if ($throwOnPropertyAccessDenied) {
                    throw new AccessDeniedException($securityMessage ?? 'Access denied');
                }
                if (null !== $previousObject) {
                    $this->setValue($object, $attribute, $this->propertyAccessor->getValue($previousObject, $attribute));
                } else {
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
    protected function instantiateObject(array &$data, string $class, array &$context, \ReflectionClass $reflectionClass, array|bool $allowedAttributes, ?string $format = null): object
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
            $missingConstructorArguments = [];
            foreach ($constructorParameters as $constructorParameter) {
                $paramName = $constructorParameter->name;
                $key = $this->nameConverter ? $this->nameConverter->normalize($paramName, $class, $format, $context) : $paramName;
                $attributeContext = $this->getAttributeDenormalizationContext($class, $paramName, $context);
                $attributeContext['deserialization_path'] = $attributeContext['deserialization_path'] ?? $key;

                $allowed = false === $allowedAttributes || (\is_array($allowedAttributes) && \in_array($paramName, $allowedAttributes, true));
                $ignored = !$this->isAllowedAttribute($class, $paramName, $format, $context);
                if ($constructorParameter->isVariadic()) {
                    if ($allowed && !$ignored && (isset($data[$key]) || \array_key_exists($key, $data))) {
                        if (!\is_array($data[$paramName])) {
                            throw new RuntimeException(\sprintf('Cannot create an instance of %s from serialized data because the variadic parameter %s can only accept an array.', $class, $constructorParameter->name));
                        }

                        $params[] = $data[$paramName];
                    }
                } elseif ($allowed && !$ignored && (isset($data[$key]) || \array_key_exists($key, $data))) {
                    try {
                        $params[] = $this->createConstructorArgument($data[$key], $key, $constructorParameter, $attributeContext, $format);
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
                        $missingConstructorArguments[] = $constructorParameter->name;
                    }

                    $constructorParameterType = 'unknown';
                    $reflectionType = $constructorParameter->getType();
                    if ($reflectionType instanceof \ReflectionNamedType) {
                        $constructorParameterType = $reflectionType->getName();
                    }

                    $exception = NotNormalizableValueException::createForUnexpectedDataType(
                        \sprintf('Failed to create object because the class misses the "%s" property.', $constructorParameter->name),
                        null,
                        [$constructorParameterType],
                        $attributeContext['deserialization_path'],
                        true
                    );
                    $context['not_normalizable_value_exceptions'][] = $exception;
                }
            }

            if ($missingConstructorArguments) {
                throw new MissingConstructorArgumentsException(\sprintf('Cannot create an instance of "%s" from serialized data because its constructor requires the following parameters to be present : "$%s".', $class, implode('", "$', $missingConstructorArguments)), 0, null, $missingConstructorArguments, $class);
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

        // @phpstan-ignore-next-line function.alreadyNarrowedType
        $defaultType = method_exists($mapping, 'getDefaultType') ? $mapping->getDefaultType() : null;
        if (!isset($data[$mapping->getTypeProperty()]) && null === $defaultType) {
            throw NotNormalizableValueException::createForUnexpectedDataType(\sprintf('Type property "%s" not found for the abstract object "%s".', $mapping->getTypeProperty(), $class), null, ['string'], isset($context['deserialization_path']) ? $context['deserialization_path'].'.'.$mapping->getTypeProperty() : $mapping->getTypeProperty());
        }

        $type = $data[$mapping->getTypeProperty()] ?? $defaultType;
        if (null === ($mappedClass = $mapping->getClassForType($type))) {
            throw NotNormalizableValueException::createForUnexpectedDataType(\sprintf('The type "%s" is not a valid value.', $type), $type, ['string'], isset($context['deserialization_path']) ? $context['deserialization_path'].'.'.$mapping->getTypeProperty() : $mapping->getTypeProperty(), true);
        }

        return $mappedClass;
    }

    protected function createConstructorArgument(mixed $parameterData, string $key, \ReflectionParameter $constructorParameter, array $context, ?string $format = null): mixed
    {
        return $this->createAndValidateAttributeValue($constructorParameter->name, $parameterData, $format, $context);
    }

    /**
     * {@inheritdoc}
     *
     * Unused in this context.
     *
     * @param object      $object
     * @param string|null $format
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
                && (isset($context['api_normalize']) && $propertyMetadata->isReadable()
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
    protected function isAllowedAttribute(object|string $classOrObject, string $attribute, ?string $format = null, array $context = []): bool
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
        $security = $propertyMetadata->getSecurity() ?? $propertyMetadata->getPolicy();
        if (null !== $this->resourceAccessChecker && $security) {
            return $this->resourceAccessChecker->isGranted($context['resource_class'], $security, [
                'object' => $object,
                'property' => $attribute,
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
                'property' => $attribute,
            ]);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function setAttributeValue(object $object, string $attribute, mixed $value, ?string $format = null, array $context = []): void
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
     * @deprecated since 4.1, use "validateAttributeType" instead
     *
     * Validates the type of the value. Allows using integers as floats for JSON formats.
     *
     * @throws NotNormalizableValueException
     */
    protected function validateType(string $attribute, LegacyType $type, mixed $value, ?string $format = null, array $context = []): void
    {
        trigger_deprecation('api-platform/serializer', '4.1', 'The "%s()" method is deprecated, use "%s::validateAttributeType()" instead.', __METHOD__, self::class);

        $builtinType = $type->getBuiltinType();
        if (LegacyType::BUILTIN_TYPE_FLOAT === $builtinType && null !== $format && str_contains($format, 'json')) {
            $isValid = \is_float($value) || \is_int($value);
        } else {
            $isValid = \call_user_func('is_'.$builtinType, $value);
        }

        if (!$isValid) {
            throw NotNormalizableValueException::createForUnexpectedDataType(\sprintf('The type of the "%s" attribute must be "%s", "%s" given.', $attribute, $builtinType, \gettype($value)), $value, [$builtinType], $context['deserialization_path'] ?? null);
        }
    }

    /**
     * Validates the type of the value. Allows using integers as floats for JSON formats.
     *
     * @throws NotNormalizableValueException
     */
    protected function validateAttributeType(string $attribute, Type $type, mixed $value, ?string $format = null, array $context = []): void
    {
        if ($type->isIdentifiedBy(TypeIdentifier::FLOAT) && null !== $format && str_contains($format, 'json')) {
            $isValid = \is_float($value) || \is_int($value);
        } else {
            $isValid = $type->accepts($value);
        }

        if (!$isValid) {
            throw NotNormalizableValueException::createForUnexpectedDataType(\sprintf('The type of the "%s" attribute must be "%s", "%s" given.', $attribute, $type, \gettype($value)), $value, [(string) $type], $context['deserialization_path'] ?? null);
        }
    }

    /**
     * @deprecated since 4.1, use "denormalizeObjectCollection" instead.
     *
     * Denormalizes a collection of objects.
     *
     * @throws NotNormalizableValueException
     */
    protected function denormalizeCollection(string $attribute, ApiProperty $propertyMetadata, LegacyType $type, string $className, mixed $value, ?string $format, array $context): array
    {
        trigger_deprecation('api-platform/serializer', '4.1', 'The "%s()" method is deprecated, use "%s::denormalizeObjectCollection()" instead.', __METHOD__, self::class);

        if (!\is_array($value)) {
            throw NotNormalizableValueException::createForUnexpectedDataType(\sprintf('The type of the "%s" attribute must be "array", "%s" given.', $attribute, \gettype($value)), $value, ['array'], $context['deserialization_path'] ?? null);
        }

        $values = [];
        $childContext = $this->createChildContext($this->createOperationContext($context, $className), $attribute, $format);
        $collectionKeyTypes = $type->getCollectionKeyTypes();
        foreach ($value as $index => $obj) {
            $currentChildContext = $childContext;
            if (isset($childContext['deserialization_path'])) {
                $currentChildContext['deserialization_path'] = "{$childContext['deserialization_path']}[{$index}]";
            }

            // no typehint provided on collection key
            if (!$collectionKeyTypes) {
                $values[$index] = $this->denormalizeRelation($attribute, $propertyMetadata, $className, $obj, $format, $currentChildContext);
                continue;
            }

            // validate collection key typehint
            foreach ($collectionKeyTypes as $collectionKeyType) {
                $collectionKeyBuiltinType = $collectionKeyType->getBuiltinType();
                if (!\call_user_func('is_'.$collectionKeyBuiltinType, $index)) {
                    continue;
                }

                $values[$index] = $this->denormalizeRelation($attribute, $propertyMetadata, $className, $obj, $format, $currentChildContext);
                continue 2;
            }
            throw NotNormalizableValueException::createForUnexpectedDataType(\sprintf('The type of the key "%s" must be "%s", "%s" given.', $index, $collectionKeyTypes[0]->getBuiltinType(), \gettype($index)), $index, [$collectionKeyTypes[0]->getBuiltinType()], ($context['deserialization_path'] ?? false) ? \sprintf('key(%s)', $context['deserialization_path']) : null, true);
        }

        return $values;
    }

    /**
     * Denormalizes a collection of objects.
     *
     * @throws NotNormalizableValueException
     */
    protected function denormalizeObjectCollection(string $attribute, ApiProperty $propertyMetadata, Type $type, string $className, mixed $value, ?string $format, array $context): array
    {
        if (!\is_array($value)) {
            throw NotNormalizableValueException::createForUnexpectedDataType(\sprintf('The type of the "%s" attribute must be "array", "%s" given.', $attribute, \gettype($value)), $value, ['array'], $context['deserialization_path'] ?? null);
        }

        $values = [];
        $childContext = $this->createChildContext($this->createOperationContext($context, $className), $attribute, $format);

        foreach ($value as $index => $obj) {
            $currentChildContext = $childContext;
            if (isset($childContext['deserialization_path'])) {
                $currentChildContext['deserialization_path'] = "{$childContext['deserialization_path']}[{$index}]";
            }

            if ($type instanceof CollectionType) {
                $collectionKeyType = $type->getCollectionKeyType();

                while ($collectionKeyType instanceof WrappingTypeInterface) {
                    $collectionKeyType = $type->getWrappedType();
                }

                if (!$collectionKeyType->accepts($index)) {
                    throw NotNormalizableValueException::createForUnexpectedDataType(\sprintf('The type of the key "%s" must be "%s", "%s" given.', $index, $type->getCollectionKeyType(), \gettype($index)), $index, [(string) $collectionKeyType], ($context['deserialization_path'] ?? false) ? \sprintf('key(%s)', $context['deserialization_path']) : null, true);
                }
            }

            $values[$index] = $this->denormalizeRelation($attribute, $propertyMetadata, $className, $obj, $format, $currentChildContext);
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
                if (!isset($context['not_normalizable_value_exceptions'])) {
                    throw new UnexpectedValueException($e->getMessage(), $e->getCode(), $e);
                }

                throw NotNormalizableValueException::createForUnexpectedDataType($e->getMessage(), $value, [$className], $context['deserialization_path'] ?? null, true, $e->getCode(), $e);
            } catch (InvalidArgumentException $e) {
                if (!isset($context['not_normalizable_value_exceptions'])) {
                    throw new UnexpectedValueException(\sprintf('Invalid IRI "%s".', $value), $e->getCode(), $e);
                }

                throw NotNormalizableValueException::createForUnexpectedDataType(\sprintf('Invalid IRI "%s".', $value), $value, [$className], $context['deserialization_path'] ?? null, true, $e->getCode(), $e);
            }
        }

        if ($propertyMetadata->isWritableLink()) {
            $context['api_allow_update'] = true;

            if (!$this->serializer instanceof DenormalizerInterface) {
                throw new LogicException(\sprintf('The injected serializer must be an instance of "%s".', DenormalizerInterface::class));
            }

            $item = $this->serializer->denormalize($value, $className, $format, $context);
            if (!\is_object($item) && null !== $item) {
                throw new \UnexpectedValueException('Expected item to be an object or null.');
            }

            return $item;
        }

        if (!\is_array($value)) {
            throw NotNormalizableValueException::createForUnexpectedDataType(\sprintf('The type of the "%s" attribute must be "array" (nested document) or "string" (IRI), "%s" given.', $attributeName, \gettype($value)), $value, ['array', 'string'], $context['deserialization_path'] ?? null, true);
        }

        throw NotNormalizableValueException::createForUnexpectedDataType(\sprintf('Nested documents for attribute "%s" are not allowed. Use IRIs instead.', $attributeName), $value, ['array', 'string'], $context['deserialization_path'] ?? null, true);
    }

    /**
     * Gets the options for the property name collection / property metadata factories.
     */
    protected function getFactoryOptions(array $context): array
    {
        $options = ['api_allow_update' => $context['api_allow_update'] ?? false];
        if (isset($context[self::GROUPS])) {
            /* @see https://github.com/symfony/symfony/blob/v4.2.6/src/Symfony/Component/PropertyInfo/Extractor/SerializerExtractor.php */
            $options['serializer_groups'] = (array) $context[self::GROUPS];
        }

        $operationCacheKey = ($context['resource_class'] ?? '').($context['operation_name'] ?? '').($context['root_operation_name'] ?? '');
        $suffix = ($context['api_normalize'] ?? '') ? 'n' : '';
        if ($operationCacheKey && isset($this->localFactoryOptionsCache[$operationCacheKey.$suffix])) {
            return $options + $this->localFactoryOptionsCache[$operationCacheKey.$suffix];
        }

        // This is a hot spot
        if (isset($context['resource_class'])) {
            // Note that the groups need to be read on the root operation
            if ($operation = ($context['root_operation'] ?? null)) {
                $options['normalization_groups'] = $operation->getNormalizationContext()['groups'] ?? null;
                $options['denormalization_groups'] = $operation->getDenormalizationContext()['groups'] ?? null;
                $options['operation_name'] = $operation->getName();
            }
        }

        return $options + $this->localFactoryOptionsCache[$operationCacheKey.$suffix] = $options;
    }

    /**
     * {@inheritdoc}
     *
     * @throws UnexpectedValueException
     */
    protected function getAttributeValue(object $object, string $attribute, ?string $format = null, array $context = []): mixed
    {
        $context['api_attribute'] = $attribute;
        $context['property_metadata'] = $propertyMetadata = $this->propertyMetadataFactory->create($context['resource_class'], $attribute, $this->getFactoryOptions($context));

        if ($context['api_denormalize'] ?? false) {
            return $this->propertyAccessor->getValue($object, $attribute);
        }

        if (!method_exists(PropertyInfoExtractor::class, 'getType')) {
            $types = $propertyMetadata->getBuiltinTypes() ?? [];

            foreach ($types as $type) {
                if (
                    $type->isCollection()
                    && ($collectionValueType = $type->getCollectionValueTypes()[0] ?? null)
                    && ($className = $collectionValueType->getClassName())
                    && $this->resourceClassResolver->isResourceClass($className)
                ) {
                    $childContext = $this->createChildContext($this->createOperationContext($context, $className, $propertyMetadata), $attribute, $format);

                    // @see ApiPlatform\Hal\Serializer\ItemNormalizer:getComponents logic for intentional duplicate content
                    // @see ApiPlatform\JsonApi\Serializer\ItemNormalizer:getComponents logic for intentional duplicate content
                    if ('jsonld' === $format && $itemUriTemplate = $propertyMetadata->getUriTemplate()) {
                        $operation = $this->resourceMetadataCollectionFactory->create($className)->getOperation(
                            operationName: $itemUriTemplate,
                            forceCollection: true,
                            httpOperation: true
                        );

                        return $this->iriConverter->getIriFromResource($object, UrlGeneratorInterface::ABS_PATH, $operation, $childContext);
                    }

                    $attributeValue = $this->propertyAccessor->getValue($object, $attribute);

                    if (!is_iterable($attributeValue)) {
                        throw new UnexpectedValueException('Unexpected non-iterable value for to-many relation.');
                    }

                    $resourceClass = $this->resourceClassResolver->getResourceClass($attributeValue, $className);

                    $data = $this->normalizeCollectionOfRelations($propertyMetadata, $attributeValue, $resourceClass, $format, $childContext);
                    $context['data'] = $data;
                    $context['type'] = $type;

                    if ($this->tagCollector) {
                        $this->tagCollector->collect($context);
                    }

                    return $data;
                }

                if (
                    ($className = $type->getClassName())
                    && $this->resourceClassResolver->isResourceClass($className)
                ) {
                    $childContext = $this->createChildContext($this->createOperationContext($context, $className, $propertyMetadata), $attribute, $format);
                    unset($childContext['iri'], $childContext['uri_variables'], $childContext['item_uri_template']);
                    if ('jsonld' === $format && $uriTemplate = $propertyMetadata->getUriTemplate()) {
                        $operation = $this->resourceMetadataCollectionFactory->create($className)->getOperation(
                            operationName: $uriTemplate,
                            httpOperation: true
                        );

                        return $this->iriConverter->getIriFromResource($object, UrlGeneratorInterface::ABS_PATH, $operation, $childContext);
                    }

                    $attributeValue = $this->propertyAccessor->getValue($object, $attribute);

                    if (!\is_object($attributeValue) && null !== $attributeValue) {
                        throw new UnexpectedValueException('Unexpected non-object value for to-one relation.');
                    }

                    $resourceClass = $this->resourceClassResolver->getResourceClass($attributeValue, $className);

                    $data = $this->normalizeRelation($propertyMetadata, $attributeValue, $resourceClass, $format, $childContext);
                    $context['data'] = $data;
                    $context['type'] = $type;

                    if ($this->tagCollector) {
                        $this->tagCollector->collect($context);
                    }

                    return $data;
                }

                if (!$this->serializer instanceof NormalizerInterface) {
                    throw new LogicException(\sprintf('The injected serializer must be an instance of "%s".', NormalizerInterface::class));
                }

                unset(
                    $context['resource_class'],
                    $context['force_resource_class'],
                    $context['uri_variables'],
                );

                // Anonymous resources
                if ($className) {
                    $childContext = $this->createChildContext($this->createOperationContext($context, $className, $propertyMetadata), $attribute, $format);
                    $attributeValue = $this->propertyAccessor->getValue($object, $attribute);

                    return $this->serializer->normalize($attributeValue, $format, $childContext);
                }

                if ('array' === $type->getBuiltinType()) {
                    if ($className = ($type->getCollectionValueTypes()[0] ?? null)?->getClassName()) {
                        $context = $this->createOperationContext($context, $className, $propertyMetadata);
                    }

                    $childContext = $this->createChildContext($context, $attribute, $format);
                    $childContext['output']['gen_id'] ??= $propertyMetadata->getGenId() ?? true;

                    $attributeValue = $this->propertyAccessor->getValue($object, $attribute);

                    return $this->serializer->normalize($attributeValue, $format, $childContext);
                }
            }

            if (!$this->serializer instanceof NormalizerInterface) {
                throw new LogicException(\sprintf('The injected serializer must be an instance of "%s".', NormalizerInterface::class));
            }

            unset(
                $context['resource_class'],
                $context['force_resource_class'],
                $context['uri_variables']
            );

            $attributeValue = $this->propertyAccessor->getValue($object, $attribute);

            return $this->serializer->normalize($attributeValue, $format, $context);
        }

        $type = $propertyMetadata->getNativeType();

        $nullable = false;
        if ($type instanceof NullableType) {
            $type = $type->getWrappedType();
            $nullable = true;
        }

        // TODO check every foreach composite to see if null is an issue
        $types = $type instanceof CompositeTypeInterface ? $type->getTypes() : (null === $type ? [] : [$type]);
        $className = null;
        $typeIsResourceClass = function (Type $type) use (&$className): bool {
            return $type instanceof ObjectType && $this->resourceClassResolver->isResourceClass($className = $type->getClassName());
        };

        foreach ($types as $type) {
            if ($type instanceof CollectionType && $type->getCollectionValueType()->isSatisfiedBy($typeIsResourceClass)) {
                $childContext = $this->createChildContext($this->createOperationContext($context, $className, $propertyMetadata), $attribute, $format);

                // @see ApiPlatform\Hal\Serializer\ItemNormalizer:getComponents logic for intentional duplicate content
                // @see ApiPlatform\JsonApi\Serializer\ItemNormalizer:getComponents logic for intentional duplicate content
                if ('jsonld' === $format && $itemUriTemplate = $propertyMetadata->getUriTemplate()) {
                    $operation = $this->resourceMetadataCollectionFactory->create($className)->getOperation(
                        operationName: $itemUriTemplate,
                        forceCollection: true,
                        httpOperation: true
                    );

                    return $this->iriConverter->getIriFromResource($object, UrlGeneratorInterface::ABS_PATH, $operation, $childContext);
                }

                $attributeValue = $this->propertyAccessor->getValue($object, $attribute);

                if (!is_iterable($attributeValue)) {
                    throw new UnexpectedValueException('Unexpected non-iterable value for to-many relation.');
                }

                $resourceClass = $this->resourceClassResolver->getResourceClass($attributeValue, $className);

                $data = $this->normalizeCollectionOfRelations($propertyMetadata, $attributeValue, $resourceClass, $format, $childContext);
                $context['data'] = $data;
                $context['type'] = $nullable ? Type::nullable($type) : $type;

                if ($this->tagCollector) {
                    $this->tagCollector->collect($context);
                }

                return $data;
            }

            if ($type->isSatisfiedBy($typeIsResourceClass)) {
                $childContext = $this->createChildContext($this->createOperationContext($context, $className, $propertyMetadata), $attribute, $format);
                unset($childContext['iri'], $childContext['uri_variables'], $childContext['item_uri_template']);
                if ('jsonld' === $format && $uriTemplate = $propertyMetadata->getUriTemplate()) {
                    $operation = $this->resourceMetadataCollectionFactory->create($className)->getOperation(
                        operationName: $uriTemplate,
                        httpOperation: true
                    );

                    return $this->iriConverter->getIriFromResource($object, UrlGeneratorInterface::ABS_PATH, $operation, $childContext);
                }

                $attributeValue = $this->propertyAccessor->getValue($object, $attribute);

                if (!\is_object($attributeValue) && null !== $attributeValue) {
                    throw new UnexpectedValueException('Unexpected non-object value for to-one relation.');
                }

                $resourceClass = $this->resourceClassResolver->getResourceClass($attributeValue, $className);

                $data = $this->normalizeRelation($propertyMetadata, $attributeValue, $resourceClass, $format, $childContext);
                $context['data'] = $data;
                $context['type'] = $nullable ? Type::nullable($type) : $type;

                if ($this->tagCollector) {
                    $this->tagCollector->collect($context);
                }

                return $data;
            }

            if (!$this->serializer instanceof NormalizerInterface) {
                throw new LogicException(\sprintf('The injected serializer must be an instance of "%s".', NormalizerInterface::class));
            }

            unset(
                $context['resource_class'],
                $context['force_resource_class'],
                $context['uri_variables'],
            );

            // Anonymous resources
            if ($className) {
                $childContext = $this->createChildContext($this->createOperationContext($context, $className, $propertyMetadata), $attribute, $format);
                $attributeValue = $this->propertyAccessor->getValue($object, $attribute);

                return $this->serializer->normalize($attributeValue, $format, $childContext);
            }

            if ($type instanceof CollectionType) {
                if (($subType = $type->getCollectionValueType()) instanceof ObjectType) {
                    $context = $this->createOperationContext($context, $subType->getClassName(), $propertyMetadata);
                }

                $childContext = $this->createChildContext($context, $attribute, $format);
                $attributeValue = $this->propertyAccessor->getValue($object, $attribute);

                return $this->serializer->normalize($attributeValue, $format, $childContext);
            }
        }

        if (!$this->serializer instanceof NormalizerInterface) {
            throw new LogicException(\sprintf('The injected serializer must be an instance of "%s".', NormalizerInterface::class));
        }

        unset(
            $context['resource_class'],
            $context['force_resource_class'],
            $context['uri_variables']
        );

        $attributeValue = $this->propertyAccessor->getValue($object, $attribute);

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
        if (null === $relatedObject || !empty($context['attributes']) || $propertyMetadata->isReadableLink() || false === ($context['output']['gen_id'] ?? true)) {
            if (!$this->serializer instanceof NormalizerInterface) {
                throw new LogicException(\sprintf('The injected serializer must be an instance of "%s".', NormalizerInterface::class));
            }

            $relatedContext = $this->createOperationContext($context, $resourceClass, $propertyMetadata);
            $normalizedRelatedObject = $this->serializer->normalize($relatedObject, $format, $relatedContext);
            if (!\is_string($normalizedRelatedObject) && !\is_array($normalizedRelatedObject) && !$normalizedRelatedObject instanceof \ArrayObject && null !== $normalizedRelatedObject) {
                throw new UnexpectedValueException('Expected normalized relation to be an IRI, array, \ArrayObject or null');
            }

            return $normalizedRelatedObject;
        }

        $context['iri'] = $iri = $this->iriConverter->getIriFromResource(resource: $relatedObject, context: $context);
        $context['data'] = $iri;
        $context['object'] = $relatedObject;
        unset($context['property_metadata'], $context['api_attribute']);

        if ($this->tagCollector) {
            $this->tagCollector->collect($context);
        } elseif (isset($context['resources'])) {
            $context['resources'][$iri] = $iri;
        }

        $push = $propertyMetadata->getPush() ?? false;
        if (isset($context['resources_to_push']) && $push) {
            $context['resources_to_push'][$iri] = $iri;
        }

        return $iri;
    }

    private function createAttributeValue(string $attribute, mixed $value, ?string $format = null, array &$context = []): mixed
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

    private function createAndValidateAttributeValue(string $attribute, mixed $value, ?string $format = null, array $context = []): mixed
    {
        $propertyMetadata = $this->propertyMetadataFactory->create($context['resource_class'], $attribute, $this->getFactoryOptions($context));

        $type = null;
        if (!method_exists(PropertyInfoExtractor::class, 'getType')) {
            $types = $propertyMetadata->getBuiltinTypes() ?? [];
        } else {
            $type = $propertyMetadata->getNativeType();
            $types = $type instanceof CompositeTypeInterface ? $type->getTypes() : (null === $type ? [] : [$type]);
        }

        $className = null;
        $typeIsResourceClass = function (Type $type) use (&$className): bool {
            return $type instanceof ObjectType ? $this->resourceClassResolver->isResourceClass($className = $type->getClassName()) : false;
        };

        $isMultipleTypes = \count($types) > 1;
        $denormalizationException = null;

        foreach ($types as $t) {
            if ($type instanceof Type) {
                $isNullable = $type->isNullable();
            } else {
                $isNullable = $t->isNullable();
            }

            if (null === $value && ($isNullable || ($context[static::DISABLE_TYPE_ENFORCEMENT] ?? false))) {
                return $value;
            }

            $collectionValueType = null;

            if ($t instanceof CollectionType) {
                $collectionValueType = $t->getCollectionValueType();
            } elseif ($t instanceof LegacyType) {
                $collectionValueType = $t->getCollectionValueTypes()[0] ?? null;
            }

            /* From @see AbstractObjectNormalizer::validateAndDenormalize() */
            // Fix a collection that contains the only one element
            // This is special to xml format only
            if ('xml' === $format && null !== $collectionValueType && (!\is_array($value) || !\is_int(key($value)))) {
                $isMixedType = $collectionValueType instanceof Type && $collectionValueType->isIdentifiedBy(TypeIdentifier::MIXED);
                if (!$isMixedType) {
                    $value = [$value];
                }
            }

            if (($collectionValueType instanceof Type && $collectionValueType->isSatisfiedBy($typeIsResourceClass))
                || ($t instanceof LegacyType && $t->isCollection() && null !== $collectionValueType && null !== ($className = $collectionValueType->getClassName()) && $this->resourceClassResolver->isResourceClass($className))
            ) {
                $resourceClass = $this->resourceClassResolver->getResourceClass(null, $className);
                $context['resource_class'] = $resourceClass;
                unset($context['uri_variables']);

                try {
                    return $t instanceof Type
                        ? $this->denormalizeObjectCollection($attribute, $propertyMetadata, $t, $resourceClass, $value, $format, $context)
                        : $this->denormalizeCollection($attribute, $propertyMetadata, $t, $resourceClass, $value, $format, $context);
                } catch (NotNormalizableValueException $e) {
                    // union/intersect types: try the next type, if not valid, an exception will be thrown at the end
                    if ($isMultipleTypes) {
                        $denormalizationException ??= $e;

                        continue;
                    }

                    throw $e;
                }
            }

            if (
                ($t instanceof Type && $t->isSatisfiedBy($typeIsResourceClass))
                || ($t instanceof LegacyType && null !== ($className = $t->getClassName()) && $this->resourceClassResolver->isResourceClass($className))
            ) {
                $resourceClass = $this->resourceClassResolver->getResourceClass(null, $className);
                $childContext = $this->createChildContext($this->createOperationContext($context, $resourceClass, $propertyMetadata), $attribute, $format);

                try {
                    return $this->denormalizeRelation($attribute, $propertyMetadata, $resourceClass, $value, $format, $childContext);
                } catch (NotNormalizableValueException $e) {
                    // union/intersect types: try the next type, if not valid, an exception will be thrown at the end
                    if ($isMultipleTypes) {
                        $denormalizationException ??= $e;

                        continue;
                    }

                    throw $e;
                }
            }

            if (
                ($t instanceof CollectionType && $collectionValueType instanceof ObjectType)
                || ($t instanceof LegacyType && $t->isCollection() && null !== $collectionValueType && null !== $collectionValueType->getClassName())
            ) {
                $className = $collectionValueType->getClassName();
                if (!$this->serializer instanceof DenormalizerInterface) {
                    throw new LogicException(\sprintf('The injected serializer must be an instance of "%s".', DenormalizerInterface::class));
                }

                unset($context['resource_class'], $context['uri_variables']);

                try {
                    return $this->serializer->denormalize($value, $className.'[]', $format, $context);
                } catch (NotNormalizableValueException $e) {
                    // union/intersect types: try the next type, if not valid, an exception will be thrown at the end
                    if ($isMultipleTypes) {
                        $denormalizationException ??= $e;

                        continue;
                    }

                    throw $e;
                }
            }

            while ($t instanceof WrappingTypeInterface) {
                $t = $t->getWrappedType();
            }

            if (
                $t instanceof ObjectType
                || ($t instanceof LegacyType && null !== $t->getClassName())
            ) {
                if (!$this->serializer instanceof DenormalizerInterface) {
                    throw new LogicException(\sprintf('The injected serializer must be an instance of "%s".', DenormalizerInterface::class));
                }

                unset($context['resource_class'], $context['uri_variables']);

                try {
                    return $this->serializer->denormalize($value, $t->getClassName(), $format, $context);
                } catch (NotNormalizableValueException $e) {
                    // union/intersect types: try the next type, if not valid, an exception will be thrown at the end
                    if ($isMultipleTypes) {
                        $denormalizationException ??= $e;

                        continue;
                    }

                    throw $e;
                }
            }

            /* From @see AbstractObjectNormalizer::validateAndDenormalize() */
            // In XML and CSV all basic datatypes are represented as strings, it is e.g. not possible to determine,
            // if a value is meant to be a string, float, int or a boolean value from the serialized representation.
            // That's why we have to transform the values, if one of these non-string basic datatypes is expected.
            if (\is_string($value) && (XmlEncoder::FORMAT === $format || CsvEncoder::FORMAT === $format)) {
                if ('' === $value && $isNullable && (
                    ($t instanceof Type && $t->isIdentifiedBy(TypeIdentifier::BOOL, TypeIdentifier::INT, TypeIdentifier::FLOAT))
                    || ($t instanceof LegacyType && \in_array($t->getBuiltinType(), [LegacyType::BUILTIN_TYPE_BOOL, LegacyType::BUILTIN_TYPE_INT, LegacyType::BUILTIN_TYPE_FLOAT], true))
                )) {
                    return null;
                }

                $typeIdentifier = $t instanceof BuiltinType ? $t->getTypeIdentifier() : TypeIdentifier::tryFrom($t->getBuiltinType());

                switch ($typeIdentifier) {
                    case TypeIdentifier::BOOL:
                        // according to http://www.w3.org/TR/xmlschema-2/#boolean, valid representations are "false", "true", "0" and "1"
                        if ('false' === $value || '0' === $value) {
                            $value = false;
                        } elseif ('true' === $value || '1' === $value) {
                            $value = true;
                        } else {
                            // union/intersect types: try the next type, if not valid, an exception will be thrown at the end
                            if ($isMultipleTypes) {
                                break 2;
                            }
                            throw NotNormalizableValueException::createForUnexpectedDataType(\sprintf('The type of the "%s" attribute for class "%s" must be bool ("%s" given).', $attribute, $className, $value), $value, ['bool'], $context['deserialization_path'] ?? null);
                        }
                        break;
                    case TypeIdentifier::INT:
                        if (ctype_digit($value) || ('-' === $value[0] && ctype_digit(substr($value, 1)))) {
                            $value = (int) $value;
                        } else {
                            // union/intersect types: try the next type, if not valid, an exception will be thrown at the end
                            if ($isMultipleTypes) {
                                break 2;
                            }
                            throw NotNormalizableValueException::createForUnexpectedDataType(\sprintf('The type of the "%s" attribute for class "%s" must be int ("%s" given).', $attribute, $className, $value), $value, ['int'], $context['deserialization_path'] ?? null);
                        }
                        break;
                    case TypeIdentifier::FLOAT:
                        if (is_numeric($value)) {
                            return (float) $value;
                        }

                        switch ($value) {
                            case 'NaN':
                                return \NAN;
                            case 'INF':
                                return \INF;
                            case '-INF':
                                return -\INF;
                            default:
                                // union/intersect types: try the next type, if not valid, an exception will be thrown at the end
                                if ($isMultipleTypes) {
                                    break 3;
                                }
                                throw NotNormalizableValueException::createForUnexpectedDataType(\sprintf('The type of the "%s" attribute for class "%s" must be float ("%s" given).', $attribute, $className, $value), $value, ['float'], $context['deserialization_path'] ?? null);
                        }
                }
            }

            if ($context[static::DISABLE_TYPE_ENFORCEMENT] ?? false) {
                return $value;
            }

            try {
                $t instanceof Type
                    ? $this->validateAttributeType($attribute, $t, $value, $format, $context)
                    : $this->validateType($attribute, $t, $value, $format, $context);

                $denormalizationException = null;
                break;
            } catch (NotNormalizableValueException $e) {
                // union/intersect types: try the next type
                if (!$isMultipleTypes) {
                    throw $e;
                }

                $denormalizationException ??= $e;
            }
        }

        if ($denormalizationException) {
            if ($type instanceof Type && $type->isSatisfiedBy(static fn ($type) => $type instanceof BuiltinType) && !$type->isSatisfiedBy($typeIsResourceClass)) {
                throw NotNormalizableValueException::createForUnexpectedDataType(\sprintf('The type of the "%s" attribute must be "%s", "%s" given.', $attribute, $type, \gettype($value)), $value, array_map(strval(...), $types), $context['deserialization_path'] ?? null);
            }

            throw $denormalizationException;
        }

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
