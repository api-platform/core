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
use ApiPlatform\Core\PathResolver\OperationPathResolverInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouterInterface;

/**
 * Resolves the operations path using a symfony route.
 *
 * @author Guilhem N. <egetick@gmail.com>
 */
final class RouterOperationPathResolver implements OperationPathResolverInterface
{
    private $router;
    private $requestContext;
    private $deferred;

    public function __construct(RouterInterface $router, RequestContext $requestContext, OperationPathResolverInterface $deferred)
    {
        $this->router = $router;
        $this->requestContext = $requestContext;
        $this->deferred = $deferred;
    }

    /**
     * {@inheritdoc}
     */
    public function resolveOperationPath(string $resourceShortName, array $operation, bool $collection) : string
    {
        if (!isset($operation['route_name'])) {
            return $this->deferred->resolveOperationPath($resourceShortName, $operation, $collection);
        }

        $route = $this->router->getRouteCollection()->get($operation['route_name']);
        if (null === $route) {
            throw new InvalidArgumentException(sprintf('The route "%s" of the resource "%s" was not found.', $operation['route_name'], $resourceShortName));
        }

        return $route->getPath();
    }

    public function getContext(): RequestContext
    {
        return $this->requestContext;
    }
}
