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
use ApiPlatform\Api\UrlGeneratorInterface;
use ApiPlatform\Core\Api\IriConverterInterface as LegacyIriConverterInterface;
use ApiPlatform\Core\Bridge\Symfony\Messenger\DataTransformer as MessengerDataTransformer;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataTransformer\DataTransformerInitializerInterface;
use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface as LegacyPropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Exception\InvalidArgumentException;
use ApiPlatform\Exception\InvalidValueException;
use ApiPlatform\Exception\ItemNotFoundException;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Symfony\Security\ResourceAccessCheckerInterface;
use ApiPlatform\Util\ClassInfoTrait;
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

    public const IS_TRANSFORMED_TO_SAME_CLASS = 'is_transformed_to_same_class';

    /**
     * @var PropertyNameCollectionFactoryInterface
     */
    protected $propertyNameCollectionFactory;
    /**
     * @var LegacyPropertyMetadataFactoryInterface|PropertyMetadataFactoryInterface
     */
    protected $propertyMetadataFactory;
    protected $resourceMetadataFactory;
    /**
     * @var LegacyIriConverterInterface|IriConverterInterface
     */
    protected $iriConverter;
    protected $resourceClassResolver;
    protected $resourceAccessChecker;
    protected $propertyAccessor;
    protected $itemDataProvider;
    protected $allowPlainIdentifiers;
    protected $dataTransformers = [];
    protected $localCache = [];

    public function __construct(PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, $propertyMetadataFactory, $iriConverter, $resourceClassResolver, PropertyAccessorInterface $propertyAccessor = null, NameConverterInterface $nameConverter = null, ClassMetadataFactoryInterface $classMetadataFactory = null, ItemDataProviderInterface $itemDataProvider = null, bool $allowPlainIdentifiers = false, array $defaultContext = [], iterable $dataTransformers = [], $resourceMetadataFactory = null, ResourceAccessCheckerInterface $resourceAccessChecker = null)
    {
        if (!isset($defaultContext['circular_reference_handler'])) {
            $defaultContext['circular_reference_handler'] = function ($object) {
                return $this->iriConverter instanceof LegacyIriConverterInterface ? $this->iriConverter->getIriFromItem($object) : $this->iriConverter->getIriFromResource($object);
            };
        }
        if (!interface_exists(AdvancedNameConverterInterface::class) && method_exists($this, 'setCircularReferenceHandler')) {
            $this->setCircularReferenceHandler($defaultContext['circular_reference_handler']);
        }

        parent::__construct($classMetadataFactory, $nameConverter, null, null, \Closure::fromCallable([$this, 'getObjectClass']), $defaultContext);

        $this->propertyNameCollectionFactory = $propertyNameCollectionFactory;
        $this->propertyMetadataFactory = $propertyMetadataFactory;

        if ($iriConverter instanceof LegacyIriConverterInterface) {
            trigger_deprecation('api-platform/core', '2.7', sprintf('Use an implementation of "%s" instead of "%s".', IriConverterInterface::class, LegacyIriConverterInterface::class));
        }

        $this->iriConverter = $iriConverter;
        $this->resourceClassResolver = $resourceClassResolver;
        $this->propertyAccessor = $propertyAccessor ?: PropertyAccess::createPropertyAccessor();
        $this->itemDataProvider = $itemDataProvider;

        if (true === $allowPlainIdentifiers) {
            @trigger_error(sprintf('Allowing plain identifiers as argument of "%s" is deprecated since API Platform 2.7 and will not be possible anymore in API Platform 3.', self::class), \E_USER_DEPRECATED);
        }
        $this->allowPlainIdentifiers = $allowPlainIdentifiers;

        $this->dataTransformers = $dataTransformers;

        // Just skip our data transformer to trigger a proper deprecation
        $customDataTransformers = array_filter(\is_array($dataTransformers) ? $dataTransformers : iterator_to_array($dataTransformers), function ($dataTransformer) {
            return !$dataTransformer instanceof MessengerDataTransformer;
        });

        if (\count($customDataTransformers)) {
            trigger_deprecation('api-platform/core', '2.7', 'The DataTransformer pattern is deprecated, use a Provider or a Processor and either use your input or return a new output there.');
        }

        if ($resourceMetadataFactory && !$resourceMetadataFactory instanceof ResourceMetadataCollectionFactoryInterface) {
            trigger_deprecation('api-platform/core', '2.7', sprintf('Use "%s" instead of "%s".', ResourceMetadataCollectionFactoryInterface::class, ResourceMetadataFactoryInterface::class));
        }

        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->resourceAccessChecker = $resourceAccessChecker;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null, array $context = []): bool
    {
        if (!\is_object($data) || is_iterable($data)) {
            return false;
        }

        $class = $this->getObjectClass($data);
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
     *
     * @return array|string|int|float|bool|\ArrayObject|null
     */
    public function normalize($object, $format = null, array $context = [])
    {
        $resourceClass = $this->getObjectClass($object);
        if (!($isTransformed = isset($context[self::IS_TRANSFORMED_TO_SAME_CLASS])) && $outputClass = $this->getOutputClass($resourceClass, $context)) {
            if (!$this->serializer instanceof NormalizerInterface) {
                throw new LogicException('Cannot normalize the output because the injected serializer is not a normalizer');
            }

            // Data transformers are deprecated, this is removed from 3.0
            if ($dataTransformer = $this->getDataTransformer($object, $outputClass, $context)) {
                $transformed = $dataTransformer->transform($object, $outputClass, $context);

                if ($object === $transformed) {
                    $context[self::IS_TRANSFORMED_TO_SAME_CLASS] = true;
                } else {
                    $context['api_normalize'] = true;
                    $context['api_resource'] = $object;
                    unset($context['output'], $context['resource_class']);
                }

                return $this->serializer->normalize($transformed, $format, $context);
            }

            unset($context['output'], $context['operation_name']);
            if ($this->resourceMetadataFactory instanceof ResourceMetadataCollectionFactoryInterface && !isset($context['operation'])) {
                $context['operation'] = $this->resourceMetadataFactory->create($context['resource_class'])->getOperation();
            }
            $context['resource_class'] = $outputClass;
            $context['api_sub_level'] = true;
            $context[self::ALLOW_EXTRA_ATTRIBUTES] = false;

            return $this->serializer->normalize($object, $format, $context);
        }

        if ($isTransformed) {
            unset($context[self::IS_TRANSFORMED_TO_SAME_CLASS]);
        }

        if ($isResourceClass = $this->resourceClassResolver->isResourceClass($resourceClass)) {
            $context = $this->initContext($resourceClass, $context);
        }

        if (isset($context['operation']) && $context['operation'] instanceof CollectionOperationInterface) {
            unset($context['operation_name']);
            unset($context['operation']);
            unset($context['iri']);
        }

        $iri = null;
        if (isset($context['iri'])) {
            $iri = $context['iri'];
        } elseif ($this->iriConverter instanceof LegacyIriConverterInterface && $isResourceClass) {
            $iri = $this->iriConverter->getIriFromItem($object);
        } elseif ($this->iriConverter instanceof IriConverterInterface) {
            $iri = $this->iriConverter->getIriFromResource($object, UrlGeneratorInterface::ABS_URL, $context['operation'] ?? null, $context);
        }

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
     *
     * @return bool
     */
    public function supportsDenormalization($data, $type, $format = null, array $context = [])
    {
        if (($context['input']['class'] ?? null) === $type) {
            return true;
        }

        return $this->localCache[$type] ?? $this->localCache[$type] = $this->resourceClassResolver->isResourceClass($type);
    }

    /**
     * {@inheritdoc}
     *
     * @return mixed
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        $resourceClass = $class;

        if (null !== $inputClass = $this->getInputClass($resourceClass, $context)) {
            if (null !== $dataTransformer = $this->getDataTransformer($data, $resourceClass, $context)) {
                $dataTransformerContext = $context;

                unset($context['input']);
                unset($context['resource_class']);

                if (!$this->serializer instanceof DenormalizerInterface) {
                    throw new LogicException('Cannot denormalize the input because the injected serializer is not a denormalizer');
                }

                if ($dataTransformer instanceof DataTransformerInitializerInterface) {
                    $context[AbstractObjectNormalizer::OBJECT_TO_POPULATE] = $dataTransformer->initialize($inputClass, $context);
                    $context[AbstractObjectNormalizer::DEEP_OBJECT_TO_POPULATE] = true;
                }

                try {
                    $denormalizedInput = $this->serializer->denormalize($data, $inputClass, $format, $context);
                } catch (NotNormalizableValueException $e) {
                    throw new UnexpectedValueException('The input data is misformatted.', $e->getCode(), $e);
                }

                if (!\is_object($denormalizedInput)) {
                    throw new UnexpectedValueException('Expected denormalized input to be an object.');
                }

                return $dataTransformer->transform($denormalizedInput, $resourceClass, $dataTransformerContext);
            }

            unset($context['input']);
            unset($context['operation']);
            unset($context['operation_name']);
            $context['resource_class'] = $inputClass;

            if (!$this->serializer instanceof DenormalizerInterface) {
                throw new LogicException('Cannot denormalize the input because the injected serializer is not a denormalizer');
            }

            try {
                return $this->serializer->denormalize($data, $inputClass, $format, $context);
            } catch (NotNormalizableValueException $e) {
                throw new UnexpectedValueException('The input data is misformatted.', $e->getCode(), $e);
            }
        }

        if (null === $objectToPopulate = $this->extractObjectToPopulate($class, $context, static::OBJECT_TO_POPULATE)) {
            $normalizedData = \is_scalar($data) ? [$data] : $this->prepareForDenormalization($data);
            $class = $this->getClassDiscriminatorResolvedClass($normalizedData, $class);
        }

        $context['api_denormalize'] = true;

        if ($this->resourceClassResolver->isResourceClass($class)) {
            $resourceClass = $this->resourceClassResolver->getResourceClass($objectToPopulate, $class);
            $context['resource_class'] = $resourceClass;
        }

        $supportsPlainIdentifiers = $this->supportsPlainIdentifiers();

        if (\is_string($data)) {
            try {
                return $this->iriConverter instanceof LegacyIriConverterInterface ? $this->iriConverter->getItemFromIri($data, $context + ['fetch_data' => true]) : $this->iriConverter->getResourceFromIri($data, $context + ['fetch_data' => true]);
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

        $previousObject = null !== $objectToPopulate ? clone $objectToPopulate : null;
        $object = parent::denormalize($data, $resourceClass, $format, $context);

        if (!$this->resourceClassResolver->isResourceClass($context['resource_class'])) {
            return $object;
        }

        // Revert attributes that aren't allowed to be changed after a post-denormalize check
        foreach (array_keys($data) as $attribute) {
            if (!$this->canAccessAttributePostDenormalize($object, $previousObject, $attribute, $context)) {
                if (null !== $previousObject) {
                    $this->setValue($object, $attribute, $this->propertyAccessor->getValue($previousObject, $attribute));
                } else {
                    $propertyMetadata = $this->propertyMetadataFactory->create($resourceClass, $attribute, $this->getFactoryOptions($context));
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
     *
     * @return object
     */
    protected function instantiateObject(array &$data, $class, array &$context, \ReflectionClass $reflectionClass, $allowedAttributes, string $format = null)
    {
        if (null !== $object = $this->extractObjectToPopulate($class, $context, static::OBJECT_TO_POPULATE)) {
            unset($context[static::OBJECT_TO_POPULATE]);

            return $object;
        }

        $class = $this->getClassDiscriminatorResolvedClass($data, $class);
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
                    throw new MissingConstructorArgumentsException(sprintf('Cannot create an instance of %s from serialized data because its constructor requires parameter "%s" to be present.', $class, $constructorParameter->name));
                }
            }

            if ($constructor->isConstructor()) {
                return $reflectionClass->newInstanceArgs($params);
            }

            return $constructor->invokeArgs(null, $params);
        }

        return new $class();
    }

    protected function getClassDiscriminatorResolvedClass(array &$data, string $class): string
    {
        if (null === $this->classDiscriminatorResolver || (null === $mapping = $this->classDiscriminatorResolver->getMappingForClass($class))) {
            return $class;
        }

        if (!isset($data[$mapping->getTypeProperty()])) {
            throw new RuntimeException(sprintf('Type property "%s" not found for the abstract object "%s"', $mapping->getTypeProperty(), $class));
        }

        $type = $data[$mapping->getTypeProperty()];
        if (null === ($mappedClass = $mapping->getClassForType($type))) {
            throw new RuntimeException(sprintf('The type "%s" has no mapped class for the abstract object "%s"', $type, $class));
        }

        return $mappedClass;
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
     *
     * @return string[]
     */
    protected function extractAttributes($object, $format = null, array $context = [])
    {
        return [];
    }

    /**
     * {@inheritdoc}
     *
     * @return array|bool
     */
    protected function getAllowedAttributes($classOrObject, array $context, $attributesAsString = false)
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
     *
     * @return bool
     */
    protected function isAllowedAttribute($classOrObject, $attribute, $format = null, array $context = [])
    {
        if (!parent::isAllowedAttribute($classOrObject, $attribute, $format, $context)) {
            return false;
        }

        return $this->canAccessAttribute(\is_object($classOrObject) ? $classOrObject : null, $attribute, $context);
    }

    /**
     * Check if access to the attribute is granted.
     *
     * @param object $object
     */
    protected function canAccessAttribute($object, string $attribute, array $context = []): bool
    {
        if (!$this->resourceClassResolver->isResourceClass($context['resource_class'])) {
            return true;
        }

        $options = $this->getFactoryOptions($context);
        /** @var PropertyMetadata|ApiProperty */
        $propertyMetadata = $this->propertyMetadataFactory->create($context['resource_class'], $attribute, $options);
        $security = $propertyMetadata instanceof PropertyMetadata ? $propertyMetadata->getAttribute('security') : $propertyMetadata->getSecurity();
        if ($this->resourceAccessChecker && $security) {
            return $this->resourceAccessChecker->isGranted($context['resource_class'], $security, [
                'object' => $object,
            ]);
        }

        return true;
    }

    /**
     * Check if access to the attribute is granted.
     *
     * @param object      $object
     * @param object|null $previousObject
     */
    protected function canAccessAttributePostDenormalize($object, $previousObject, string $attribute, array $context = []): bool
    {
        $options = $this->getFactoryOptions($context);
        /** @var PropertyMetadata|ApiProperty */
        $propertyMetadata = $this->propertyMetadataFactory->create($context['resource_class'], $attribute, $options);
        $security = $propertyMetadata instanceof PropertyMetadata ? $propertyMetadata->getAttribute('security_post_denormalize') : $propertyMetadata->getSecurityPostDenormalize();
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
    protected function setAttributeValue($object, $attribute, $value, $format = null, array $context = [])
    {
        $this->setValue($object, $attribute, $this->createAttributeValue($attribute, $value, $format, $context));
    }

    /**
     * Validates the type of the value. Allows using integers as floats for JSON formats.
     *
     * @param mixed $value
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
            throw new UnexpectedValueException(sprintf('The type of the "%s" attribute must be "%s", "%s" given.', $attribute, $builtinType, \gettype($value)));
        }
    }

    /**
     * Denormalizes a collection of objects.
     *
     * @param ApiProperty|PropertyMetadata $propertyMetadata
     * @param mixed                        $value
     *
     * @throws InvalidArgumentException
     */
    protected function denormalizeCollection(string $attribute, $propertyMetadata, Type $type, string $className, $value, ?string $format, array $context): array
    {
        if (!\is_array($value)) {
            throw new InvalidArgumentException(sprintf('The type of the "%s" attribute must be "array", "%s" given.', $attribute, \gettype($value)));
        }

        $collectionKeyType = method_exists(Type::class, 'getCollectionKeyTypes') ? ($type->getCollectionKeyTypes()[0] ?? null) : $type->getCollectionKeyType();
        $collectionKeyBuiltinType = null === $collectionKeyType ? null : $collectionKeyType->getBuiltinType();

        $values = [];
        foreach ($value as $index => $obj) {
            if (null !== $collectionKeyBuiltinType && !\call_user_func('is_'.$collectionKeyBuiltinType, $index)) {
                throw new InvalidArgumentException(sprintf('The type of the key "%s" must be "%s", "%s" given.', $index, $collectionKeyBuiltinType, \gettype($index)));
            }

            $values[$index] = $this->denormalizeRelation($attribute, $propertyMetadata, $className, $obj, $format, $this->createChildContext($context, $attribute, $format));
        }

        return $values;
    }

    /**
     * Denormalizes a relation.
     *
     * @param ApiProperty|PropertyMetadata $propertyMetadata
     * @param mixed                        $value
     *
     * @throws LogicException
     * @throws UnexpectedValueException
     * @throws ItemNotFoundException
     *
     * @return object|null
     */
    protected function denormalizeRelation(string $attributeName, $propertyMetadata, string $className, $value, ?string $format, array $context)
    {
        $supportsPlainIdentifiers = $this->supportsPlainIdentifiers();

        if (\is_string($value)) {
            try {
                return $this->iriConverter instanceof LegacyIriConverterInterface ? $this->iriConverter->getItemFromIri($value, $context + ['fetch_data' => true]) : $this->iriConverter->getResourceFromIri($value, $context + ['fetch_data' => true]);
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
                $item = $this->serializer->denormalize($value, $className, $format, $context);
                if (!\is_object($item) && null !== $item) {
                    throw new \UnexpectedValueException('Expected item to be an object or null.');
                }

                return $item;
            } catch (InvalidValueException $e) {
                if (!$supportsPlainIdentifiers) {
                    throw $e;
                }
            }
        }

        if (!\is_array($value)) {
            if (!$supportsPlainIdentifiers) {
                throw new UnexpectedValueException(sprintf('Expected IRI or nested document for attribute "%s", "%s" given.', $attributeName, \gettype($value)));
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
     * Gets the options for the property name collection / property metadata factories.
     */
    protected function getFactoryOptions(array $context): array
    {
        $options = [];

        if (isset($context[self::GROUPS])) {
            /* @see https://github.com/symfony/symfony/blob/v4.2.6/src/Symfony/Component/PropertyInfo/Extractor/SerializerExtractor.php */
            $options['serializer_groups'] = (array) $context[self::GROUPS];
        }

        if (isset($context['resource_class']) && $this->resourceClassResolver->isResourceClass($context['resource_class']) && $this->resourceMetadataFactory instanceof ResourceMetadataCollectionFactoryInterface) {
            $resourceClass = $this->resourceClassResolver->getResourceClass(null, $context['resource_class']); // fix for abstract classes and interfaces
            // This is a hot spot, we should avoid calling this here but in many cases we can't
            $operation = $context['root_operation'] ?? $context['operation'] ?? $this->resourceMetadataFactory->create($resourceClass)->getOperation($context['root_operation_name'] ?? $context['operation_name'] ?? null);
            $options['normalization_groups'] = $operation->getNormalizationContext()['groups'] ?? null;
            $options['denormalization_groups'] = $operation->getDenormalizationContext()['groups'] ?? null;
        }

        if (isset($context['operation_name'])) {
            $options['operation_name'] = $context['operation_name'];
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
        @trigger_error(sprintf('The method %s() is deprecated since 2.1 and will be removed in 3.0.', __METHOD__), \E_USER_DEPRECATED);

        return $context;
    }

    /**
     * {@inheritdoc}
     *
     * @throws UnexpectedValueException
     * @throws LogicException
     *
     * @return mixed
     */
    protected function getAttributeValue($object, $attribute, $format = null, array $context = [])
    {
        $context['api_attribute'] = $attribute;
        /** @var ApiProperty|PropertyMetadata */
        $propertyMetadata = $this->propertyMetadataFactory->create($context['resource_class'], $attribute, $this->getFactoryOptions($context));

        try {
            $attributeValue = $this->propertyAccessor->getValue($object, $attribute);
        } catch (NoSuchPropertyException $e) {
            // BC to be removed in 3.0
            if ($propertyMetadata instanceof PropertyMetadata && !$propertyMetadata->hasChildInherited()) {
                throw $e;
            }
            if ($propertyMetadata instanceof ApiProperty) {
                throw $e;
            }

            $attributeValue = null;
        }

        if ($context['api_denormalize'] ?? false) {
            return $attributeValue;
        }

        $type = $propertyMetadata instanceof PropertyMetadata ? $propertyMetadata->getType() : ($propertyMetadata->getBuiltinTypes()[0] ?? null);

        if (
            $type &&
            $type->isCollection() &&
            ($collectionValueType = method_exists(Type::class, 'getCollectionValueTypes') ? ($type->getCollectionValueTypes()[0] ?? null) : $type->getCollectionValueType()) &&
            ($className = $collectionValueType->getClassName()) &&
            $this->resourceClassResolver->isResourceClass($className)
        ) {
            if (!is_iterable($attributeValue)) {
                throw new UnexpectedValueException('Unexpected non-iterable value for to-many relation.');
            }

            $resourceClass = $this->resourceClassResolver->getResourceClass($attributeValue, $className);
            $childContext = $this->createChildContext($context, $attribute, $format);
            $childContext['resource_class'] = $resourceClass;
            if ($this->resourceMetadataFactory instanceof ResourceMetadataCollectionFactoryInterface) {
                $childContext['operation'] = $this->resourceMetadataFactory->create($resourceClass)->getOperation();
            }
            unset($childContext['iri'], $childContext['uri_variables']);

            return $this->normalizeCollectionOfRelations($propertyMetadata, $attributeValue, $resourceClass, $format, $childContext);
        }

        if (
            $type &&
            ($className = $type->getClassName()) &&
            $this->resourceClassResolver->isResourceClass($className)
        ) {
            if (!\is_object($attributeValue) && null !== $attributeValue) {
                throw new UnexpectedValueException('Unexpected non-object value for to-one relation.');
            }

            $resourceClass = $this->resourceClassResolver->getResourceClass($attributeValue, $className);
            $childContext = $this->createChildContext($context, $attribute, $format);
            $childContext['resource_class'] = $resourceClass;
            if ($this->resourceMetadataFactory instanceof ResourceMetadataCollectionFactoryInterface) {
                $childContext['operation'] = $this->resourceMetadataFactory->create($resourceClass)->getOperation();
            }
            unset($childContext['iri'], $childContext['uri_variables']);

            return $this->normalizeRelation($propertyMetadata, $attributeValue, $resourceClass, $format, $childContext);
        }

        if (!$this->serializer instanceof NormalizerInterface) {
            throw new LogicException(sprintf('The injected serializer must be an instance of "%s".', NormalizerInterface::class));
        }

        unset($context['resource_class']);

        if ($type && $type->getClassName()) {
            $childContext = $this->createChildContext($context, $attribute, $format);
            unset($childContext['iri'], $childContext['uri_variables']);

            if ($propertyMetadata instanceof PropertyMetadata) {
                $childContext['output']['iri'] = $propertyMetadata->getIri() ?? false;
            } else {
                $childContext['output']['gen_id'] = $propertyMetadata->getGenId() ?? false;
            }

            return $this->serializer->normalize($attributeValue, $format, $childContext);
        }

        return $this->serializer->normalize($attributeValue, $format, $context);
    }

    /**
     * Normalizes a collection of relations (to-many).
     *
     * @param ApiProperty|PropertyMetadata $propertyMetadata
     * @param iterable                     $attributeValue
     *
     * @throws UnexpectedValueException
     */
    protected function normalizeCollectionOfRelations($propertyMetadata, $attributeValue, string $resourceClass, ?string $format, array $context): array
    {
        $value = [];
        foreach ($attributeValue as $index => $obj) {
            if (!\is_object($obj) && null !== $obj) {
                throw new UnexpectedValueException('Unexpected non-object element in to-many relation.');
            }

            $value[$index] = $this->normalizeRelation($propertyMetadata, $obj, $resourceClass, $format, $context);
        }

        return $value;
    }

    /**
     * Normalizes a relation.
     *
     * @param ApiProperty|PropertyMetadata $propertyMetadata
     * @param object|null                  $relatedObject
     *
     * @throws LogicException
     * @throws UnexpectedValueException
     *
     * @return string|array|\ArrayObject|null IRI or normalized object data
     */
    protected function normalizeRelation($propertyMetadata, $relatedObject, string $resourceClass, ?string $format, array $context)
    {
        if (null === $relatedObject || !empty($context['attributes']) || $propertyMetadata->isReadableLink()) {
            if (!$this->serializer instanceof NormalizerInterface) {
                throw new LogicException(sprintf('The injected serializer must be an instance of "%s".', NormalizerInterface::class));
            }

            $normalizedRelatedObject = $this->serializer->normalize($relatedObject, $format, $context);
            if (!\is_string($normalizedRelatedObject) && !\is_array($normalizedRelatedObject) && !$normalizedRelatedObject instanceof \ArrayObject && null !== $normalizedRelatedObject) {
                throw new UnexpectedValueException('Expected normalized relation to be an IRI, array, \ArrayObject or null');
            }

            return $normalizedRelatedObject;
        }

        $iri = $this->iriConverter instanceof LegacyIriConverterInterface ? $this->iriConverter->getIriFromItem($relatedObject) : $this->iriConverter->getIriFromResource($relatedObject);

        if (isset($context['resources'])) {
            $context['resources'][$iri] = $iri;
        }

        $push = $propertyMetadata instanceof PropertyMetadata ? $propertyMetadata->getAttribute('push', false) : ($propertyMetadata->getPush() ?? false);
        if (isset($context['resources_to_push']) && $push) {
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
     *
     * @param mixed $object
     */
    protected function transformOutput($object, array $context = [], string $outputClass = null)
    {
    }

    private function createAttributeValue($attribute, $value, $format = null, array $context = [])
    {
        if (!$this->resourceClassResolver->isResourceClass($context['resource_class'])) {
            return $value;
        }

        /** @var ApiProperty|PropertyMetadata */
        $propertyMetadata = $this->propertyMetadataFactory->create($context['resource_class'], $attribute, $this->getFactoryOptions($context));
        $type = $propertyMetadata instanceof PropertyMetadata ? $propertyMetadata->getType() : ($propertyMetadata->getBuiltinTypes()[0] ?? null);

        if (null === $type) {
            // No type provided, blindly return the value
            return $value;
        }

        if (null === $value && $type->isNullable()) {
            return $value;
        }

        $collectionValueType = method_exists(Type::class, 'getCollectionValueTypes') ? ($type->getCollectionValueTypes()[0] ?? null) : $type->getCollectionValueType();

        /* From @see AbstractObjectNormalizer::validateAndDenormalize() */
        // Fix a collection that contains the only one element
        // This is special to xml format only
        if ('xml' === $format && null !== $collectionValueType && (!\is_array($value) || !\is_int(key($value)))) {
            $value = [$value];
        }

        if (
            $type->isCollection() &&
            null !== $collectionValueType &&
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
            if ($this->resourceMetadataFactory instanceof ResourceMetadataCollectionFactoryInterface) {
                $childContext['operation'] = $this->resourceMetadataFactory->create($resourceClass)->getOperation();
            }

            return $this->denormalizeRelation($attribute, $propertyMetadata, $resourceClass, $value, $format, $childContext);
        }

        if (
            $type->isCollection() &&
            null !== $collectionValueType &&
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
                    // according to https://www.w3.org/TR/xmlschema-2/#boolean, valid representations are "false", "true", "0" and "1"
                    if ('false' === $value || '0' === $value) {
                        $value = false;
                    } elseif ('true' === $value || '1' === $value) {
                        $value = true;
                    } else {
                        throw new NotNormalizableValueException(sprintf('The type of the "%s" attribute for class "%s" must be bool ("%s" given).', $attribute, $className, $value));
                    }
                    break;
                case Type::BUILTIN_TYPE_INT:
                    if (ctype_digit($value) || ('-' === $value[0] && ctype_digit(substr($value, 1)))) {
                        $value = (int) $value;
                    } else {
                        throw new NotNormalizableValueException(sprintf('The type of the "%s" attribute for class "%s" must be int ("%s" given).', $attribute, $className, $value));
                    }
                    break;
                case Type::BUILTIN_TYPE_FLOAT:
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
                            throw new NotNormalizableValueException(sprintf('The type of the "%s" attribute for class "%s" must be float ("%s" given).', $attribute, $className, $value));
                    }
            }
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
     * @param mixed  $value
     */
    private function setValue($object, string $attributeName, $value)
    {
        try {
            $this->propertyAccessor->setValue($object, $attributeName, $value);
        } catch (NoSuchPropertyException $exception) {
            // Properties not found are ignored
        }
    }

    /**
     * TODO: to remove in 3.0.
     *
     * @deprecated since 2.7
     */
    private function supportsPlainIdentifiers(): bool
    {
        return $this->allowPlainIdentifiers && null !== $this->itemDataProvider;
    }
}

class_alias(AbstractItemNormalizer::class, \ApiPlatform\Core\Serializer\AbstractItemNormalizer::class);
