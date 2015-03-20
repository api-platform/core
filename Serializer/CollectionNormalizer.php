<?php
namespace Dunglas\JsonLdApiBundle\Serializer;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\SerializerAwareNormalizer;

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
     * @param ResourceResolver $resourceResolver
     * @param RouterInterface $router
     */
    public function __construct(ResourceResolver $resourceResolver, RouterInterface $router)
    {
        $this->resourceResolver = $resourceResolver;
        $this->router = $router;
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

        $data = [];
        if (!isset($context['has_json_ld_context'])) {
            $data['@context'] = $this->router->generate(
                'json_ld_api_context',
                ['shortName' => $resource->getShortName()]
            );
            $context['has_json_ld_context'] = true;
        }

        if (isset($context['sub_level'])) {
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
