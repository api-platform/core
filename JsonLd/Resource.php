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
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class representing a JSON-LD/Hydra resource.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class Resource
{
    /**
     * @var string
     */
    const ROUTE_NAME_PREFIX = 'json_ld_api_';

    /**
     * @var array
     */
    protected static $defaultCollectionOperations = [
        ['hydra:method' => 'GET'],
        ['hydra:method' => 'POST'],
    ];
    /**
     * @var array
     */
    protected static $defaultItemOperations = [
        ['hydra:method' => 'GET'],
        ['hydra:method' => 'PUT'],
        ['hydra:method' => 'DELETE'],
    ];
    /**
     * @var array
     */
    protected static $defaultFilter = [
        'exact' => true,
    ];
    /**
     * @var string
     */
    protected $entityClass;
    /**
     * @var array
     */
    protected $filters = [];
    /**
     * @var array
     */
    protected $normalizationContext;
    /**
     * @var array
     */
    protected $denormalizationContext;
    /**
     * @var array|null
     */
    protected $validationGroups;
    /**
     * @var array
     */
    protected $serializerContext;
    /**
     * @var string
     */
    protected $shortName;
    /**
     * @var string
     */
    protected $beautifiedName;
    /**
     * @var array
     */
    protected $collectionOperations;
    /**
     * @var array
     */
    protected $itemOperations;
    /**
     * @var string
     */
    protected $controllerName;
    /**
     * @var RouteCollection|null
     */
    protected $routeCollection;
    /**
     * @var string|null
     */
    protected $elementRoute;
    /**
     * @var string|null
     */
    protected $collectionRoute;

    /**
     * @param string      $entityClass
     * @param array       $filters
     * @param array       $normalizationContext
     * @param array       $denormalizationContext
     * @param array|null  $validationGroups
     * @param string|null $shortName
     * @param array|null  $collectionOperations
     * @param array|null  $itemOperations
     * @param string      $controllerName
     */
    public function __construct(
        $entityClass,
        array $filters = [],
        array $normalizationContext = [],
        array $denormalizationContext = [],
        array $validationGroups = null,
        $shortName = null,
        array $collectionOperations = null,
        array $itemOperations = null,
        $controllerName = 'DunglasJsonLdApiBundle:Resource'
    ) {
        if (!class_exists($entityClass)) {
            throw new \InvalidArgumentException(sprintf('The class %s does not exist.', $entityClass));
        }

        $this->entityClass = $entityClass;
        $this->shortName = $shortName ?: substr($this->entityClass, strrpos($this->entityClass, '\\') + 1);
        $this->normalizationContext = $normalizationContext;
        $this->denormalizationContext = $denormalizationContext;
        $this->validationGroups = $validationGroups;
        $this->controllerName = $controllerName;

        $this->beautifiedName = Inflector::pluralize(Inflector::tableize($this->shortName));

        $this->collectionOperations = null === $collectionOperations ? self::$defaultCollectionOperations : $collectionOperations;
        foreach ($this->collectionOperations as $key => $operation) {
            $this->collectionOperations[$key] = $this->populateOperation($operation, true);
        }

        $this->itemOperations = null === $itemOperations ? self::$defaultItemOperations : $itemOperations;
        foreach ($this->itemOperations as $key => $operation) {
            $this->itemOperations[$key] = $this->populateOperation($operation, false);
        }

        foreach ($filters as $filters => $filter) {
            $this->filters[$filters] = array_merge(self::$defaultFilter, $filter);
        }

        $this->normalizationContext['resource'] = $this;
        $this->denormalizationContext['resource'] = $this;
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
     * Gets filters available for this resource.
     *
     * @return array
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * Gets the normalization context.
     *
     * @return array
     */
    public function getNormalizationContext()
    {
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
     * Gets the denormalization context.
     *
     * @return array
     */
    public function getDenormalizationContext()
    {
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
     * Gets validation groups to use.
     *
     * @return string[]|null
     */
    public function getValidationGroups()
    {
        return $this->validationGroups;
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
        foreach ($this->collectionOperations as &$collectionOperation) {
            $this->addRoute($collectionOperation, true);
        }

        foreach ($this->itemOperations as &$itemOperation) {
            $this->addRoute($itemOperation, false);
        }

        return $this->routeCollection;
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
            $operation['!route_name'] = sprintf('%s%s_%s', self::ROUTE_NAME_PREFIX, $this->beautifiedName, $action);
        }

        if (!isset($operation['!route_path'])) {
            $operation['!route_path'] = '/'.$this->beautifiedName.($isCollection ? '' : '/{id}');
        }

        return $operation;
    }

    /**
     * Adds a route to the collection.
     *
     * @param array   $operation
     * @param boolean $isCollection
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

            if (!$this->elementRoute && !$isCollection) {
                $this->elementRoute = $operation['!route_name'];
            }
        }
    }

    /**
     * Gets the route associated with the collection.
     *
     * @return string
     */
    public function getCollectionRoute()
    {
        if (!$this->collectionRoute) {
            // Can be optimized
            $this->getRouteCollection();
        }

        return $this->collectionRoute;
    }

    /**
     * Gets route associated with an element.
     *
     * @return string
     */
    public function getElementRoute()
    {
        if (!$this->elementRoute) {
            // Can be optimized
            $this->getRouteCollection();
        }

        return $this->elementRoute;
    }

    /**
     * Gets item operations.
     *
     * @return array
     */
    public function getItemOperations()
    {
        return $this->itemOperations;
    }

    /**
     * Get collection operations.
     *
     * @return array
     */
    public function getCollectionOperations()
    {
        return $this->collectionOperations;
    }

    /**
     * Gets the short name of the resource pluralized and camel cased.
     *
     * @return string
     */
    public function getBeautifiedName()
    {
        return $this->beautifiedName;
    }
}
