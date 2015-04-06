<?php

/*
 * This file is part of the DunglasJsonLdApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\JsonLdApiBundle\Serializer;

use Dunglas\JsonLdApiBundle\JsonLd\ResourceCollectionInterface;
use Dunglas\JsonLdApiBundle\JsonLd\ResourceInterface;
use Dunglas\JsonLdApiBundle\Mapping\ClassMetadataFactory;
use Dunglas\JsonLdApiBundle\Mapping\AttributeMetadata;
use Dunglas\JsonLdApiBundle\JsonLd\ContextBuilder;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Serializer\Exception\CircularReferenceException;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

/**
 * Converts between objects and array including JSON-LD and Hydra metadata.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ItemNormalizer extends AbstractNormalizer
{
    use ResourceResolver;

    /**
     * @var string
     */
    const FORMAT = 'json-ld';

    /**
     * @var ClassMetadataFactory
     */
    private $jsonLdClassMetadataFactory;
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
        ClassMetadataFactory $jsonLdClassMetadataFactory,
        ContextBuilder $contextBuilder,
        NameConverterInterface $nameConverter = null,
        PropertyAccessorInterface $propertyAccessor = null
    ) {
        parent::__construct(null, $nameConverter);

        $this->resourceCollection = $resourceCollection;
        $this->jsonLdClassMetadataFactory = $jsonLdClassMetadataFactory;
        $this->contextBuilder = $contextBuilder;
        $this->propertyAccessor = $propertyAccessor ?: PropertyAccess::createPropertyAccessor();
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
     * @throws CircularReferenceException
     * @throws InvalidArgumentException
     */
    public function normalize($object, $format = null, array $context = [])
    {
        if (is_object($object) && $this->isCircularReference($object, $context)) {
            return $this->handleCircularReference($object);
        }

        $resource = $this->guessResource($object, $context);
        list($context, $data) = $this->contextBuilder->bootstrap($resource, $context);

        // Don't use hydra:Collection in sub levels
        $context['json_ld_sub_level'] = true;

        $classMetadata = $this->jsonLdClassMetadataFactory->getMetadataFor(
            $resource->getEntityClass(),
            isset($context['json_ld_normalization_groups']) ? $context['json_ld_normalization_groups'] : $resource->getNormalizationGroups(),
            isset($context['json_ld_denormalization_groups']) ? $context['json_ld_denormalization_groups'] : $resource->getDenormalizationGroups(),
            isset($context['json_ld_validation_groups']) ? $context['json_ld_validation_groups'] : $resource->getValidationGroups()
        );
        $attributesMetadata = $classMetadata->getAttributes();

        $data['@id'] = $this->resourceCollection->getItemUri($object, $resource->getEntityClass());
        $data['@type'] = ($iri = $classMetadata->getIri()) ? $iri : $resource->getShortName();

        foreach ($attributesMetadata as $attributeName => $attributeMetadata) {
            if ($attributeMetadata->isReadable() && 'id' !== $attributeName) {
                $attributeValue = $this->propertyAccessor->getValue($object, $attributeName);

                if (isset($attributeMetadata->getTypes()[0])) {
                    $type = $attributeMetadata->getTypes()[0];

                    if (
                        $attributeValue &&
                        $type->isCollection() &&
                        ($collectionType = $type->getCollectionType()) &&
                        $class = $this->getClassHavingResource($collectionType)
                    ) {
                        $uris = [];
                        foreach ($attributeValue as $obj) {
                            $uris[] = $this->normalizeRelation($resource, $attributeMetadata, $obj, $class);
                        }

                        $data[$attributeName] = $uris;
                    } elseif ($attributeValue && $class = $this->getClassHavingResource($type)) {
                        $data[$attributeName] = $this->normalizeRelation($resource, $attributeMetadata, $attributeValue, $class);
                    }
                }

                if (!isset($data[$attributeName])) {
                    $data[$attributeName] = $this->serializer->normalize($attributeValue, 'json-ld', $context);
                }
            }
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
     * @throws InvalidArgumentException
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        $resource = $this->guessResource($data, $context);
        $normalizedData = $this->prepareForDenormalization($data);

        $attributes = $this->jsonLdClassMetadataFactory->getMetadataFor(
            $class,
            $resource->getNormalizationGroups(),
            $resource->getDenormalizationGroups(),
            $resource->getValidationGroups()
        )->getAttributes();

        $allowedAttributes = [];
        foreach ($attributes as $attributeName => $attribute) {
            if ($attribute->isReadable()) {
                $allowedAttributes[] = $attributeName;
            }
        }

        $reflectionClass = new \ReflectionClass($class);
        $object = $this->instantiateObject($normalizedData, $class, $context, $reflectionClass, $allowedAttributes);

        foreach ($normalizedData as $attribute => $value) {
            // Ignore JSON-LD special attributes
            if ('@' === $attribute[0]) {
                continue;
            }

            $allowed = $allowedAttributes === false || in_array($attribute, $allowedAttributes);
            $ignored = in_array($attribute, $this->ignoredAttributes);

            if (!$allowed || $ignored) {
                continue;
            }

            if ($this->nameConverter) {
                $attribute = $this->nameConverter->denormalize($attribute);
            }

            $types = $attributes[$attribute]->getTypes();
            if (isset($types[0]) && !is_null($value) && 'object' === $types[0]->getType()) {
                if ($attributes[$attribute]->getTypes()[0]->isCollection()) {
                    $collection = [];
                    foreach ($value as $uri) {
                        if (!is_string($uri)) {
                            throw new InvalidArgumentException(sprintf(
                                'Nested objects are not supported (found in attribute "%s")',
                                $attribute
                            ));
                        }

                        $collection[] = $this->resourceCollection->getItemFromUri($uri);
                    }

                    $value = $collection;
                } elseif (!$this->getClassHavingResource($types[0])) {
                    $typeClass = $types[0]->getClass();
                    if ('DateTime' === $typeClass) {
                        $value = new \DateTime($value);
                    } else {
                        throw new InvalidArgumentException(sprintf(
                            'Property type not supported ("%s" in attribute "%s")',
                            $typeClass,
                            $attribute
                        ));
                    }
                } elseif (is_string($value)) {
                    $value = $this->resourceCollection->getItemFromUri($value);
                } else {
                    throw new InvalidArgumentException(sprintf(
                        'Type not supported (found "%s" in attribute "%s")',
                        gettype($value),
                        $attribute
                    ));
                }
            }

            try {
                $this->propertyAccessor->setValue($object, $attribute, $value);
            } catch (NoSuchPropertyException $exception) {
                // Properties not found are ignored
            }
        }

        return $object;
    }

    /**
     * Normalizes a relation as an URI if is a Link or as a JSON-LD object.
     *
     * @param ResourceInterface $currentResource
     * @param AttributeMetadata $attribute
     * @param mixed             $relatedObject
     * @param string            $class
     *
     * @return string|array
     */
    private function normalizeRelation(ResourceInterface $currentResource, AttributeMetadata $attribute, $relatedObject, $class)
    {
        if ($attribute->isLink()) {
            return $this->resourceCollection->getItemUri($relatedObject, $class);
        } else {
            $context = $this->contextBuilder->bootstrapRelation($currentResource, $class);

            return $this->serializer->normalize($relatedObject, 'json-ld', $context);
        }
    }
}
