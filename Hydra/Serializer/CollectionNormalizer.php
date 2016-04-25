<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Hydra\Serializer;

use Dunglas\ApiBundle\Api\Filter\FilterInterface;
use Dunglas\ApiBundle\Api\ResourceCollectionInterface;
use Dunglas\ApiBundle\Api\ResourceInterface;
use Dunglas\ApiBundle\Api\ResourceResolverTrait;
use Dunglas\ApiBundle\JsonLd\ContextBuilder;
use Dunglas\ApiBundle\JsonLd\Serializer\ContextTrait;
use Dunglas\ApiBundle\Model\PaginatorInterface;
use Dunglas\ApiBundle\Util\RequestParser;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\SerializerAwareNormalizer;

/**
 * This normalizer handles collections and paginated collections.
 *
 * @author Kevin Dunglas <dunglas@gmail.com>
 * @author Samuel ROZE <samuel.roze@gmail.com>
 */
class CollectionNormalizer extends SerializerAwareNormalizer implements NormalizerInterface
{
    use ResourceResolverTrait;
    use ContextTrait;

    /**
     * @var string
     */
    const HYDRA_COLLECTION = 'hydra:Collection';
    /**
     * @var string
     */
    const HYDRA_PAGED_COLLECTION = 'hydra:PagedCollection';

    /**
     * @var ContextBuilder
     */
    private $contextBuilder;
    /**
     * @var string
     */
    private $pageParameterName;

    /**
     * @param ResourceCollectionInterface $resourceCollection
     * @param ContextBuilder              $contextBuilder
     * @param string                      $pageParameterName
     */
    public function __construct(
        ResourceCollectionInterface $resourceCollection,
        ContextBuilder $contextBuilder,
        $pageParameterName
    ) {
        $this->resourceCollection = $resourceCollection;
        $this->contextBuilder = $contextBuilder;
        $this->pageParameterName = $pageParameterName;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return 'json-ld' === $format && (is_array($data) || $data instanceof \Traversable);
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        $resource = $this->guessResource($object, $context);

        if (isset($context['json_ld_sub_level'])) {
            $data = [];
            foreach ($object as $index => $obj) {
                $data[$index] = $this->serializer->normalize($obj, $format, $context);
            }
        } else {
            $context = $this->createContext($resource, $context);

            $data = [
                '@context' => $this->contextBuilder->getResourceContext($resource, $context),
                '@id' => $context['request_uri'],
            ];
            list($parts, $parameters) = $this->parseRequestUri($context['request_uri']);

            if ($object instanceof PaginatorInterface) {
                $data['@type'] = self::HYDRA_PAGED_COLLECTION;

                $currentPage = $object->getCurrentPage();
                $lastPage = $object->getLastPage();

                if (1. !== $currentPage) {
                    $previousPage = $currentPage - 1.;
                    $data['hydra:previousPage'] = $this->getPageUrl($parts, $parameters, $previousPage);
                }

                if ($currentPage !== $lastPage) {
                    $data['hydra:nextPage'] = $this->getPageUrl($parts, $parameters, $currentPage + 1.);
                }

                $data['hydra:totalItems'] = $object->getTotalItems();
                $data['hydra:itemsPerPage'] = $object->getItemsPerPage();
                $data['hydra:firstPage'] = $this->getPageUrl($parts, $parameters, 1.);
                $data['hydra:lastPage'] = $this->getPageUrl($parts, $parameters, $lastPage);
            } else {
                $data['@type'] = self::HYDRA_COLLECTION;
            }

            $data['hydra:member'] = [];
            foreach ($object as $obj) {
                $data['hydra:member'][] = $this->serializer->normalize($obj, $format, $context);
            }

            $filters = $resource->getFilters();
            if (!empty($filters)) {
                $data['hydra:search'] = $this->getSearch($resource, $parts, $filters);
            }
        }

        return $data;
    }

    /**
     * Parse and standardize the request URI.
     *
     * @param string $requestUri
     *
     * @return array
     */
    private function parseRequestUri($requestUri)
    {
        $parts = parse_url($requestUri);
        if (false === $parts) {
            throw new \InvalidArgumentException(sprintf('The request URI "%s" is malformed.', $requestUri));
        }

        $parameters = [];
        if (isset($parts['query'])) {
            $parameters = RequestParser::parseRequestParams($parts['query']);

            // Remove existing page parameter
            if (isset($parameters[$this->pageParameterName])) {
                unset($parameters[$this->pageParameterName]);
            }
        }

        return [$parts, $parameters];
    }

    /**
     * Gets a collection URL for the given page.
     *
     * @param array $parts
     * @param array $parameters
     * @param float $page
     *
     * @return string
     */
    private function getPageUrl(array $parts, array $parameters, $page)
    {
        if (1. !== $page) {
            $parameters[$this->pageParameterName] = $page;
        }

        $parts['query'] = http_build_query($parameters);

        $url = $parts['path'];

        if ('' !== $parts['query']) {
            $url .= '?'.$parts['query'];
        }

        return $url;
    }

    /**
     * Returns the content of the Hydra search property.
     *
     * @param ResourceInterface $resource
     * @param array             $parts
     * @param FilterInterface[] $filters
     *
     * @return array
     */
    private function getSearch(ResourceInterface $resource, array $parts, array $filters)
    {
        $variables = [];
        $mapping = [];
        foreach ($filters as $filter) {
            foreach ($filter->getDescription($resource) as $variable => $data) {
                $variables[] = $variable;
                $mapping[] = [
                    '@type' => 'IriTemplateMapping',
                    'variable' => $variable,
                    'property' => $data['property'],
                    'required' => $data['required'],
                ];
            }
        }

        return [
            '@type' => 'hydra:IriTemplate',
            'hydra:template' => sprintf('%s{?%s}', $parts['path'], implode(',', $variables)),
            'hydra:variableRepresentation' => 'BasicRepresentation',
            'hydra:mapping' => $mapping,
        ];
    }
}
