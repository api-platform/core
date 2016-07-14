<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Hydra\Serializer;

use ApiPlatform\Core\DataProvider\PaginatorInterface;
use ApiPlatform\Core\JsonLd\Serializer\JsonLdContextTrait;
use ApiPlatform\Core\Routing\CollectionRoutingHelper;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Adds a view key to the result of a paginated Hydra collection.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Samuel ROZE <samuel.roze@gmail.com>
 */
final class PartialCollectionViewNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use JsonLdContextTrait;

    private $collectionNormalizer;
    private $pageParameterName;
    private $enabledParameterName;

    public function __construct(NormalizerInterface $collectionNormalizer, string $pageParameterName = 'page', string $enabledParameterName = 'pagination')
    {
        $this->collectionNormalizer = $collectionNormalizer;
        $this->pageParameterName = $pageParameterName;
        $this->enabledParameterName = $enabledParameterName;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        $data = $this->collectionNormalizer->normalize($object, $format, $context);
        if (isset($context['api_sub_level'])) {
            return $data;
        }

        if ($paginated = $object instanceof PaginatorInterface) {
            $currentPage = $object->getCurrentPage();
            $lastPage = $object->getLastPage();

            if (1. === $currentPage && 1. === $lastPage) {
                // Consider the collection not paginated if there is only one page
                $paginated = false;
            }
        }

        list($parts, $parameters) = CollectionRoutingHelper::parseRequestUri($context['request_uri'] ?? '/', $this->pageParameterName);
        $appliedFilters = $parameters;
        unset($appliedFilters[$this->enabledParameterName]);

        if ([] === $appliedFilters && !$paginated) {
            return $data;
        }

        $data['hydra:view'] = [
            '@id' => CollectionRoutingHelper::generateUrl($parts, $parameters, $this->pageParameterName, $paginated ? $currentPage : null),
            '@type' => 'hydra:PartialCollectionView',
        ];

        if ($paginated) {
            $data['hydra:view']['hydra:first'] = CollectionRoutingHelper::generateUrl($parts, $parameters, $this->pageParameterName, 1.);
            $data['hydra:view']['hydra:last'] = CollectionRoutingHelper::generateUrl($parts, $parameters, $this->pageParameterName, $lastPage);

            if (1. !== $currentPage) {
                $data['hydra:view']['hydra:previous'] = CollectionRoutingHelper::generateUrl($parts, $parameters, $this->pageParameterName, $currentPage - 1.);
            }

            if ($currentPage !== $lastPage) {
                $data['hydra:view']['hydra:next'] = CollectionRoutingHelper::generateUrl($parts, $parameters, $this->pageParameterName, $currentPage + 1.);
            }
        }

        return $data;
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
    public function setNormalizer(NormalizerInterface $normalizer)
    {
        if ($this->collectionNormalizer instanceof NormalizerAwareInterface) {
            $this->collectionNormalizer->setNormalizer($normalizer);
        }
    }
}
