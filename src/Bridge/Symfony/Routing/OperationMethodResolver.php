<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ApiPlatform\Core\Bridge\Symfony\Routing;

use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;

/**
 * Resolves the HTTP method associated with an operation, extended for Symfony routing.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Teoh Han Hui <teohhanhui@gmail.com>
 */
final class OperationMethodResolver implements OperationMethodResolverInterface
{
    private $router;
    private $resourceMetadataFactory;

    public function __construct(RouterInterface $router, ResourceMetadataFactoryInterface $resourceMetadataFactory)
    {
        $this->router = $router;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getCollectionOperationMethod(string $resourceClass, string $operationName): string
    {
        return $this->getOperationMethod($resourceClass, $operationName, true);
    }

    /**
     * {@inheritdoc}
     */
    public function getItemOperationMethod(string $resourceClass, string $operationName): string
    {
        return $this->getOperationMethod($resourceClass, $operationName, false);
    }

    /**
     * {@inheritdoc}
     */
    public function getCollectionOperationRoute(string $resourceClass, string $operationName): Route
    {
        return $this->getOperationRoute($resourceClass, $operationName, true);
    }

    /**
     * {@inheritdoc}
     */
    public function getItemOperationRoute(string $resourceClass, string $operationName): Route
    {
        return $this->getOperationRoute($resourceClass, $operationName, false);
    }

    /**
     * @param string $resourceClass
     * @param string $operationName
     * @param bool   $collection
     *
     * @throws RuntimeException
     *
     * @return string
     */
    private function getOperationMethod(string $resourceClass, string $operationName, bool $collection): string
    {
        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);

        if ($collection) {
            $method = $resourceMetadata->getCollectionOperationAttribute($operationName, 'method');
        } else {
            $method = $resourceMetadata->getItemOperationAttribute($operationName, 'method');
        }

        if (null !== $method) {
            return $method;
        }

        if (null === $routeName = $this->getRouteName($resourceMetadata, $operationName, $collection)) {
            throw new RuntimeException(sprintf('Either a "route_name" or a "method" operation attribute must exist for the operation "%s" of the resource "%s".', $operationName, $resourceClass));
        }

        $route = $this->getRoute($routeName);
        $methods = $route->getMethods();

        if (empty($methods)) {
            return 'GET';
        }

        return $methods[0];
    }

    /**
     * Gets the route related to the given operation.
     *
     * @param string $resourceClass
     * @param string $operationName
     * @param bool   $collection
     *
     * @throws RuntimeException
     *
     * @return Route
     */
    private function getOperationRoute(string $resourceClass, string $operationName, bool $collection): Route
    {
        $routeName = $this->getRouteName($this->resourceMetadataFactory->create($resourceClass), $operationName, $collection);
        if (null !== $routeName) {
            return $this->getRoute($routeName);
        }

        $operationNameKey = sprintf('_api_%s_operation_name', $collection ? 'collection' : 'item');

        foreach ($this->router->getRouteCollection()->all() as $routeName => $route) {
            $currentResourceClass = $route->getDefault('_api_resource_class');
            $currentOperationName = $route->getDefault($operationNameKey);

            if ($resourceClass === $currentResourceClass && $operationName === $currentOperationName) {
                return $route;
            }
        }

        throw new RuntimeException(sprintf('No route found for operation "%s" for type "%s".', $operationName, $resourceClass));
    }

    /**
     * Gets the route name or null if not defined.
     *
     * @param ResourceMetadata $resourceMetadata
     * @param string           $operationName
     * @param bool             $collection
     *
     * @return string|null
     */
    private function getRouteName(ResourceMetadata $resourceMetadata, string $operationName, bool $collection)
    {
        if ($collection) {
            return $resourceMetadata->getCollectionOperationAttribute($operationName, 'route_name');
        }

        return $resourceMetadata->getItemOperationAttribute($operationName, 'route_name');
    }

    /**
     * Gets the route with the given name.
     *
     * @param string $routeName
     *
     * @throws RuntimeException
     *
     * @return Route
     */
    private function getRoute(string $routeName): Route
    {
        foreach ($this->router->getRouteCollection() as $name => $route) {
            if ($routeName === $name) {
                return $route;
            }
        }

        throw new RuntimeException(sprintf('The route "%s" does not exist.', $routeName));
    }
}
