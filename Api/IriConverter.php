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
    ) {
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
                $this->getRouteName($resource, 'item'),
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
        return $this->router->generate($this->getRouteName($resource, 'collection'), [], $referenceType);
    }

    /**
     * {@inheritdoc}
     */
    public function hasIriFromResource(ResourceInterface $resource)
    {
        return null !== $this->getRouteName($resource, 'collection');
    }

    /**
     * Gets the route name related to a resource.
     *
     * @param ResourceInterface $resource
     *
     * @return string
     */
    private function getRouteName(ResourceInterface $resource, $prefix)
    {
        if (!$this->routeCache->contains($resource)) {
            $this->routeCache[$resource] = [];
        }

        $key = $prefix.'RouteName';

        if (isset($this->routeCache[$resource][$key])) {
            return $this->routeCache[$resource][$key];
        }

        $operations = 'item' === $prefix ? $resource->getItemOperations() : $resource->getCollectionOperations();
        foreach ($operations as $operation) {
            $methods = $operation->getRoute()->getMethods();
            if (empty($methods) || in_array('GET', $methods)) {
                $data = $this->routeCache[$resource];
                $data[$key] = $operation->getRouteName();
                $this->routeCache[$resource] = $data;

                return $data[$key];
            }
        }

        $data = $this->routeCache[$resource];
        $data[$key] = null;
        $this->routeCache[$resource] = $data;
        return null;
    }
}
