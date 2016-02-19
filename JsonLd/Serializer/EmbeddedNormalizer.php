<?php

namespace Dunglas\ApiBundle\JsonLd\Serializer;

use Dunglas\ApiBundle\Exception\InvalidArgumentException;
use Dunglas\ApiBundle\Exception\RuntimeException;
use Dunglas\ApiBundle\Mapping\ClassMetadataInterface;
use Dunglas\ApiBundle\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class EmbeddedNormalizer extends AbstractNormalizer
{
    /**
     * @var string
     */
    const FORMAT = 'jsonld';

    /**
     * @var ClassMetadataFactoryInterface
     */
    private $apiClassMetadataFactory;

    /**
     * @var PropertyAccessorInterface
     */
    private $propertyAccessor;

    /**
     * @var string
     */
    private $embeddedClass;

    /**
     * @param ClassMetadataFactoryInterface $apiClassMetadataFactory
     * @param PropertyAccessorInterface     $propertyAccessor
     * @param NameConverterInterface        $nameConverter
     * @param string                        $embeddedClass
     */
    public function __construct(
        ClassMetadataFactoryInterface $apiClassMetadataFactory,
        PropertyAccessorInterface $propertyAccessor,
        NameConverterInterface $nameConverter = null,
        $embeddedClass
    ) {
        parent::__construct(null, $nameConverter);

        $this->apiClassMetadataFactory = $apiClassMetadataFactory;
        $this->propertyAccessor = $propertyAccessor;
        $this->embeddedClass = $embeddedClass;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof $this->embeddedClass;
    }

    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException
     */
    public function normalize($object, $format = null, array $context = [])
    {
        if (!$this->serializer instanceof NormalizerInterface) {
            throw new RuntimeException('The serializer must implement the NormalizerInterface.');
        }

        $embeddedClass = get_class($object);

        $data = [];

        $classMetadata = $this->getMetadata($embeddedClass, $context);
        $attributesMetadata = $classMetadata->getAttributesMetadata();

        $data['@type'] = ($iri = $classMetadata->getIri()) ? $iri : $this->getShortName($embeddedClass);

        foreach ($attributesMetadata as $attributeName => $attributeMetadata) {
            if (!$attributeMetadata->isReadable()) {
                continue;
            }
            $attributeValue = $this->propertyAccessor->getValue($object, $attributeName);

            if ($this->nameConverter) {
                $attributeName = $this->nameConverter->normalize($attributeName);
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
        return $this->embeddedClass === $type;
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

        $embeddedClass = $class;
        $normalizedData = $this->prepareForDenormalization($data);

        $attributesMetadata = $this->getMetadata($embeddedClass, $context)->getAttributesMetadata();

        $allowedAttributes = [];
        foreach ($attributesMetadata as $attributeName => $attributeMetadata) {
            if ($attributeMetadata->isWritable()) {
                $allowedAttributes[] = $attributeName;
            }
        }

        $reflectionClass = new \ReflectionClass($embeddedClass);

        $object = $this->instantiateObject(
            $normalizedData,
            $embeddedClass,
            $context,
            $reflectionClass,
            $allowedAttributes
        );

        foreach ($normalizedData as $attributeName => $attributeValue) {
            // Ignore JSON-LD special attributes
            if ('@' === $attributeName[0]) {
                continue;
            }

            if ($this->nameConverter) {
                $attributeName = $this->nameConverter->denormalize($attributeName);
            }

            if (!in_array($attributeName, $allowedAttributes) || in_array($attributeName, $this->ignoredAttributes)) {
                continue;
            }

            $this->setValue($object, $attributeName, $attributeValue);
        }

        return $object;
    }

    /**
     * Gets metadata for the given embeddable class with the current context.
     *
     * @param string $embeddedClass
     * @param array  $context
     *
     * @return ClassMetadataInterface
     */
    private function getMetadata($embeddedClass, array $context)
    {
        return $this->apiClassMetadataFactory->getMetadataFor(
            $embeddedClass,
            isset($context['jsonld_normalization_groups']) ? $context['jsonld_normalization_groups'] : null,
            isset($context['jsonld_denormalization_groups']) ? $context['jsonld_denormalization_groups'] : null,
            isset($context['jsonld_validation_groups']) ? $context['jsonld_validation_groups'] : null
        );
    }

    /**
     * Gets short name for an embeddable class.
     *
     * @param string $embeddedClass
     *
     * @return string
     */
    private function getShortName($embeddedClass)
    {
        return substr($embeddedClass, strrpos($embeddedClass, '\\') + 1);
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
}
