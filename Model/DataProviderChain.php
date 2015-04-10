<?php

/*
 * This file is part of the DunglasJsonLdApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\JsonLdApiBundle\Model;

use Dunglas\JsonLdApiBundle\Api\ResourceCollectionInterface;
use Dunglas\JsonLdApiBundle\Api\ResourceInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * A chain of data providers.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class DataProviderChain implements DataProviderInterface
{
    /**
     * @var ResourceCollectionInterface
     */
    private $resourceCollection;
    /**
     * @var RouterInterface
     */
    private $router;
    /**
     * @var DataProviderInterface[]
     */
    private $dataProviders;

    /**
     * @param ResourceCollectionInterface $resourceCollection
     * @param RouterInterface             $router
     * @param DataProviderInterface[]     $dataProviders
     */
    public function __construct(ResourceCollectionInterface $resourceCollection, RouterInterface $router, array $dataProviders)
    {
        $this->resourceCollection = $resourceCollection;
        $this->router = $router;
        $this->dataProviders = $dataProviders;
    }

    /**
     * {@inheritdoc}
     */
    public function getItem(ResourceInterface $resource, $id, $fetchData = false)
    {
        foreach ($this->dataProviders as $dataProvider) {
            if ($dataProvider->supports($resource) && $result = $dataProvider->getItem($resource, $id, $fetchData)) {
                return $result;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getItemFromIri($iri, $fetchData = false)
    {
        $parameters = $this->router->match($iri);
        if (
            !isset($parameters['_resource']) ||
            !isset($parameters['id']) ||
            !($resource = $this->resourceCollection->getResourceForShortName($parameters['_resource']))
        ) {
            throw new \InvalidArgumentException(sprintf('No resource associated with the IRI "%s".', $iri));
        }

        return $this->getItem($resource, $parameters['id'], $fetchData);
    }

    /**
     * {@inheritdoc}
     */
    public function getCollection(ResourceInterface $resource, array $filters = [], array $order = [], $page = null, $itemsPerPage = null)
    {
        foreach ($this->dataProviders as $dataProvider) {
            if ($dataProvider->supports($resource) &&
                $result = $dataProvider->getCollection($resource, $filters, $order, $page, $itemsPerPage)) {
                return $result;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supports(ResourceInterface $resource)
    {
        return true;
    }
}
