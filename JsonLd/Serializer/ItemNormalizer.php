<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\JsonLd\Serializer;

use Dunglas\ApiBundle\Api\IriConverterInterface;
use Dunglas\ApiBundle\Api\ResourceCollectionInterface;
use Dunglas\ApiBundle\Api\ResourceInterface;
use Dunglas\ApiBundle\Api\ResourceResolverTrait;
use Dunglas\ApiBundle\Exception\InvalidArgumentException;
use Dunglas\ApiBundle\Exception\RuntimeException;
use Dunglas\ApiBundle\JsonLd\ContextBuilder;
use Dunglas\ApiBundle\Mapping\AttributeMetadataInterface;
use Dunglas\ApiBundle\Mapping\ClassMetadataFactoryInterface;
use Dunglas\ApiBundle\Mapping\ClassMetadataInterface;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
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
class ItemNormalizer extends AbstractNormalizer
{
    use ResourceResolverTrait;
    use ContextTrait;

    /**
     * @var string
     */
    const FORMAT = 'json-ld';

    /**
     * @var IriConverterInterface
     */
    private $iriConverter;
    /**
     * @var ClassMetadataFactoryInterface
     */
    private $apiClassMetadataFactory;
    /**
     * @var PropertyAccessorInterface
     */
    private $propertyAccessor;
    /**
     * @var ContextBuilder
     */
    private $contextBuilder;

    public function __construct(
        ResourceCollectionInterface $resourceCollection,
        IriConverterInterface $iriConverter,
        ClassMetadataFactoryInterface $apiClassMetadataFactory,
        ContextBuilder $contextBuilder,
        PropertyAccessorInterface $propertyAccessor,
        NameConverterInterface $nameConverter = null
    ) {
        parent::__construct(null, $nameConverter);

        $this->resourceCollection = $resourceCollection;
        $this->iriConverter = $iriConverter;
        $this->apiClassMetadataFactory = $apiClassMetadataFactory;
        $this->contextBuilder = $contextBuilder;
        $this->propertyAccessor = $propertyAccessor;

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

        $resource = $this->guessResource($object, $context, true);

        $data = [];
        if (!isset($context['json_ld_has_context'])) {
            $data['@context'] = $this->contextBuilder->getResourceContext($resource, $context);
        }

        $context = $this->createContext($resource, $context);

        $classMetadata = $this->getMetadata($resource, $context);
        $attributesMetadata = $classMetadata->getAttributes();

        $data['@id'] = $this->iriConverter->getIriFromItem($object);
        $data['@type'] = ($iri = $classMetadata->getIri()) ? $iri : $resource->getShortName();

        foreach ($attributesMetadata as $attributeMetadata) {
            if ($attributeMetadata->isIdentifier() || !$attributeMetadata->isReadable()) {
                continue;
            }
            $attributeName = $attributeMetadata->getName();
            $attributeValue = $this->propertyAccessor->getValue($object, $attributeName);

            if ($this->nameConverter) {
                $attributeName = $this->nameConverter->normalize($attributeName);
            }

            if (isset($attributeMetadata->getTypes()[0])) {
                $type = $attributeMetadata->getTypes()[0];

                if (
                    $attributeValue &&
                    $type->isCollection() &&
                    ($collectionType = $type->getCollectionType()) &&
                    $subResource = $this->getResourceFromType($collectionType)
                ) {
                    $values = [];
                    foreach ($attributeValue as $index => $obj) {
                        $values[$index] = $this->normalizeRelation($attributeMetadata, $obj, $subResource, $context);
                    }

                    $data[$attributeName] = $values;

                    continue;
                }

                if ($attributeValue && $subResource = $this->getResourceFromType($type)) {
                    $data[$attributeName] = $this->normalizeRelation($attributeMetadata, $attributeValue, $subResource, $context);

                    continue;
                }
            }

            $data[$attributeName] = $this->serializer->normalize($attributeValue, self::FORMAT, $context);
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

        $resource = $this->guessResource($data, $context, true);
        $normalizedData = $this->prepareForDenormalization($data);
        $context = $this->createContext($resource, $context);
        $attributesMetadata = $this->getMetadata($resource, $context)->getAttributes();

        $allowedAttributes = [];
        foreach ($attributesMetadata as $attributeName => $attributeMetadata) {
            if ($attributeMetadata->isWritable()) {
                $allowedAttributes[] = $attributeName;
            }
        }

        if (isset($data['@id']) && !isset($context['object_to_populate'])) {
            $context['object_to_populate'] = $this->iriConverter->getItemFromIri($data['@id']);

            // Avoid issues with proxies if we populated the object
            $overrideClass = true;
        } else {
            $overrideClass = false;
        }

        $instanceClass = $overrideClass ? get_class($context['object_to_populate']) : $class;
        $reflectionClass = new \ReflectionClass($instanceClass);
        if ($reflectionClass->isAbstract()) {
            throw new InvalidArgumentException(
                sprintf(
                    'Cannot create an instance of %s from serialized data because it is an abstract resource',
                    $instanceClass
                )
            );
        }

        $object = $this->instantiateObject(
            $normalizedData,
            $instanceClass,
            $context,
            $reflectionClass,
            $allowedAttributes
        );

        foreach ($normalizedData as $attributeName => $attributeValue) {
            if ($this->nameConverter) {
                $attributeName = $this->nameConverter->denormalize($attributeName);
            }

            if (!in_array($attributeName, $allowedAttributes) || in_array($attributeName, $this->ignoredAttributes)) {
                continue;
            }

            $types = $attributesMetadata[$attributeName]->getTypes();
            if (isset($types[0])) {
                $type = $types[0];

                if (
                    $type->isCollection() &&
                    ($collectionType = $type->getCollectionType()) &&
                    ($class = $collectionType->getClass())
                ) {
                    if (!is_array($attributeValue)) {
                        continue;
                    }

                    $values = [];
                    foreach ($attributeValue as $index => $obj) {
                        $values[$index] = $this->denormalizeRelation(
                            $resource,
                            $attributesMetadata[$attributeName],
                            $class,
                            $obj,
                            $context
                        );
                    }

                    $this->setValue($object, $attributeName, $values);

                    continue;
                }

                if ($attributeValue && ($class = $type->getClass())) {
                    $this->setValue(
                        $object,
                        $attributeName,
                        $this->denormalizeRelation(
                            $resource,
                            $attributesMetadata[$attributeName],
                            $class,
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
     * @param AttributeMetadataInterface $attribute
     * @param mixed                      $relatedObject
     * @param ResourceInterface          $resource
     * @param array                      $context
     *
     * @return string|array
     */
    private function normalizeRelation(
        AttributeMetadataInterface $attribute,
        $relatedObject,
        ResourceInterface $resource,
        array $context
    ) {
        if ($attribute->isNormalizationLink()) {
            return $this->iriConverter->getIriFromItem($relatedObject);
        } else {
            return $this->serializer->normalize(
                $relatedObject,
                self::FORMAT,
                $this->createRelationContext($resource, $context)
            );
        }
    }

    /**
     * Denormalizes a relation.
     *
     * @param ResourceInterface          $currentResource
     * @param AttributeMetadataInterface $attributeMetadata
     * @param string                     $class
     * @param mixed                      $value
     * @param array                      $context
     *
     * @throws InvalidArgumentException
     *
     * @return object|null
     */
    private function denormalizeRelation(
        ResourceInterface $currentResource,
        AttributeMetadataInterface $attributeMetadata,
        $class,
        $value,
        array $context
    ) {
        if ('DateTime' === $class) {
            return $this->serializer->denormalize($value, $class ?: null, self::FORMAT, $context);
        }

        $attributeName = $attributeMetadata->getName();

        // Always allow IRI to be compliant with the Hydra spec
        if (is_string($value)) {
            try {
                return $this->iriConverter->getItemFromIri($value);
            } catch (InvalidArgumentException $e) {
                throw new InvalidArgumentException(sprintf(
                    'IRI  not supported (found "%s" in "%s" of "%s")',
                    $value,
                    $attributeName,
                    $currentResource->getEntityClass()
                ), $e->getCode(), $e);
            }
        }

        if (!$resource = $this->resourceCollection->getResourceForEntity($class)) {
            throw new InvalidArgumentException(sprintf(
                'Type not supported (found "%s" in attribute "%s" of "%s")',
                $class,
                $attributeName,
                $currentResource->getEntityClass()
            ));
        }

        if (!$attributeMetadata->isDenormalizationLink()) {
            return $this->serializer->denormalize(
                $value,
                $class,
                self::FORMAT,
                $this->createRelationContext($resource, $context)
            );
        }

        throw new InvalidArgumentException(sprintf(
            'Nested objects for attribute "%s" of "%s" are not enabled. Use serialization groups to change that behavior.',
            $attributeName,
            $currentResource->getEntityClass()
        ));
    }

    /**
     * Sets a value of the object using the PropertyAccess component.
     *
     * @param object $object
     * @param string $attributeName
     * @param mixed  $value
     */
    private function setValue($object, $attributeName, $value)
    {
        try {
            $this->propertyAccessor->setValue($object, $attributeName, $value);
        } catch (NoSuchPropertyException $exception) {
            // Properties not found are ignored
        }
    }

    /**
     * Gets metadata for the given resource with the current context.
     *
     * Fallback to the resource own groups if no context is provided.
     *
     * @param ResourceInterface $resource
     * @param array             $context
     *
     * @return ClassMetadataInterface
     */
    private function getMetadata(ResourceInterface $resource, array $context)
    {
        return $this->apiClassMetadataFactory->getMetadataFor(
            $resource->getEntityClass(),
            isset($context['json_ld_normalization_groups']) ? $context['json_ld_normalization_groups'] : $resource->getNormalizationGroups(),
            isset($context['json_ld_denormalization_groups']) ? $context['json_ld_denormalization_groups'] : $resource->getDenormalizationGroups(),
            isset($context['json_ld_validation_groups']) ? $context['json_ld_validation_groups'] : $resource->getValidationGroups()
        );
    }
}
