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

use Dunglas\ApiBundle\Api\ResourceCollectionInterface;
use Dunglas\ApiBundle\Api\ResourceResolver;
use Dunglas\ApiBundle\JsonLd\ContextBuilder;
use Dunglas\ApiBundle\Model\PaginatorInterface;
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
    use ResourceResolver;

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
    public function normalize($object, $format = null, array $context = array())
    {
        $resource = $this->guessResource($object, $context);
        list($context, $data) = $this->contextBuilder->bootstrap($resource, $context);

        if (isset($context['json_ld_sub_level'])) {
            $data = [];
            foreach ($object as $obj) {
                $data[] = $this->serializer->normalize($obj, $format, $context);
            }
        } else {
            $data['@id'] = $context['request_uri'];

            if ($object instanceof PaginatorInterface) {
                $data['@type'] = self::HYDRA_PAGED_COLLECTION;

                $currentPage = $object->getCurrentPage();
                $lastPage = $object->getLastPage();

                if (1. !== $currentPage) {
                    $previousPage = $currentPage - 1.;
                    $data['hydra:previousPage'] = $this->getPageUrl($context['request_uri'], $previousPage);
                }

                if ($currentPage !== $lastPage) {
                    $data['hydra:nextPage'] = $this->getPageUrl($context['request_uri'], $currentPage + 1.);
                }

                $data['hydra:totalItems'] = $object->getTotalItems();
                $data['hydra:itemsPerPage'] = $object->getItemsPerPage();
                $data['hydra:firstPage'] = $this->getPageUrl($context['request_uri'], 1.);
                $data['hydra:lastPage'] = $this->getPageUrl($context['request_uri'], $lastPage);
            } else {
                $data['@type'] = self::HYDRA_COLLECTION;
            }

            $data['hydra:member'] = [];
            foreach ($object as $obj) {
                $data['hydra:member'][] = $this->serializer->normalize($obj, $format, $context);
            }
        }

        return $data;
    }

    /**
     * Gets a collection URL for the given page.
     *
     * @param string $requestUri
     * @param float  $page
     *
     * @return string
     */
    private function getPageUrl($requestUri, $page)
    {
        $parts = parse_url($requestUri);

        $parameters = [];
        if (isset($parts['query'])) {
            parse_str($parts['query'], $parameters);

            // Remove existing page parameter
            if (isset($parameters[$this->pageParameterName])) {
                unset($parameters[$this->pageParameterName]);
            }
        }

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
}
