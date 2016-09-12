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

use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * {@inheritdoc}
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class RouteNameResolver implements RouteNameResolverInterface
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
    public function getRouteName(string $resourceClass, bool $collection) : string
    {
        $operationType = $collection ? 'collection' : 'item';
        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
        //@TODO prefix, inflector etc.
        $expectedOperation = sprintf('api_%s_get_item', $resourceMetadata->getShortName());

        foreach ($this->router->getRouteCollection()->all() as $routeName => $route) {
            $currentResourceClass = $route->getDefault('_api_resource_class');
            $operation = $route->getDefault(sprintf('_api_%s_operation_name', $operationType));
            $methods = $route->getMethods();

            if ($resourceClass === $currentResourceClass && $expectedOperation !== $operation && (empty($methods) || in_array('GET', $methods))) {
                return $routeName;
            }
        }

        throw new InvalidArgumentException(sprintf('No %s route associated with the type "%s".', $operationType, $resourceClass));
    }
}
