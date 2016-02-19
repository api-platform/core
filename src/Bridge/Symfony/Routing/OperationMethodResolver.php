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

use ApiPlatform\Builder\Api\OperationMethodResolverInterface;
use ApiPlatform\Builder\Exception\RuntimeException;
use ApiPlatform\Builder\Metadata\Resource\Factory\ItemMetadataFactoryInterface;
use Symfony\Component\Routing\RouterInterface;

final class OperationMethodResolver implements OperationMethodResolverInterface
{
    private $router;
    private $itemMetadataFactory;

    public function __construct(RouterInterface $router, ItemMetadataFactoryInterface $itemMetadataFactory)
    {
        $this->router = $router;
        $this->itemMetadataFactory = $itemMetadataFactory;
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
     * @return string
     *
     * @throws RuntimeException
     */
    private function getOperationMethod(string $resourceClass, string $operationName, bool $collection = true) : string
    {
        $itemMetadata = $this->itemMetadataFactory->create($resourceClass);

        if ($collection) {
            $method = $itemMetadata->getCollectionOperationAttribute($operationName, 'method');
        } else {
            $method = $itemMetadata->getItemOperationAttribute($operationName, 'method');
        }

        if (null !== $method) {
            return $method;
        }

        if ($collection) {
            $routeName = $itemMetadata->getCollectionOperationAttribute($operationName, 'route_name');
        } else {
            $routeName = $itemMetadata->getItemOperationAttribute($operationName, 'route_name');
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
