<?php

/*
 * This file is part of the DunglasJsonLdApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\JsonLdApiBundle\JsonLd;

use Doctrine\Common\Inflector\Inflector;
use Dunglas\JsonLdApiBundle\Model\DataProviderInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class representing a JSON-LD/Hydra resource.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class Resource implements ResourceInterface
{
    /**
     * @var string
     */
    const ROUTE_NAME_PREFIX = 'json_ld_api_';

    /**
     * @var string
     */
    private $entityClass;
    /**
     * @var DataProviderInterface
     */
    private $dataProvider;
    /**
     * @var ResourceCollection
     */
    private $resourceCollection;
    /**
     * @var array
     */
    private $filters = [];
    /**
     * @var array
     */
    private $normalizationContext = [];
    /**
     * @var array
     */
    private $denormalizationContext = [];
    /**
     * @var array|null
     */
    private $validationGroups;
    /**
     * @var string|null
     */
    private $shortName;
    /**
     * @var string|null
     */
    private $beautifiedName;
    /**
     * @var array
     */
    private $collectionOperations = [
        ['hydra:method' => 'GET'],
        ['hydra:method' => 'POST'],
    ];
    /**
     * @var array
     */
    private $itemOperations = [
        ['hydra:method' => 'GET'],
        ['hydra:method' => 'PUT'],
        ['hydra:method' => 'DELETE'],
    ];
    /**
     * @var string
     */
    private $controllerName = 'DunglasJsonLdApiBundle:Resource';
    /**
     * @var RouteCollection|null
     */
    private $routeCollection;
    /**
     * @var string|null
     */
    private $itemRoute;
    /**
     * @var string|null
     */
    private $collectionRoute;
    /**
     * @var bool
     */
    private $populatedFilters = false;
    /**
     * @var bool
     */
    private $populatedCollectionOperations = false;
    /**
     * @var bool
     */
    private $populatedItemOperations = false;

    /**
     * @param string                $entityClass
     * @param DataProviderInterface $dataProvider
     */
    public function __construct(
        $entityClass,
        DataProviderInterface $dataProvider
    ) {
        if (!class_exists($entityClass)) {
            throw new \InvalidArgumentException(sprintf('The class %s does not exist.', $entityClass));
        }

        $this->entityClass = $entityClass;
        $this->shortName = substr($this->entityClass, strrpos($this->entityClass, '\\') + 1);
        $this->dataProvider = $dataProvider;
        $this->dataProvider->setResource($this);
    }

    /**
     * Gets the associated entity class.
     *
     * @return string
     */
    public function getEntityClass()
    {
        return $this->entityClass;
    }

    /**
     * Gets the related data provider.
     *
     * @return DataProviderInterface
     */
    public function getDataProvider()
    {
        return $this->dataProvider;
    }

    /**
     * Sets the resource collection.
     *
     * @param ResourceCollectionInterface $resourceCollection
     */
    public function setResourceCollection(ResourceCollectionInterface $resourceCollection)
    {
        $this->resourceCollection = $resourceCollection;
    }

    /**
     * Gets the resource collection.
     *
     * @return ResourceCollectionInterface
     */
    public function getResourceCollection()
    {
        return $this->resourceCollection;
    }

    /**
     * Sets filters.
     *
     * @param array $filters
     */
    public function setFilters(array $filters)
    {
        $this->filters = $filters;
    }

    /**
     * Gets filters available for this resource.
     *
     * @return array
     */
    public function getFilters()
    {
        if (!$this->populatedFilters) {
            foreach ($this->filters as $key => $filter) {
                if (!isset($this->filters[$key]['exact'])) {
                    $this->filters[$key]['exact'] = true;
                }
            }

            $this->populatedFilters = true;
        }

        return $this->filters;
    }

    /**
     * Sets normalization context.
     *
     * @param array $normalizationContext
     */
    public function setNormalizationContext(array $normalizationContext)
    {
        $this->normalizationContext = $normalizationContext;
    }

    /**
     * Gets the normalization context.
     *
     * @return array
     */
    public function getNormalizationContext()
    {
        if (!isset($this->normalizationContext['resource'])) {
            $this->normalizationContext['resource'] = $this;
        }

        return $this->normalizationContext;
    }

    /**
     * Gets normalization groups.
     *
     * @return string[]|null
     */
    public function getNormalizationGroups()
    {
        return isset($this->normalizationContext['groups']) ? $this->normalizationContext['groups'] : null;
    }

    /**
     * Sets denormalization context.
     *
     * @param array $denormalizationContext
     */
    public function setDenormalizationContext(array $denormalizationContext)
    {
        $this->denormalizationContext = $denormalizationContext;
    }

    /**
     * Gets the denormalization context.
     *
     * @return array
     */
    public function getDenormalizationContext()
    {
        if (!isset($this->denormalizationContext['resource'])) {
            $this->denormalizationContext['resource'] = $this;
        }

        return $this->denormalizationContext;
    }

    /**
     * Gets denormalization groups.
     *
     * @return string[]|null
     */
    public function getDenormalizationGroups()
    {
        return isset($this->denormalizationContext['groups']) ? $this->denormalizationContext['groups'] : null;
    }

    /**
     * Sets validation groups.
     *
     * @param array $validationGroups
     */
    public function setValidationGroups(array $validationGroups)
    {
        $this->validationGroups = $validationGroups;
    }

    /**
     * Gets validation groups to use.
     *
     * @return string[]|null
     */
    public function getValidationGroups()
    {
        return $this->validationGroups;
    }

    /**
     * Sets short name.
     *
     * @param string $shortName
     */
    public function setShortName($shortName)
    {
        $this->shortName = $shortName;
    }

    /**
     * Gets the short name (display name) of the resource.
     *
     * @return string
     */
    public function getShortName()
    {
        return $this->shortName;
    }

    /**
     * Sets controller name.
     *
     * @param string $controllerName
     */
    public function setControllerName($controllerName)
    {
        $this->controllerName = $controllerName;
    }

    /**
     * Gets the controller name.
     *
     * @return string
     */
    public function getControllerName()
    {
        return $this->controllerName;
    }

    /**
     * Gets the route collection for this resource.
     *
     * @return RouteCollection
     */
    public function getRouteCollection()
    {
        if ($this->routeCollection) {
            return $this->routeCollection;
        }

        $this->routeCollection = new RouteCollection();
        $collectionOperations = $this->getCollectionOperations();
        foreach ($collectionOperations as &$collectionOperation) {
            $this->addRoute($collectionOperation, true);
        }

        $itemOperations = $this->getItemOperations();
        foreach ($itemOperations as &$itemOperation) {
            $this->addRoute($itemOperation, false);
        }

        return $this->routeCollection;
    }

    /**
     * Gets the route associated with the collection.
     *
     * @return string
     */
    public function getCollectionRoute()
    {
        if (!$this->collectionRoute) {
            $this->getRouteCollection();
        }

        return $this->collectionRoute;
    }

    /**
     * Gets route associated with an item.
     *
     * @return string
     */
    public function getItemRoute()
    {
        if (!$this->itemRoute) {
            $this->getRouteCollection();
        }

        return $this->itemRoute;
    }

    /**
     * Sets item operations.
     *
     * @param array $itemOperations
     */
    public function setItemOperations(array $itemOperations)
    {
        $this->itemOperations = $itemOperations;
    }

    /**
     * Gets item operations.
     *
     * @return array
     */
    public function getItemOperations()
    {
        if (!$this->populatedItemOperations) {
            foreach ($this->itemOperations as $key => $operation) {
                $this->itemOperations[$key] = $this->populateOperation($operation, false);
            }

            $this->populatedItemOperations = true;
        }

        return $this->itemOperations;
    }

    /**
     * Sets collection operations.
     *
     * @param array $collectionOperations
     */
    public function setCollectionOperations(array $collectionOperations)
    {
        $this->collectionOperations = $collectionOperations;
    }

    /**
     * Get collection operations.
     *
     * @return array
     */
    public function getCollectionOperations()
    {
        if (!$this->populatedCollectionOperations) {
            foreach ($this->collectionOperations as $key => $operation) {
                $this->collectionOperations[$key] = $this->populateOperation($operation, true);
            }

            $this->populatedCollectionOperations = true;
        }

        return $this->collectionOperations;
    }

    /**
     * Gets the short name of the resource pluralized and camel cased.
     *
     * @return string
     */
    public function getBeautifiedName()
    {
        if (!$this->beautifiedName) {
            $this->beautifiedName = Inflector::pluralize(Inflector::tableize($this->shortName));
        }

        return $this->beautifiedName;
    }

    /**
     * Populates default operation values.
     *
     * @param array $operation
     * @param bool  $isCollection
     *
     * @return array
     */
    private function populateOperation(array $operation, $isCollection)
    {
        $prefixedShortName = sprintf('#%s', $this->shortName);
        $beautifiedName = $this->getBeautifiedName();

        if (!isset($operation['hydra:method'])) {
            $operation['hydra:method'] = 'GET';
        }

        if ($isCollection) {
            if ('POST' === $operation['hydra:method']) {
                if (!isset($operation['@type'])) {
                    $operation['@type'] = 'hydra:CreateResourceOperation';
                }

                if (!isset($operation['hydra:title'])) {
                    $operation['hydra:title'] = sprintf('Creates a %s resource.', $this->shortName);
                }

                if (!isset($operation['expects'])) {
                    $operation['expects'] = $prefixedShortName;
                }

                if (!isset($operation['returns'])) {
                    $operation['returns'] = $prefixedShortName;
                }
            } else {
                if (!isset($operation['@type'])) {
                    $operation['@type'] = 'hydra:Operation';
                }

                if ('GET' === $operation['hydra:method']) {
                    if (!isset($operation['hydra:title'])) {
                        $operation['hydra:title'] = sprintf('Retrieves the collection of %s resources.', $this->shortName);
                    }

                    if (!isset($operation['returns'])) {
                        $operation['returns'] = 'hydra:PagedCollection';
                    }
                }
            }
        } else {
            if ('PUT' === $operation['hydra:method']) {
                if (!isset($operation['@type'])) {
                    $operation['@type'] = 'hydra:ReplaceResourceOperation';
                }

                if (!isset($operation['hydra:title'])) {
                    $operation['hydra:title'] = sprintf('Replaces the %s resource.', $this->shortName);
                }

                if (!isset($operation['returns'])) {
                    $operation['returns'] = $prefixedShortName;
                }

                if (!isset($operation['expects'])) {
                    $operation['expects'] = $prefixedShortName;
                }
            } elseif ('DELETE' === $operation['hydra:method']) {
                if (!isset($operation['@type'])) {
                    $operation['@type'] = 'hydra:Operation';
                }

                if (!isset($operation['hydra:title'])) {
                    $operation['hydra:title'] = sprintf('Deletes the %s resource.', $this->shortName);
                }

                if (!isset($operation['expects'])) {
                    $operation['returns'] = 'owl:Nothing';
                }
            } elseif ('GET' === $operation['hydra:method']) {
                if (!isset($operation['@type'])) {
                    $operation['@type'] = 'hydra:Operation';
                }

                if (!isset($operation['hydra:title'])) {
                    $operation['hydra:title'] = sprintf('Retrieves %s resource.', $this->shortName);
                }

                if (!isset($operation['returns'])) {
                    $operation['returns'] = $prefixedShortName;
                }
            }
        }

        if (!isset($operation['rdfs:label'])) {
            $operation['rdfs:label'] = $operation['hydra:title'];
        }

        $action = $operation['hydra:method'] === 'GET' && $isCollection ? 'cget' : strtolower($operation['hydra:method']);

        // Use ! as ignore character because @ and are _ reserved JSON-LD characters
        if (!isset($operation['!controller'])) {
            $operation['!controller'] = sprintf('%s:%s', $this->controllerName, $action);
        }

        if (!isset($operation['!route_name'])) {
            $operation['!route_name'] = sprintf('%s%s_%s', self::ROUTE_NAME_PREFIX, $beautifiedName, $action);
        }

        if (!isset($operation['!route_path'])) {
            $operation['!route_path'] = '/'.$beautifiedName.($isCollection ? '' : '/{id}');
        }

        return $operation;
    }

    /**
     * Adds a route to the collection.
     *
     * @param array $operation
     * @param bool  $isCollection
     */
    private function addRoute(array &$operation, $isCollection)
    {
        $methods = 'GET' === $operation['hydra:method'] ? ['GET', 'HEAD'] : [$operation['hydra:method']];
        $route = new Route(
            $operation['!route_path'],
            [
                '_controller' => $operation['!controller'],
                '_json_ld_resource' => $this->shortName,
            ],
            [],
            [],
            '',
            [],
            $methods
        );

        $this->routeCollection->add($operation['!route_name'], $route);
        $operation['!route'] = $route;

        // Set routes
        if ('GET' === $operation['hydra:method']) {
            if (!$this->collectionRoute && $isCollection) {
                $this->collectionRoute = $operation['!route_name'];
            }

            if (!$this->itemRoute && !$isCollection) {
                $this->itemRoute = $operation['!route_name'];
            }
        }
    }
}
