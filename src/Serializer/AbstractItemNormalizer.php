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
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Exception\ItemNotFoundException;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

/**
 * Base item normalizer.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
abstract class AbstractItemNormalizer extends AbstractObjectNormalizer
{
    use ContextTrait;

    protected $propertyNameCollectionFactory;
    protected $propertyMetadataFactory;
    protected $iriConverter;
    protected $resourceClassResolver;
    protected $propertyAccessor;

    public function __construct(PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, IriConverterInterface $iriConverter, ResourceClassResolverInterface $resourceClassResolver, PropertyAccessorInterface $propertyAccessor = null, NameConverterInterface $nameConverter = null, ClassMetadataFactoryInterface $classMetadataFactory = null)
    {
        parent::__construct($classMetadataFactory, $nameConverter);

        $this->propertyNameCollectionFactory = $propertyNameCollectionFactory;
        $this->propertyMetadataFactory = $propertyMetadataFactory;
        $this->iriConverter = $iriConverter;
        $this->resourceClassResolver = $resourceClassResolver;
        $this->propertyAccessor = $propertyAccessor ?: PropertyAccess::createPropertyAccessor();

        $this->setCircularReferenceHandler(function ($object) {
            return $this->iriConverter->getIriFromItem($object);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        if (!is_object($data)) {
            return false;
        }

        try {
            $this->resourceClassResolver->getResourceClass($data);
        } catch (InvalidArgumentException $e) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        $resourceClass = $this->resourceClassResolver->getResourceClass($object, $context['resource_class'] ?? null, true);
        $context = $this->initContext($resourceClass, $context);
        $context['api_normalize'] = true;

        return parent::normalize($object, $format, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return $this->resourceClassResolver->isResourceClass($type);
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        $context['api_denormalize'] = true;
        if (!isset($context['resource_class'])) {
            $context['resource_class'] = $class;
        }

        return parent::denormalize($data, $class, $format, $context);
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
                (isset($context['api_normalize']) && $propertyMetadata->isReadable()) ||
                (isset($context['api_denormalize']) && $propertyMetadata->isWritable())
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
        $propertyMetadata = $this->propertyMetadataFactory->create($context['resource_class'], $attribute, $this->getFactoryOptions($context));
        $type = $propertyMetadata->getType();

        if (null === $type) {
            // No type provided, blindly set the value
            $this->setValue($object, $attribute, $value);

            return;
        }

        if (null === $value && $type->isNullable()) {
            $this->setValue($object, $attribute, $value);

            return;
        }

        if (
            $type->isCollection() &&
            null !== ($collectionValueType = $type->getCollectionValueType()) &&
            null !== $className = $collectionValueType->getClassName()
        ) {
            $this->setValue(
                $object,
                $attribute,
                $this->denormalizeCollection($attribute, $propertyMetadata, $type, $className, $value, $format, $context)
            );

            return;
        }

        if (null !== $className = $type->getClassName()) {
            $this->setValue(
                $object,
                $attribute,
                $this->denormalizeRelation($attribute, $propertyMetadata, $className, $value, $format, $context)
            );

            return;
        }

        $this->validateType($attribute, $type, $value, $format);
        $this->setValue($object, $attribute, $value);
    }

    /**
     * Validates the type of the value. Allows using integers as floats for JSON formats.
     *
     * @param string      $attribute
     * @param Type        $type
     * @param mixed       $value
     * @param string|null $format
     *
     * @throws InvalidArgumentException
     */
    protected function validateType(string $attribute, Type $type, $value, string $format = null)
    {
        $builtinType = $type->getBuiltinType();
        if (Type::BUILTIN_TYPE_FLOAT === $builtinType && null !== $format && false !== strpos($format, 'json')) {
            $isValid = is_float($value) || is_int($value);
        } else {
            $isValid = call_user_func('is_'.$builtinType, $value);
        }

        if (!$isValid) {
            throw new InvalidArgumentException(sprintf(
                'The type of the "%s" attribute must be "%s", "%s" given.', $attribute, $builtinType, gettype($value)
            ));
        }
    }

    /**
     * Denormalizes a collection of objects.
     *
     * @param string           $attribute
     * @param PropertyMetadata $propertyMetadata
     * @param Type             $type
     * @param string           $className
     * @param mixed            $value
     * @param string|null      $format
     * @param array            $context
     *
     * @throws InvalidArgumentException
     *
     * @return array
     */
    private function denormalizeCollection(string $attribute, PropertyMetadata $propertyMetadata, Type $type, string $className, $value, string $format = null, array $context): array
    {
        if (!is_array($value)) {
            throw new InvalidArgumentException(sprintf(
                'The type of the "%s" attribute must be "array", "%s" given.', $attribute, gettype($value)
            ));
        }

        $collectionKeyType = $type->getCollectionKeyType();
        $collectionKeyBuiltinType = null === $collectionKeyType ? null : $collectionKeyType->getBuiltinType();

        $values = [];
        foreach ($value as $index => $obj) {
            if (null !== $collectionKeyBuiltinType && !call_user_func('is_'.$collectionKeyBuiltinType, $index)) {
                throw new InvalidArgumentException(sprintf(
                        'The type of the key "%s" must be "%s", "%s" given.',
                        $index, $collectionKeyBuiltinType, gettype($index))
                );
            }

            $values[$index] = $this->denormalizeRelation($attribute, $propertyMetadata, $className, $obj, $format, $context);
        }

        return $values;
    }

    /**
     * Denormalizes a relation.
     *
     * @param string           $attributeName
     * @param PropertyMetadata $propertyMetadata
     * @param string           $className
     * @param mixed            $value
     * @param string|null      $format
     * @param array            $context
     *
     * @throws InvalidArgumentException
     *
     * @return object|null
     */
    private function denormalizeRelation(string $attributeName, PropertyMetadata $propertyMetadata, string $className, $value, string $format = null, array $context)
    {
        if (is_string($value)) {
            try {
                return $this->iriConverter->getItemFromIri($value, $context + ['fetch_data' => true]);
            } catch (ItemNotFoundException $e) {
                throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
            } catch (InvalidArgumentException $e) {
                // Give a chance to other normalizers (e.g.: DateTimeNormalizer)
            }
        }

        if (!$this->resourceClassResolver->isResourceClass($className) || $propertyMetadata->isWritableLink()) {
            return $this->serializer->denormalize($value, $className, $format, $this->createRelationSerializationContext($className, $context));
        }

        if (!is_array($value)) {
            throw new InvalidArgumentException(sprintf(
                'Expected IRI or nested document for attribute "%s", "%s" given.', $attributeName, gettype($value)
            ));
        }

        throw new InvalidArgumentException(sprintf('Nested documents for attribute "%s" are not allowed. Use IRIs instead.', $attributeName));
    }

    /**
     * Sets a value of the object using the PropertyAccess component.
     *
     * @param object $object
     * @param string $attributeName
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
     * Gets a valid context for property metadata factories.
     *
     * @see https://github.com/symfony/symfony/blob/master/src/Symfony/Component/PropertyInfo/Extractor/SerializerExtractor.php
     *
     * @param array $context
     *
     * @return array
     */
    protected function getFactoryOptions(array $context): array
    {
        $options = [];

        if (isset($context['groups'])) {
            $options['serializer_groups'] = $context['groups'];
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
     * @param string $resourceClass
     * @param array  $context
     *
     * @return array
     */
    protected function createRelationSerializationContext(string $resourceClass, array $context): array
    {
        $context['resource_class'] = $resourceClass;
        unset($context['item_operation_name'], $context['collection_operation_name']);

        return $context;
    }

    /**
     * {@inheritdoc}
     *
     * @throws NoSuchPropertyException
     */
    protected function getAttributeValue($object, $attribute, $format = null, array $context = [])
    {
        $propertyMetadata = $this->propertyMetadataFactory->create($context['resource_class'], $attribute, $this->getFactoryOptions($context));

        try {
            $attributeValue = $this->propertyAccessor->getValue($object, $attribute);
        } catch (NoSuchPropertyException $e) {
            if (null === $propertyMetadata->isChildInherited()) {
                throw $e;
            }

            $attributeValue = null;
        }

        $type = $propertyMetadata->getType();

        if (
            (is_array($attributeValue) || $attributeValue instanceof \Traversable) &&
            $type &&
            $type->isCollection() &&
            ($collectionValueType = $type->getCollectionValueType()) &&
            ($className = $collectionValueType->getClassName()) &&
            $this->resourceClassResolver->isResourceClass($className)
        ) {
            $value = [];
            foreach ($attributeValue as $index => $obj) {
                $value[$index] = $this->normalizeRelation($propertyMetadata, $obj, $className, $format, $context);
            }

            return $value;
        }

        if (
            $attributeValue &&
            $type &&
            ($className = $type->getClassName()) &&
            $this->resourceClassResolver->isResourceClass($className)
        ) {
            return $this->normalizeRelation($propertyMetadata, $attributeValue, $className, $format, $context);
        }

        return $this->serializer->normalize($attributeValue, $format, $context);
    }

    /**
     * Normalizes a relation as an URI if is a Link or as a JSON-LD object.
     *
     * @param PropertyMetadata $propertyMetadata
     * @param mixed            $relatedObject
     * @param string           $resourceClass
     * @param string|null      $format
     * @param array            $context
     *
     * @return string|array
     */
    private function normalizeRelation(PropertyMetadata $propertyMetadata, $relatedObject, string $resourceClass, string $format = null, array $context)
    {
        if ($propertyMetadata->isReadableLink()) {
            return $this->serializer->normalize($relatedObject, $format, $this->createRelationSerializationContext($resourceClass, $context));
        }

        return $this->iriConverter->getIriFromItem($relatedObject);
    }
}
