<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Api;

use Dunglas\ApiBundle\Model\DataProviderInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\RouterInterface;

/**
 * {@inheritdoc}
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class IriConverter implements IriConverterInterface
{
    /**
     * @var ResourceCollectionInterface
     */
    private $resourceCollection;
    /**
     * @var DataProviderInterface
     */
    private $dataProvider;
    /**
     * @var RouterInterface
     */
    private $router;
    /**
     * @var PropertyAccessorInterface
     */
    private $propertyAccessor;
    /**
     * @var \SplObjectStorage
     */
    private $routeCache;

    public function __construct(
        ResourceCollectionInterface $resourceCollection,
        DataProviderInterface $dataProvider,
        RouterInterface $router,
        PropertyAccessorInterface $propertyAccessor
    )
    {
        $this->resourceCollection = $resourceCollection;
        $this->dataProvider = $dataProvider;
        $this->router = $router;
        $this->propertyAccessor = $propertyAccessor;
        $this->routeCache = new \SplObjectStorage();
    }

    /**
     * {@inheritdoc}
     */
    public function getItemFromIri($iri, $fetchData = false)
    {
        try {
            $parameters = $this->router->match($iri);
        } catch (ResourceNotFoundException $e) {
            return;
        }

        if (
            !isset($parameters['_resource']) ||
            !isset($parameters['id']) ||
            !($resource = $this->resourceCollection->getResourceForShortName($parameters['_resource']))
        ) {
            throw new \InvalidArgumentException(sprintf('No resource associated with the IRI "%s".', $iri));
        }

        return $this->dataProvider->getItem($resource, $parameters['id'], $fetchData);
    }

    /**
     * {@inheritdoc}
     */
    public function getIriFromItem($item, $referenceType = RouterInterface::ABSOLUTE_PATH)
    {
        if ($resource = $this->resourceCollection->getResourceForEntity($item)) {
            return $this->router->generate(
                $this->getItemRouteName($resource),
                ['id' => $this->propertyAccessor->getValue($item, 'id')],
                $referenceType
            );
        }

        throw new \InvalidArgumentException(sprintf('No resource associated with the type "%s".', get_class($item)));
    }

    /**
     * {@inheritdoc}
     */
    public function getIriFromResource(ResourceInterface $resource, $referenceType = RouterInterface::ABSOLUTE_PATH)
    {
        return $this->router->generate($this->getCollectionRouteName($resource, [], $referenceType));
    }

    /**
     * Gets the collection route name for a resource.
     *
     * @param ResourceInterface $resource
     *
     * @return string
     */
    private function getCollectionRouteName(ResourceInterface $resource)
    {
        $this->initRouteCache($resource);

        if (isset($this->routeCache[$resource]['collectionRouteName'])) {
            return $this->routeCache[$resource]['collectionRouteName'];
        }

        $operations = $resource->getCollectionOperations();
        foreach ($operations as $operation) {
            if (in_array('GET', $operation->getRoute()->getMethods())) {
                $data = $this->routeCache[$resource];
                $data['collectionRouteName'] = $operation->getRouteName();
                $this->routeCache[$resource] = $data;

                return $data['collectionRouteName'];
            }
        }
    }

    /**
     * Gets the item route name for a resource.
     *
     * @param ResourceInterface $resource
     *
     * @return string
     */
    private function getItemRouteName(ResourceInterface $resource)
    {
        $this->initRouteCache($resource);

        if (isset($this->routeCache[$resource]['itemRouteName'])) {
            return $this->routeCache[$resource]['itemRouteName'];
        }

        $operations = $resource->getitemOperations();
        foreach ($operations as $operation) {
            if (in_array('GET', $operation->getRoute()->getMethods())) {
                $data = $this->routeCache[$resource];
                $data['itemRouteName'] = $operation->getRouteName();
                $this->routeCache[$resource] = $data;

                return $data['itemRouteName'];
            }
        }
    }

    /**
     * Initializes the route cache structure for the given resource.
     *
     * @param ResourceInterface $resource
     */
    private function initRouteCache(ResourceInterface $resource)
    {
        if (!$this->routeCache->contains($resource)) {
            $this->routeCache[$resource] = [];
        }
    }
}
