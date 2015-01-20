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

use Dunglas\JsonLdApiBundle\Resources;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Serializer\Exception\CircularReferenceException;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

/**
 * Converts between objects and array including JSON-LD and Hydra metadata.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class JsonLdNormalizer extends AbstractNormalizer
{
    const FORMAT = 'json-ld';

    /**
     * @var Resources
     */
    protected $resources;
    /**
     * @var RouterInterface
     */
    protected $router;
    /**
     * @var PropertyAccessorInterface
     */
    protected $propertyAccessor;

    public function __construct(
        Resources $resources,
        RouterInterface $router,
        ClassMetadataFactory $classMetadataFactory = null,
        NameConverterInterface $nameConverter = null,
        PropertyAccessorInterface $propertyAccessor = null
    ) {
        parent::__construct($classMetadataFactory, $nameConverter);

        $this->resources = $resources;
        $this->router = $router;
        $this->propertyAccessor = $propertyAccessor ?: PropertyAccess::createPropertyAccessor();
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return is_object($data) && self::FORMAT === $format;
    }

    /**
     * {@inheritdoc}
     *
     * @throws CircularReferenceException
     */
    public function normalize($object, $format = null, array $context = [])
    {
        $class = get_class($object);
        $resource = $this->resources->getResourceForEntity($class);

        if (!$resource) {
            throw new \InvalidArgumentException(sprintf('There is no Resource associated with the class %s.', $class));
        }

        if ($this->isCircularReference($object, $context)) {
            return $this->handleCircularReference($object);
        }

        $data = [];
        $attributes = $this->getAllowedAttributes($object, $context);

        $data['@context'] = $this->router->generate(
            'json_ld_api_context',
            ['shortName' => $resource->getShortName()]
        );

        $data['@id'] = $this->router->generate(
            $resource->getIdRoute(),
            ['id' => $this->propertyAccessor->getValue($object, 'id')]
        );

        $data['@type'] = $resource->getShortName();

        // If not using groups, detect manually
        if (false === $attributes) {
            $attributes = [];

            // methods
            $reflClass = new \ReflectionClass($object);
            foreach ($reflClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $reflMethod) {
                if (
                    !$reflMethod->isConstructor() &&
                    !$reflMethod->isDestructor() &&
                    0 === $reflMethod->getNumberOfRequiredParameters()
                ) {
                    // getsetters (jQuery style, e.g. read: last(), write: last($item))
                    $name = $reflMethod->getName();

                    if (strpos($name, 'get') === 0 || strpos($name, 'has') === 0) {
                        // getters and hassers
                        $name = lcfirst(substr($name, 3));
                    } elseif (strpos($name, 'is') === 0) {
                        // issers
                        $name = lcfirst(substr($name, 2));
                    }

                    $attributes[$name] = true;
                }
            }

            // properties
            foreach ($reflClass->getProperties(\ReflectionProperty::IS_PUBLIC) as $reflProperty) {
                $name = $reflProperty->getName();
                $attributes[$name] = true;
            }

            unset($attributes['id']);
            $attributes = array_keys($attributes);
        }

        foreach ($attributes as $attribute) {
            $attributeValue = $this->propertyAccessor->getValue($object, $attribute);

            if (null !== $attributeValue && !is_scalar($attributeValue)) {
                $attributeValue = $this->serializer->normalize($attributeValue, $format);
            }

            $data[$attribute] = $attributeValue;
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
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        $allowedAttributes = $this->getAllowedAttributes($class, $context);
        $normalizedData = $this->prepareForDenormalization($data);

        $reflectionClass = new \ReflectionClass($class);
        $object = $this->instantiateObject($normalizedData, $class, $context, $reflectionClass, $allowedAttributes);

        foreach ($normalizedData as $attribute => $value) {
            // Ignore JSON-LD attributes
            if ('@' === $attribute[0]) {
                continue;
            }

            $allowed = $allowedAttributes === false || in_array($attribute, $allowedAttributes);
            $ignored = in_array($attribute, $this->ignoredAttributes);

            if ($allowed && !$ignored) {
                if ($this->nameConverter) {
                    $attribute = $this->nameConverter->denormalize($attribute);
                }

                $this->propertyAccessor->setValue($object, $attribute, $value);
            }
        }

        return $object;
    }
}
