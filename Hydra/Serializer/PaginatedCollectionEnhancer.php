<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Hydra\Serializer;

use Dunglas\ApiBundle\Api\ResourceInterface;
use Dunglas\ApiBundle\Api\ResourceResolver;
use Dunglas\ApiBundle\Model\PaginatorInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\SerializerAwareNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Enhance the result of collection by enabling pagination.
 *
 * @author Samuel ROZE <samuel.roze@gmail.com>
 */
class PaginatedCollectionEnhancer extends SerializerAwareNormalizer implements NormalizerInterface
{
    /**
     * @var string
     */
    const HYDRA_PAGED_COLLECTION = 'hydra:PagedCollection';

    /**
     * @var CollectionNormalizer
     */
    private $collectionNormalizer;
    /**
     * @var ResourceResolver
     */
    private $resourceResolver;

    /**
     * @param NormalizerInterface $collectionNormalizer
     * @param ResourceResolver    $resourceResolver
     */
    public function __construct(NormalizerInterface $collectionNormalizer, ResourceResolver $resourceResolver)
    {
        $this->collectionNormalizer = $collectionNormalizer;
        $this->resourceResolver = $resourceResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        $data = $this->collectionNormalizer->normalize($object, $format, $context);
        if (isset($context['jsonld_sub_level']) || !$object instanceof PaginatorInterface) {
            return $data;
        }

        $resource = $this->resourceResolver->guessResource($object, $context);
        list($parts, $parameters) = $this->parseRequestUri($resource, $context['request_uri']);

        $data['@type'] = self::HYDRA_PAGED_COLLECTION;

        $currentPage = $object->getCurrentPage();
        $lastPage = $object->getLastPage();

        if (1. !== $currentPage) {
            $previousPage = $currentPage - 1.;
            $data['hydra:previousPage'] = $this->getPageUrl($resource, $parts, $parameters, $previousPage);
        }

        if ($currentPage !== $lastPage) {
            $data['hydra:nextPage'] = $this->getPageUrl($resource, $parts, $parameters, $currentPage + 1.);
        }

        $data['hydra:totalItems'] = $object->getTotalItems();
        $data['hydra:itemsPerPage'] = $object->getItemsPerPage();
        $data['hydra:firstPage'] = $this->getPageUrl($resource, $parts, $parameters, 1.);
        $data['hydra:lastPage'] = $this->getPageUrl($resource, $parts, $parameters, $lastPage);

        // Reorder the `hydra:member` key to the end
        $members = $data['hydra:member'];
        unset($data['hydra:member']);
        $data['hydra:member'] = $members;

        return $data;
    }

    /**
     * Parse and standardize the request URI.
     *
     * @param ResourceInterface $resource
     * @param string            $requestUri
     *
     * @return array
     */
    private function parseRequestUri(ResourceInterface $resource, $requestUri)
    {
        $parts = parse_url($requestUri);
        if (false === $parts) {
            throw new \InvalidArgumentException(sprintf('The request URI "%s" is malformed.', $requestUri));
        }

        $parameters = [];
        if (isset($parts['query'])) {
            parse_str($parts['query'], $parameters);

            // Remove existing page parameter
            if (isset($parameters[$resource->getPageParameter()])) {
                unset($parameters[$resource->getPageParameter()]);
            }
        }

        return [$parts, $parameters];
    }

    /**
     * Gets a collection URL for the given page.
     *
     * @param ResourceInterface $resource
     * @param array             $parts
     * @param array             $parameters
     * @param float             $page
     *
     * @return string
     */
    private function getPageUrl(ResourceInterface $resource, array $parts, array $parameters, $page)
    {
        if (1. !== $page) {
            $parameters[$resource->getPageParameter()] = $page;
        }

        $query = http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);
        $parts['query'] = preg_replace('/%5B[0-9]+%5D/', '%5B%5D', $query);

        $url = $parts['path'];

        if ('' !== $parts['query']) {
            $url .= '?'.$parts['query'];
        }

        return $url;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return $this->collectionNormalizer->supportsNormalization($data, $format);
    }

    /**
     * {@inheritdoc}
     */
    public function setSerializer(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;

        if ($this->collectionNormalizer instanceof SerializerAwareNormalizer) {
            $this->collectionNormalizer->setSerializer($serializer);
        }
    }
}
