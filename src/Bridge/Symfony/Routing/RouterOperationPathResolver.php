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

use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\PathResolver\OperationPathResolverInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Resolves the operations path using a Symfony route.
 *
 * @author Guilhem N. <egetick@gmail.com>
 */
final class RouterOperationPathResolver implements OperationPathResolverInterface
{
    private $router;
    private $deferred;

    public function __construct(RouterInterface $router, OperationPathResolverInterface $deferred)
    {
        $this->router = $router;
        $this->deferred = $deferred;
    }

    /**
     * {@inheritdoc}
     *
     * @throws InvalidArgumentException
     */
    public function resolveOperationPath(string $resourceShortName, array $operation, bool $collection): string
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
}
