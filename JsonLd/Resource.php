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
    private $itemRouteName;
    /**
     * @var string|null
     */
    private $collectionRouteName;
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
        $this->dataProvider->initResource($this);
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityClass()
    {
        return $this->entityClass;
    }

    /**
     * {@inheritdoc}
     */
    public function getDataProvider()
    {
        return $this->dataProvider;
    }

    /**
     * Initializes the resource collection.
     *
     * @param ResourceCollectionInterface $resourceCollection
     */
    public function initResourceCollection(ResourceCollectionInterface $resourceCollection)
    {
        $this->resourceCollection = $resourceCollection;
    }

    /**
     * {@inheritdoc}
     */
    public function getResourceCollection()
    {
        return $this->resourceCollection;
    }

    /**
     * Initializes filters.
     *
     * @param array $filters
     */
    public function initFilters(array $filters)
    {
        $this->filters = $filters;
    }

    /**
     * {@inheritdoc}
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
     * Initializes normalization context.
     *
     * @param array $normalizationContext
     */
    public function initNormalizationContext(array $normalizationContext)
    {
        $this->normalizationContext = $normalizationContext;
    }

    /**
     * {@inheritdoc}
     */
    public function getNormalizationContext()
    {
        if (!isset($this->normalizationContext['resource'])) {
            $this->normalizationContext['resource'] = $this;
        }

        return $this->normalizationContext;
    }

    /**
     * {@inheritdoc}
     */
    public function getNormalizationGroups()
    {
        return isset($this->normalizationContext['groups']) ? $this->normalizationContext['groups'] : null;
    }

    /**
     * Initializes denormalization context.
     *
     * @param array $denormalizationContext
     */
    public function initDenormalizationContext(array $denormalizationContext)
    {
        $this->denormalizationContext = $denormalizationContext;
    }

    /**
     * {@inheritdoc}
     */
    public function getDenormalizationContext()
    {
        if (!isset($this->denormalizationContext['resource'])) {
            $this->denormalizationContext['resource'] = $this;
        }

        return $this->denormalizationContext;
    }

    /**
     * {@inheritdoc}
     */
    public function getDenormalizationGroups()
    {
        return isset($this->denormalizationContext['groups']) ? $this->denormalizationContext['groups'] : null;
    }

    /**
     * Initializes validation groups.
     *
     * @param array $validationGroups
     */
    public function initValidationGroups(array $validationGroups)
    {
        $this->validationGroups = $validationGroups;
    }

    /**
     * {@inheritdoc}
     */
    public function getValidationGroups()
    {
        return $this->validationGroups;
    }

    /**
     * Initializes short name.
     *
     * @param string $shortName
     */
    public function initShortName($shortName)
    {
        $this->shortName = $shortName;
    }

    /**
     * {@inheritdoc}
     */
    public function getShortName()
    {
        return $this->shortName;
    }

    /**
     * Initializes controller name.
     *
     * @param string $controllerName
     */
    public function initControllerName($controllerName)
    {
        $this->controllerName = $controllerName;
    }

    /**
     * {@inheritdoc}
     */
    public function getControllerName()
    {
        return $this->controllerName;
    }

    /**
     * {@inheritdoc}
     */
    public function getRouteCollection()
    {
        if ($this->routeCollection) {
            return $this->routeCollection;
        }

        $this->routeCollection = new RouteCollection();

        $this->addRoutes(true);
        $this->addRoutes(false);

        return $this->routeCollection;
    }

    /**
     * {@inheritdoc}
     */
    public function getCollectionRouteName()
    {
        if (!$this->collectionRouteName) {
            $this->getRouteCollection();
        }

        return $this->collectionRouteName;
    }

    /**
     * {@inheritdoc}
     */
    public function getItemRouteName()
    {
        if (!$this->itemRouteName) {
            $this->getRouteCollection();
        }

        return $this->itemRouteName;
    }

    /**
     * Initializes item operations.
     *
     * @param array $itemOperations
     */
    public function initItemOperations(array $itemOperations)
    {
        $this->itemOperations = $itemOperations;
    }

    /**
     * {@inheritdoc}
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
     * Initializes collection operations.
     *
     * @param array $collectionOperations
     */
    public function initCollectionOperations(array $collectionOperations)
    {
        $this->collectionOperations = $collectionOperations;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * Adds a routes to the collection.
     *
     * @param bool $collection
     */
    private function addRoutes($collection)
    {
        if ($collection) {
            $this->getCollectionOperations();

            $operations = &$this->collectionOperations;
            $routeName = &$this->collectionRouteName;
        } else {
            $this->getItemOperations();

            $operations = &$this->itemOperations;
            $routeName = &$this->itemRouteName;
        }

        foreach ($operations as $key => $operation) {
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
            $operations[$key]['!route'] = $route;

            // Set routes
            if ('GET' === $operation['hydra:method'] && !$routeName) {
                $routeName = $operation['!route_name'];
            }
        }
    }
}
