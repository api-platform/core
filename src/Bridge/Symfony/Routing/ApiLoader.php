<?php

/*
 * This file is part of the API Platform Builder package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Builder\Bridge\Symfony\Routing;

use Doctrine\Common\Inflector\Inflector;
use ApiPlatform\Builder\Exception\RuntimeException;
use ApiPlatform\Builder\Metadata\Resource\Factory\CollectionMetadataFactoryInterface as ResourceCollectionMetadataFactoryInterface;
use ApiPlatform\Builder\Metadata\Resource\Factory\ItemMetadataFactoryInterface as ResourceItemMetadataFactoryInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Loader\XmlFileLoader;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Loads Resources.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ApiLoader extends Loader
{
    const ROUTE_NAME_PREFIX = 'api_';
    const DEFAULT_ACTION_PATTERN = 'api.action.';

    private $fileLoader;
    private $resourceCollectionMetadataFactory;
    private $resourceItemMetadataFactory;

    public function __construct(KernelInterface $kernel, ResourceCollectionMetadataFactoryInterface $resourceCollectionMetadataFactory, ResourceItemMetadataFactoryInterface $resourceItemMetadataFactory)
    {
        $this->fileLoader = new XmlFileLoader(new FileLocator($kernel->locateResource('@ApiPlatformBuilderBundle/Resources/config/routing')));
        $this->resourceCollectionMetadataFactory = $resourceCollectionMetadataFactory;
        $this->resourceItemMetadataFactory = $resourceItemMetadataFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function load($data, $type = null)
    {
        $routeCollection = new RouteCollection();

        $routeCollection->addCollection($this->fileLoader->load('jsonld.xml'));
        $routeCollection->addCollection($this->fileLoader->load('hydra.xml'));

        foreach ($this->resourceCollectionMetadataFactory->create() as $resourceClass) {
            $itemMetadata = $this->resourceItemMetadataFactory->create($resourceClass);
            $normalizedShortName = Inflector::pluralize(Inflector::tableize($itemMetadata->getShortName()));

            foreach ($itemMetadata->getCollectionOperations() as $operationName => $operation) {
                $this->addRoute($routeCollection, $resourceClass, $operationName, $operation, $normalizedShortName, true);
            }

            foreach ($itemMetadata->getItemOperations() as $operationName => $operation) {
                $this->addRoute($routeCollection, $resourceClass, $operationName, $operation, $normalizedShortName, false);
            }
        }

        return $routeCollection;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource, $type = null)
    {
        return 'api' === $type;
    }

    /**
     * Creates and adds a route for the given operation to the route collection.
     *
     * @param RouteCollection $routeCollection
     * @param string          $resourceClass
     * @param string          $operationName
     * @param array           $operation
     * @param string          $normalizedShortName
     * @param bool            $collection
     *
     * @throws RuntimeException
     */
    private function addRoute(RouteCollection $routeCollection, string $resourceClass, string $operationName, array $operation, string $normalizedShortName, bool $collection)
    {
        if (isset($operation['route_name'])) {
            return;
        }

        if (!isset($operation['method'])) {
            throw new RuntimeException('Either a "route_name" or a "method" operation attribute must exist.');
        }

        if (isset($operation['controller'])) {
            $controller = $operation['controller'];
        } else {
            $actionName = sprintf('%s_%s', strtolower($operation['method']), $collection ? 'collection' : 'item');
            $controller = self::DEFAULT_ACTION_PATTERN.$actionName;
        }

        $path = '/'.$normalizedShortName;
        if (!$collection) {
            $path .= '/{id}';
        }

        $routeName = sprintf('%s%s_%s', self::ROUTE_NAME_PREFIX, $normalizedShortName, $actionName);

        $route = new Route(
            $path,
            [
                '_controller' => $controller,
                '_resource_class' => $resourceClass,
                sprintf('_%s_operation_name', $collection ? 'collection' : 'item') => $operationName,
            ],
            [],
            [],
            '',
            [],
            [$operation['method']]
        );

        $routeCollection->add($routeName, $route);
    }
}
