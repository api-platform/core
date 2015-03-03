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
use Dunglas\JsonLdApiBundle\Model\DataManipulatorInterface;
use Dunglas\JsonLdApiBundle\JsonLd\Resource;
use Dunglas\JsonLdApiBundle\JsonLd\Resources;
use PropertyInfo\Type;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Serializer\Exception\CircularReferenceException;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Dunglas\JsonLdApiBundle\Mapping\ClassMetadataFactory;
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
    private $resources;
    /**
     * @var RouterInterface
     */
    private $router;
    /**
     * @var DataManipulatorInterface
     */
    private $dataManipulator;
    /**
     * @var ClassMetadataFactory
     */
    private $jsonLdClassMetadataFactory;
    /**
     * @var PropertyAccessorInterface
     */
    private $propertyAccessor;

    public function __construct(
        Resources $resources,
        RouterInterface $router,
        DataManipulatorInterface $dataManipulator,
        ClassMetadataFactory $jsonLdClassMetadataFactory,
        NameConverterInterface $nameConverter = null,
        PropertyAccessorInterface $propertyAccessor = null
    ) {
        parent::__construct(null, $nameConverter);

        $this->resources = $resources;
        $this->router = $router;
        $this->dataManipulator = $dataManipulator;
        $this->jsonLdClassMetadataFactory = $jsonLdClassMetadataFactory;
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
            if (isset($context['sub_level'])) {
                $data = [];
                foreach ($object as $obj) {
                    $data[] = $this->normalize($obj, $format, $context);
                }
            } else {
                $data['@id'] = $this->router->generate($resource->getCollectionRoute());

                if ($object instanceof Paginator) {
                    $data['@type'] = self::HYDRA_PAGED_COLLECTION;

                    $query = $object->getQuery();
                    $firstResult = $query->getFirstResult();
                    $maxResults = $query->getMaxResults();
                    $currentPage = floor($firstResult / $maxResults) + 1.;
                    $totalItems = count($object);
                    $lastPage = ceil($totalItems / $maxResults) ?: 1.;

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
            }

            return $data;
        }

        // Don't use hydra:Collection in sub levels
        $context['sub_level'] = true;

        $data['@id'] = $this->router->generate(
            $resource->getElementRoute(),
            ['id' => $this->propertyAccessor->getValue($object, 'id')]
        );
        $data['@type'] = $resource->getShortName();

        $attributes = $this->jsonLdClassMetadataFactory->getMetadataFor(
            $resource->getEntityClass(),
            $resource->getNormalizationGroups(),
            $resource->getDenormalizationGroups(),
            $resource->getValidationGroups()
        )->getAttributes();

        foreach ($attributes as $attributeName => $attribute) {
            if ($attribute->isReadable() && 'id' !== $attributeName) {
                $attributeValue = $this->propertyAccessor->getValue($object, $attributeName);

                if (isset($attribute->getTypes()[0])) {
                    $type = $attribute->getTypes()[0];

                    if (
                        $attributeValue &&
                        $type->isCollection() &&
                        ($collectionType = $type->getCollectionType()) &&
                        $class = $this->getClassHavingResource($collectionType)
                    ) {
                        $uris = [];
                        foreach ($attributeValue as $obj) {
                            $uris[] = $this->dataManipulator->getUriFromObject($obj, $class);
                        }

                        $attributeValue = $uris;
                    } elseif ($attributeValue && $class = $this->getClassHavingResource($type)) {
                        $attributeValue = $this->dataManipulator->getUriFromObject($attributeValue, $class);
                    }
                }

                if ($attributeValue instanceof \DateTime) {
                    $attributeValue = $attributeValue->format(\DateTime::ATOM);
                }

                $data[$attributeName] = $this->serializer->normalize($attributeValue, 'json', $context);
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

            if ($allowed && !$ignored) {
                if ($this->nameConverter) {
                    $attribute = $this->nameConverter->denormalize($attribute);
                }

                if (
                    isset($attributes[$attribute]->getTypes()[0]) &&
                    !is_null($value) &&
                    'object' === $attributes[$attribute]->getTypes()[0]->getType()
                ) {
                    if ($attributes[$attribute]->getTypes()[0]->isCollection()) {
                        $collection = [];
                        foreach ($value as $uri) {
                            if (!is_string($uri)) {
                                throw new InvalidArgumentException(sprintf(
                                    'Nested objects are not supported (found in attribute "%s")',
                                    $attribute
                                ));
                            }

                            $collection[] = $this->dataManipulator->getObjectFromUri($uri);
                        }

                        $value = $collection;
                    } elseif (is_string($value)) {
                        $value = $this->dataManipulator->getObjectFromUri($value);
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
        }

        return $object;
    }

    /**
     * Guesses the associated resource.
     *
     * @param mixed      $type
     * @param array|null $context
     *
     * @return \Dunglas\JsonLdApiBundle\JsonLd\Resource
     *
     * @throws InvalidArgumentException
     */
    private function guessResource($type, array $context = null)
    {
        if (isset($context['resource'])) {
            return $context['resource'];
        }

        if (is_object($type)) {
            $type = get_class($type);
        }

        if (!is_string($type)) {
            $type = gettype($type);
        }

        if ($resource = $this->resources->getResourceForEntity($type)) {
            return $resource;
        }

        throw new InvalidArgumentException(
            sprintf('Cannot find a resource object for type "%s".', $type)
        );
    }

    /**
     * Returns the class if a resource is associated with it.
     *
     * @param Type $type
     *
     * @return string|null
     */
    private function getClassHavingResource(Type $type)
    {
        if (
            'object' === $type->getType() &&
            ($class = $type->getClass()) &&
            $this->resources->getResourceForEntity($class)
        ) {
            return $class;
        }
    }
}
