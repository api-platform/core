<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\JsonLd\Serializer;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\JsonLd\ContextBuilderInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

/**
 * Converts between objects and array including JSON-LD and Hydra metadata.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ItemNormalizer extends AbstractObjectNormalizer
{
    use ContextTrait;

    const FORMAT = 'jsonld';

    private $resourceMetadataFactory;
    private $propertyNameCollectionFactory;
    private $propertyMetadataFactory;
    private $iriConverter;
    private $resourceClassResolver;
    private $contextBuilder;
    private $propertyAccessor;

    public function __construct(ResourceMetadataFactoryInterface $resourceMetadataFactory, PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, IriConverterInterface $iriConverter, ResourceClassResolverInterface $resourceClassResolver, ContextBuilderInterface $contextBuilder, PropertyAccessorInterface $propertyAccessor = null, NameConverterInterface $nameConverter = null)
    {
        parent::__construct(null, $nameConverter);

        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->propertyNameCollectionFactory = $propertyNameCollectionFactory;
        $this->propertyMetadataFactory = $propertyMetadataFactory;
        $this->iriConverter = $iriConverter;
        $this->resourceClassResolver = $resourceClassResolver;
        $this->contextBuilder = $contextBuilder;
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
        if (self::FORMAT !== $format || !is_object($data)) {
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
        $resourceClass = $this->getResourceClass($this->resourceClassResolver, $object, $context);
        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);

        $data = $this->addJsonLdContext($this->contextBuilder, $resourceClass, $context);

        $context['jsonld_normalize'] = true;
        $context = $this->createContext($resourceClass, $resourceMetadata, $context, true);

        $rawData = parent::normalize($object, $format, $context);
        if (!is_array($rawData)) {
            return $rawData;
        }

        $data['@id'] = $this->iriConverter->getIriFromItem($object);
        $data['@type'] = ($iri = $resourceMetadata->getIri()) ? $iri : $resourceMetadata->getShortName();

        return array_merge($data, $rawData);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return self::FORMAT === $format && $this->resourceClassResolver->isResourceClass($type);
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        $resourceClass = $this->getResourceClass($this->resourceClassResolver, $data, $context);
        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);

        $context['jsonld_denormalize'] = true;
        $context = $this->createContext($resourceClass, $resourceMetadata, $context, false);

        // Avoid issues with proxies if we populated the object
        $overrideClass = isset($data['@id']) && !isset($context['object_to_populate']);

        if ($overrideClass) {
            $context['object_to_populate'] = $this->iriConverter->getItemFromIri($data['@id']);
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
    protected function getAttributeValue($object, $attribute, $format = null, array $context = [])
    {
        $propertyMetadata = $this->propertyMetadataFactory->create($context['resource_class'], $attribute, $this->getFactoryOptions($context));

        $attributeValue = $this->propertyAccessor->getValue($object, $attribute);
        $type = $propertyMetadata->getType();

        if (
            $attributeValue &&
            $type &&
            $type->isCollection() &&
            ($collectionValueType = $type->getCollectionValueType()) &&
            ($className = $collectionValueType->getClassName()) &&
            $this->resourceClassResolver->isResourceClass($className)
        ) {
            $value = [];
            foreach ($attributeValue as $index => $obj) {
                $value[$index] = $this->normalizeRelation($propertyMetadata, $obj, $className, $context);
            }

            return $value;
        }

        if (
            $attributeValue &&
            $type &&
            ($className = $type->getClassName()) &&
            $this->resourceClassResolver->isResourceClass($className)
        ) {
            return $this->normalizeRelation($propertyMetadata, $attributeValue, $className, $context);
        }

        return $this->serializer->normalize($attributeValue, self::FORMAT, $context);
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
                (isset($context['jsonld_normalize']) && !$propertyMetadata->isIdentifier() && $propertyMetadata->isReadable()) ||
                (isset($context['jsonld_denormalize']) && $propertyMetadata->isWritable())
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
                        $context
                    )
                );

                return;
            }
        }

        $this->setValue($object, $attribute, $value);
    }

    /**
     * Normalizes a relation as an URI if is a Link or as a JSON-LD object.
     *
     * @param PropertyMetadata $propertyMetadata
     * @param mixed            $relatedObject
     * @param string           $resourceClass
     * @param array            $context
     *
     * @return string|array
     */
    private function normalizeRelation(PropertyMetadata $propertyMetadata, $relatedObject, string $resourceClass, array $context)
    {
        if ($propertyMetadata->isReadableLink()) {
            return $this->serializer->normalize($relatedObject, self::FORMAT, $this->createRelationContext($resourceClass, $context));
        }

        return $this->iriConverter->getIriFromItem($relatedObject);
    }

    /**
     * Denormalizes a relation.
     *
     * @param string           $resourceClass
     * @param string           $attributeName
     * @param PropertyMetadata $propertyMetadata
     * @param string           $className
     * @param mixed            $value
     * @param array            $context
     *
     * @return object|null
     *
     * @throws InvalidArgumentException
     */
    private function denormalizeRelation(string $resourceClass, string $attributeName, PropertyMetadata $propertyMetadata, string $className, $value, array $context)
    {
        if (is_string($value)) {
            try {
                return $this->iriConverter->getItemFromIri($value);
            } catch (InvalidArgumentException $e) {
                // Give a change to other normalizers (e.g.: DateTimeNormalizer)
            }
        }

        if (!$this->resourceClassResolver->isResourceClass($className) || $propertyMetadata->isWritableLink()) {
            return $this->serializer->denormalize($value, $className, self::FORMAT, $this->createRelationContext($className, $context));
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
    private function getFactoryOptions(array $context) : array
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
}
