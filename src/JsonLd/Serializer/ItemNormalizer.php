<?php

/*
 * This file is part of the API Platform Builder package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Builder\JsonLd\Serializer;

use ApiPlatform\Builder\Api\IriConverterInterface;
use ApiPlatform\Builder\Api\ResourceClassResolverInterface;
use ApiPlatform\Builder\Exception\InvalidArgumentException;
use ApiPlatform\Builder\Exception\RuntimeException;
use ApiPlatform\Builder\JsonLd\ContextBuilderInterface;
use ApiPlatform\Builder\Metadata\Property\Factory\CollectionMetadataFactoryInterface as PropertyCollectionMetadataFactoryInterface;
use ApiPlatform\Builder\Metadata\Property\Factory\ItemMetadataFactoryInterface as PropertyItemMetadataFactoryInterface;
use ApiPlatform\Builder\Metadata\Property\ItemMetadata as PropertyItemMetadata;
use ApiPlatform\Builder\Metadata\Resource\Factory\ItemMetadataFactoryInterface as ResourceItemMetadataFactoryInterface;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Serializer\Exception\CircularReferenceException;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Converts between objects and array including JSON-LD and Hydra metadata.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ItemNormalizer extends AbstractNormalizer
{
    use ContextTrait;

    const FORMAT = 'jsonld';

    private $resourceItemMetadataFactory;
    private $propertyCollectionMetadataFactory;
    private $propertyItemMetadataFactory;
    private $iriConverter;
    private $resourceClassResolver;
    private $contextBuilder;
    private $propertyAccessor;

    public function __construct(ResourceItemMetadataFactoryInterface $resourceItemMetadataFactory, PropertyCollectionMetadataFactoryInterface $propertyCollectionMetadataFactory, PropertyItemMetadataFactoryInterface $propertyItemMetadataFactory, IriConverterInterface $iriConverter, ResourceClassResolverInterface $resourceClassResolver, ContextBuilderInterface $contextBuilder, PropertyAccessorInterface $propertyAccessor = null, NameConverterInterface $nameConverter = null)
    {
        parent::__construct(null, $nameConverter);

        $this->resourceItemMetadataFactory = $resourceItemMetadataFactory;
        $this->propertyCollectionMetadataFactory = $propertyCollectionMetadataFactory;
        $this->propertyItemMetadataFactory = $propertyItemMetadataFactory;
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
        return self::FORMAT === $format && (is_object($data) || is_array($data));
    }

    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException
     * @throws CircularReferenceException
     * @throws InvalidArgumentException
     */
    public function normalize($object, $format = null, array $context = [])
    {
        if (!$this->serializer instanceof NormalizerInterface) {
            throw new RuntimeException('The serializer must implement the NormalizerInterface.');
        }

        if (is_object($object) && $this->isCircularReference($object, $context)) {
            return $this->handleCircularReference($object);
        }

        $resourceClass = $this->getResourceClass($this->resourceClassResolver, $object, $context);
        $resourceItemMetadata = $this->resourceItemMetadataFactory->create($resourceClass);
        $data = $this->addJsonLdContext($this->contextBuilder, $resourceClass, $context);
        $context = $this->createContext($resourceClass, $resourceItemMetadata, $context, true);
        $options = $this->getFactoryOptions($context);
        $propertyNames = $this->propertyCollectionMetadataFactory->create($resourceClass, $options);

        $data['@id'] = $this->iriConverter->getIriFromItem($object);
        $data['@type'] = ($iri = $resourceItemMetadata->getIri()) ? $iri : $resourceItemMetadata->getShortName();

        foreach ($propertyNames as $propertyName) {
            $propertyItemMetadata = $this->propertyItemMetadataFactory->create($resourceClass, $propertyName, $options);

            if ($propertyItemMetadata->isIdentifier() || !$propertyItemMetadata->isReadable()) {
                continue;
            }

            $attributeValue = $this->propertyAccessor->getValue($object, $propertyName);

            if ($this->nameConverter) {
                $propertyName = $this->nameConverter->normalize($propertyName);
            }

            $type = $propertyItemMetadata->getType();

            if (
                $attributeValue &&
                $type &&
                $type->isCollection() &&
                ($collectionValueType = $type->getCollectionValueType()) &&
                ($className = $collectionValueType->getClassName()) &&
                $this->resourceClassResolver->isResourceClass($className)
            ) {
                $data[$propertyName] = [];
                foreach ($attributeValue as $index => $obj) {
                    $data[$propertyName][$index] = $this->normalizeRelation($propertyItemMetadata, $obj, $className, $context);
                }

                continue;
            }

            if (
                $attributeValue &&
                $type &&
                ($className = $type->getClassName()) &&
                $this->resourceClassResolver->isResourceClass($className)
            ) {
                $data[$propertyName] = $this->normalizeRelation($propertyItemMetadata, $attributeValue, $className, $context);

                continue;
            }

            $data[$propertyName] = $this->serializer->normalize($attributeValue, self::FORMAT, $context);
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return self::FORMAT === $format;
    }

    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException
     * @throws InvalidArgumentException
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        if (!$this->serializer instanceof DenormalizerInterface) {
            throw new RuntimeException('The serializer must implement the DenormalizerInterface to denormalize relations.');
        }

        $resourceClass = $this->getResourceClass($this->resourceClassResolver, $data, $context);
        $normalizedData = $this->prepareForDenormalization($data);

        $resourceItemMetadata = $this->resourceItemMetadataFactory->create($resourceClass);
        $context = $this->createContext($resourceClass, $resourceItemMetadata, $context, false);
        $options = $this->getFactoryOptions($context);
        $propertyNames = $this->propertyCollectionMetadataFactory->create($resourceClass, $options);

        $allowedAttributes = [];
        foreach ($propertyNames as $propertyName) {
            $propertyItemMetadata = $this->propertyItemMetadataFactory->create($resourceClass, $propertyName, $options);

            if ($propertyItemMetadata->isWritable()) {
                $allowedAttributes[$propertyName] = $propertyItemMetadata;
            }
        }

        // Avoid issues with proxies if we populated the object
        $overrideClass = isset($data['@id']) && !isset($context['object_to_populate']);

        if ($overrideClass) {
            $context['object_to_populate'] = $this->iriConverter->getItemFromIri($data['@id']);
        }

        $instanceClass = $overrideClass ? get_class($context['object_to_populate']) : $class;
        $reflectionClass = new \ReflectionClass($instanceClass);
        if ($reflectionClass->isAbstract()) {
            throw new InvalidArgumentException(sprintf('Cannot create an instance of %s from serialized data because it is an abstract resource.', $instanceClass));
        }

        $object = $this->instantiateObject($normalizedData, $instanceClass, $context, $reflectionClass, array_keys($allowedAttributes));

        foreach ($normalizedData as $attributeName => $attributeValue) {
            // Ignore JSON-LD special attributes
            if ('@' === $attributeName[0]) {
                continue;
            }

            if ($this->nameConverter) {
                $attributeName = $this->nameConverter->denormalize($attributeName);
            }

            if (!isset($allowedAttributes[$attributeName]) || in_array($attributeName, $this->ignoredAttributes)) {
                continue;
            }

            /*
             * @var Type
             */
            $type = $allowedAttributes[$attributeName]->getType();
            if ($type && $attributeValue) {
                if (
                    $type->isCollection() &&
                    ($collectionType = $type->getCollectionValueType()) &&
                    ($className = $collectionType->getClassName())
                ) {
                    if (!is_array($attributeValue)) {
                        continue;
                    }

                    $values = [];
                    foreach ($attributeValue as $index => $obj) {
                        $values[$index] = $this->denormalizeRelation(
                            $resourceClass,
                            $attributeName,
                            $allowedAttributes[$attributeName],
                            $className,
                            $obj,
                            $context
                        );
                    }

                    $this->setValue($object, $attributeName, $values);

                    continue;
                }

                if ($className = $type->getClassName()) {
                    $this->setValue(
                        $object,
                        $attributeName,
                        $this->denormalizeRelation(
                            $resourceClass,
                            $attributeName,
                            $allowedAttributes[$attributeName],
                            $className,
                            $attributeValue,
                            $context
                        )
                    );

                    continue;
                }
            }

            $this->setValue($object, $attributeName, $attributeValue);
        }

        return $object;
    }

    /**
     * Normalizes a relation as an URI if is a Link or as a JSON-LD object.
     *
     * @param PropertyItemMetadata $propertyItemMetadata
     * @param mixed                $relatedObject
     * @param string               $resourceClass
     * @param array                $context
     *
     * @return string|array
     */
    private function normalizeRelation(PropertyItemMetadata $propertyItemMetadata, $relatedObject, string $resourceClass, array $context)
    {
        if ($propertyItemMetadata->isReadableLink()) {
            return $this->serializer->normalize($relatedObject, self::FORMAT, $this->createRelationContext($resourceClass, $context));
        }

        return $this->iriConverter->getIriFromItem($relatedObject);
    }

    /**
     * Denormalizes a relation.
     *
     * @param string               $resourceClass
     * @param string               $attributeName
     * @param PropertyItemMetadata $propertyItemMetadata
     * @param string               $className
     * @param mixed                $value
     * @param array                $context
     *
     * @return object|null
     *
     * @throws InvalidArgumentException
     */
    private function denormalizeRelation(string $resourceClass, string $attributeName, PropertyItemMetadata $propertyItemMetadata, string $className, $value, array $context)
    {
        if (is_string($value)) {
            try {
                return $this->iriConverter->getItemFromIri($value);
            } catch (InvalidArgumentException $e) {
                // Give a change to other normalizers (e.g.: DateTimeNormalizer)
            }
        }

        if (!$this->resourceClassResolver->isResourceClass($className) || $propertyItemMetadata->isWritableLink()) {
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
