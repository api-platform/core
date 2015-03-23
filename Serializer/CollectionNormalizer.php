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
use Dunglas\JsonLdApiBundle\JsonLd\ContextBuilder;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\SerializerAwareNormalizer;

/**
 * This normalizer handle collections and paginated collections.
 *
 * @author Samuel ROZE <samuel.roze@gmail.com>
 * @author Kevin Dunglas <dunglas@gmail.com>
 */
class CollectionNormalizer extends SerializerAwareNormalizer implements NormalizerInterface
{
    const HYDRA_COLLECTION = 'hydra:Collection';
    const HYDRA_PAGED_COLLECTION = 'hydra:PagedCollection';

    /**
     * @var ResourceResolver
     */
    private $resourceResolver;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var ContextBuilder
     */
    private $contextBuilder;

    /**
     * @param ResourceResolver $resourceResolver
     * @param RouterInterface $router
     * @param ContextBuilder $contextBuilder
     */
    public function __construct(ResourceResolver $resourceResolver, RouterInterface $router, ContextBuilder $contextBuilder)
    {
        $this->resourceResolver = $resourceResolver;
        $this->router = $router;
        $this->contextBuilder = $contextBuilder;
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
        $resource = $this->resourceResolver->guessResource($object, $context);
        list($context, $data) = $this->contextBuilder->bootstrap($resource, $context);

        if (isset($context['json_ld_sub_level'])) {
            $data = [];
            foreach ($object as $obj) {
                $data[] = $this->serializer->normalize($obj, $format, $context);
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

            $data['hydra:member'] = [];
            foreach ($object as $obj) {
                $data['hydra:member'][] = $this->serializer->normalize($obj, $format, $context);
            }
        }

        return $data;
    }
}
