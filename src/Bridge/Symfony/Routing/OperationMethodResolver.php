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

use ApiPlatform\Core\Api\OperationMethodResolverInterface;
use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use Symfony\Component\Routing\RouterInterface;

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
    public function getCollectionOperationMethod(string $resourceClass, string $operationName) : string
    {
        return $this->getOperationMethod($resourceClass, $operationName, true);
    }

    /**
     * {@inheritdoc}
     */
    public function getItemOperationMethod(string $resourceClass, string $operationName) : string
    {
        return $this->getOperationMethod($resourceClass, $operationName, false);
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
    private function getOperationMethod(string $resourceClass, string $operationName, bool $collection = true) : string
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

        if ($collection) {
            $routeName = $resourceMetadata->getCollectionOperationAttribute($operationName, 'route_name');
        } else {
            $routeName = $resourceMetadata->getItemOperationAttribute($operationName, 'route_name');
        }

        if (null === $routeName) {
            throw new RuntimeException(sprintf('Either a "route_name" or a "method" operation attribute must exist for the operation "%s" of the resource "%s".', $operationName, $resourceClass));
        }

        /*
         * @var Route
         */
        foreach ($this->router->getRouteCollection() as $name => $route) {
            if ($routeName === $name) {
                $methods = $route->getMethods();

                if (empty($methods)) {
                    return 'GET';
                }

                return $methods[0];
            }
        }

        throw new RuntimeException(sprintf('Route "%s" not found for the operation "%s" of the resource "%s".', $routeName, $operationName, $resourceClass));
    }
}
