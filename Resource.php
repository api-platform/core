<?php

/*
 * This file is part of the DunglasJsonLdApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\JsonLdApiBundle;

use Doctrine\Common\Inflector\Inflector;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class representing a JSON-LD / Hydra resource.
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
     * @var string
     */
    const ROUTE_PATH_PREFIX = '/';

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
    protected $filters;
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
    protected $routeCollection = null;
    /**
     * @var string|null
     */
    protected $elementRoute = null;
    /**
     * @var string|null
     */
    protected $collectionRoute = null;

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
        $this->collectionOperations = null === $collectionOperations ? self::$defaultCollectionOperations : $collectionOperations;
        $this->itemOperations = null === $itemOperations ? self::$defaultItemOperations : $itemOperations;
        $this->controllerName = $controllerName;

        foreach ($filters as &$filter) {
            $filter = array_merge(self::$defaultFilter, $filter);
        }
        $this->filters = $filters;

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
        $beautified = $this->getBeautifiedName();

        foreach ($this->collectionOperations as $collectionOperation) {
            $this->addRoute($beautified, $this->routeCollection, $collectionOperation, true);
        }

        foreach ($this->itemOperations as $itemOperation) {
            $this->addRoute($beautified, $this->routeCollection, $itemOperation, false);
        }

        return $this->routeCollection;
    }

    /**
     * Adds a route to the collection.
     *
     * @param string          $beautified
     * @param RouteCollection $routeCollection
     * @param array           $operation
     * @param boolean         $isCollection
     */
    private function addRoute($beautified, RouteCollection $routeCollection, array $operation, $isCollection)
    {
        $method = isset($operation['hydra:method']) ? $operation['hydra:method'] : $operation['hydra:method'] = 'GET';
        $action = $method === 'GET' && $isCollection ? 'cget' : strtolower($method);

        // Use ! as ignore character because @ and are _ reserver JSON-LD characters
        if (isset($operation['!controller'])) {
            $controller = $operation['!controller'];
        } else {
            $controller = sprintf('%s:%s', $this->controllerName, $action);
        }

        if (isset($operation['!route_name'])) {
            $routeName = $operation['!route_name'];
        } else {
            $routeName = sprintf('%s%s_%s', self::ROUTE_NAME_PREFIX, $beautified, $action);
        }

        if (isset($operation['!route_path'])) {
            $routePath = $operation['!route_path'];
        } else {
            $routePath = self::ROUTE_PATH_PREFIX.$beautified;

            if (!$isCollection) {
                $routePath .= '/{id}';
            }
        }

        $methods = 'GET' === $method ? ['GET', 'HEAD'] : [$method];

        $routeCollection->add($routeName, new Route(
            $routePath,
            [
                '_controller' => $controller,
                '_json_ld_resource' => $this->shortName,
            ],
            [],
            [],
            '',
            [],
            $methods
        ));

        // Set routes
        if ('GET' === $method) {
            if (!$this->collectionRoute && $isCollection) {
                $this->collectionRoute = $routeName;
            }

            if (!$this->elementRoute && !$isCollection) {
                $this->elementRoute = $routeName;
            }
        }
    }

    /**
     * Gets the route associated with the collection.
     *
     * @return null|string
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
        return Inflector::pluralize(Inflector::tableize($this->shortName));
    }
}
