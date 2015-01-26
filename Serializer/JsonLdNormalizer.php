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

use Doctrine\ORM\Tools\Pagination\Paginator;
use Dunglas\JsonLdApiBundle\Resources;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Serializer\Exception\CircularReferenceException;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
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
    const HYDRA_COLLECTION = 'hydra:Collection';
    const HYDRA_PAGED_COLLECTION = 'hydra:PagedCollection';

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
        if (!$resource = $this->guessResource($object, $context)) {
            throw new InvalidArgumentException('A resource object must be passed in the context.');
        }

        if (is_object($object) && $this->isCircularReference($object, $context)) {
            return $this->handleCircularReference($object);
        }

        $data = [];
        if (!isset($context['has_json_ld_context'])) {
            $data['@context'] = $this->router->generate(
                'json_ld_api_context',
                ['shortName' => $resource->getShortName()]
            );
            $context['has_json_ld_context'] = true;
        }

        // Collection
        if (is_array($object) || $object instanceof \Traversable) {
            $data['@id'] = $this->router->generate($resource->getCollectionRoute());

            if ($object instanceof Paginator) {
                $data['@type'] = self::HYDRA_PAGED_COLLECTION;

                $query = $object->getQuery();
                $firstResult = $query->getFirstResult();
                $maxResults = $query->getMaxResults();
                $currentPage = floor($firstResult / $maxResults) + 1.;
                $totalItems = count($object);
                $lastPage = ceil($totalItems / $maxResults);

                $baseUrl = $data['@id'];
                $paginatedUrl = $baseUrl.'?page=';

                if (1. !== $currentPage) {
                    $previousPage = $currentPage - 1.;
                    $data['@id'] .= $paginatedUrl.$currentPage;
                    $data['hydra:previousPage'] = 1. === $previousPage ? $baseUrl : $paginatedUrl.$previousPage;
                }

                if ($currentPage !== $lastPage) {
                    $data['hydra:nextPage'] = $paginatedUrl.($currentPage + 1.);
                }

                $data['hydra:totalItems'] = $totalItems;
                $data['hydra:itemsPerPage'] = $maxResults;
                $data['hydra:firstPage'] = $baseUrl;
                $data['hydra:lastPage'] = 1. === $lastPage ? $baseUrl : $paginatedUrl.$lastPage;
            } else {
                $data['@type'] = self::HYDRA_COLLECTION;
            }

            $data['member'] = [];
            foreach ($object as $obj) {
                $data['member'][] = $this->normalize($obj, $format, $context);
            }

            return $data;
        }

        $data['@id'] = $this->router->generate(
            $resource->getElementRoute(),
            ['id' => $this->propertyAccessor->getValue($object, 'id')]
        );
        $data['@type'] = $resource->getShortName();

        $attributes = $this->getAllowedAttributes($object, $context);
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
                    $methodName = $reflMethod->getName();

                    if (strpos($methodName, 'get') === 0 || strpos($methodName, 'has') === 0) {
                        // getters and hassers
                        $attributeName = lcfirst(substr($methodName, 3));
                    } elseif (strpos($methodName, 'is') === 0) {
                        // issers
                        $attributeName = lcfirst(substr($methodName, 2));
                    }

                    if (isset($attributeName)) {
                        $attributes[$attributeName] = true;
                    }
                }
            }

            // properties
            foreach ($reflClass->getProperties(\ReflectionProperty::IS_PUBLIC) as $reflProperty) {
                $name = $reflProperty->getName();
                $attributes[$name] = true;
            }

            unset($attributes['id']);
        }

        foreach ($attributes as $attribute => $type) {
            $attributeValue = $this->propertyAccessor->getValue($object, $attribute);

            if (null !== $attributeValue && !is_scalar($attributeValue)) {
                $attributeValue = $this->serializer->normalize($attributeValue, $format, $context);
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

    /**
     * Guesses the associated resource.
     *
     * @param  mixed         $object
     * @param  array         $context
     * @return Resource|null
     */
    private function guessResource($object, array $context)
    {
        if (isset($context['resource'])) {
            return $context['resource'];
        }

        if (is_object($object)) {
            $this->resources->getResourceForEntity(get_class($object));
        }

        return;
    }
}
