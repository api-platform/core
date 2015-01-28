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
        ClassMetadataFactory $jsonLdClassMetadataFactory = null,
        NameConverterInterface $nameConverter = null,
        PropertyAccessorInterface $propertyAccessor = null
    ) {
        parent::__construct(
            $jsonLdClassMetadataFactory ? $jsonLdClassMetadataFactory->getSerializerClassMetadataFactory() : null,
            $nameConverter
        );

        $this->resources = $resources;
        $this->router = $router;
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

        $attributes = $this->jsonLdClassMetadataFactory->getMetadataFor($object)->getAttributes(
            $resource->getNormalizationGroups(),
            $resource->getDenormalizationGroups(),
            $resource->getValidationGroups()
        );

        foreach ($attributes as $attribute => $details) {
            if ('id' !== $attribute) {
                $attributeValue = $this->propertyAccessor->getValue($object, $attribute);

                if ($details['type']) {
                    $attributeContext = $context;
                    $attributeContext['resource'] = $this->resources->getResourceForEntity($details['type']);
                    $data[$attribute] = $this->serializer->normalize($attributeValue, $format, $attributeContext);

                    continue;
                }

                $data[$attribute] = $this->serializer->normalize($attributeValue, 'json', $context);
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
     * @param mixed $type
     * @param array $context
     *
     * @return \Dunglas\JsonLdApiBundle\Resource|null
     *
     * @throws InvalidArgumentException
     */
    private function guessResource($type, array $context)
    {
        if (isset($context['resource'])) {
            return $context['resource'];
        }

        if (is_object($type)) {
            $type = get_class($type);
        }

        if (is_string($type)) {
            if ($resource = $this->resources->getResourceForEntity($type)) {
                return $resource;
            }

            throw new InvalidArgumentException(
                sprintf('Cannot find a resource object for type "%s". Add a "resource" key in the context.', $type)
            );
        }

        throw new InvalidArgumentException(
            sprintf('Cannot find a resource object for type "%s". Add a "resource" key in the context.', gettype($type))
        );
    }
}
