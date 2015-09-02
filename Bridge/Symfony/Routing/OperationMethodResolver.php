<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Bridge\Symfony\Routing;

use Dunglas\ApiBundle\Api\OperationMethodResolverInterface;
use Dunglas\ApiBundle\Exception\RuntimeException;
use Dunglas\ApiBundle\Metadata\Resource\Factory\ItemMetadataFactoryInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;

class OperationMethodResolver implements OperationMethodResolverInterface
{
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var ItemMetadataFactoryInterface
     */
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
     * @param array $operation
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
