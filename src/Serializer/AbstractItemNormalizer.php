<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Serializer;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
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

    public function __construct(PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, IriConverterInterface $iriConverter, ResourceClassResolverInterface $resourceClassResolver, PropertyAccessorInterface $propertyAccessor = null, NameConverterInterface $nameConverter = null)
    {
        parent::__construct(null, $nameConverter);

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

        if ($type && $value) {
            if (
                $type->isCollection() &&
                ($collectionType = $type->getCollectionValueType()) &&
                ($className = $collectionType->getClassName())
            ) {
                if (!is_array($value)) {
                    return;
                }

                $values = [];
                foreach ($value as $index => $obj) {
                    $values[$index] = $this->denormalizeRelation(
                        $context['resource_class'],
                        $attribute,
                        $propertyMetadata,
                        $className,
                        $obj,
                        $format,
                        $context
                    );
                }

                $this->setValue($object, $attribute, $values);

                return;
            }

            if ($className = $type->getClassName()) {
                $this->setValue(
                    $object,
                    $attribute,
                    $this->denormalizeRelation(
                        $context['resource_class'],
                        $attribute,
                        $propertyMetadata,
                        $className,
                        $value,
                        $format,
                        $context
                    )
                );

                return;
            }
        }

        $this->setValue($object, $attribute, $value);
    }

    /**
     * Denormalizes a relation.
     *
     * @param string           $resourceClass
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
    private function denormalizeRelation(string $resourceClass, string $attributeName, PropertyMetadata $propertyMetadata, string $className, $value, string $format = null, array $context)
    {
        if (is_string($value)) {
            try {
                return $this->iriConverter->getItemFromIri($value, true);
            } catch (InvalidArgumentException $e) {
                // Give a chance to other normalizers (e.g.: DateTimeNormalizer)
            }
        }

        if (!$this->resourceClassResolver->isResourceClass($className) || $propertyMetadata->isWritableLink()) {
            return $this->serializer->denormalize($value, $className, $format, $this->createRelationSerializationContext($className, $context));
        }

        if (!is_array($value)) {
            throw new InvalidArgumentException(sprintf(
                'Expected IRI or nested object for attribute "%s" of "%s", "%s" given.',
                $attributeName,
                $resourceClass,
                is_object($value) ? get_class($value) : gettype($value)
            ));
        }

        throw new InvalidArgumentException(sprintf(
            'Nested objects for attribute "%s" of "%s" are not enabled. Use serialization groups to change that behavior.',
            $attributeName,
            $resourceClass
        ));
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
    protected function getFactoryOptions(array $context) : array
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
    protected function createRelationSerializationContext(string $resourceClass, array $context) : array
    {
        $context['resource_class'] = $resourceClass;
        unset($context['item_operation_name']);
        unset($context['collection_operation_name']);

        return $context;
    }

    /**
     * {@inheritdoc}
     */
    protected function getAttributeValue($object, $attribute, $format = null, array $context = [])
    {
        $propertyMetadata = $this->propertyMetadataFactory->create($context['resource_class'], $attribute, $this->getFactoryOptions($context));

        $attributeValue = $this->propertyAccessor->getValue($object, $attribute);
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
