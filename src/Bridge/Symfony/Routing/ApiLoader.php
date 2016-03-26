<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Bridge\Symfony\Routing;

use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use Doctrine\Common\Inflector\Inflector;
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
    const DEFAULT_ACTION_PATTERN = 'api_platform.action.';

    private $fileLoader;
    private $resourceNameCollectionFactory;
    private $resourceMetadataFactory;

    public function __construct(KernelInterface $kernel, ResourceNameCollectionFactoryInterface $resourceNameCollectionFactory, ResourceMetadataFactoryInterface $resourceMetadataFactory)
    {
        $this->fileLoader = new XmlFileLoader(new FileLocator($kernel->locateResource('@ApiPlatformBundle/Resources/config/routing')));
        $this->resourceNameCollectionFactory = $resourceNameCollectionFactory;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function load($data, $type = null)
    {
        $routeCollection = new RouteCollection();

        $routeCollection->addCollection($this->fileLoader->load('jsonld.xml'));
        $routeCollection->addCollection($this->fileLoader->load('hydra.xml'));

        foreach ($this->resourceNameCollectionFactory->create() as $resourceClass) {
            $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
            $normalizedShortName = Inflector::pluralize(Inflector::tableize($resourceMetadata->getShortName()));

            foreach ($resourceMetadata->getCollectionOperations() as $operationName => $operation) {
                $this->addRoute($routeCollection, $resourceClass, $operationName, $operation, $normalizedShortName, true);
            }

            foreach ($resourceMetadata->getItemOperations() as $operationName => $operation) {
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
        return 'api_platform' === $type;
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
