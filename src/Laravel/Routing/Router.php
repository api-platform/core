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

namespace ApiPlatform\Laravel\Routing;

use ApiPlatform\Metadata\UrlGeneratorInterface;
use Illuminate\Http\Request as LaravelRequest;
use Illuminate\Routing\Router as BaseRouter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

/**
 * Laravel router decorator.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class Router implements RouterInterface, UrlGeneratorInterface
{
    public const CONST_MAP = [
        UrlGeneratorInterface::ABS_URL => RouterInterface::ABSOLUTE_URL,
        UrlGeneratorInterface::ABS_PATH => RouterInterface::ABSOLUTE_PATH,
        UrlGeneratorInterface::REL_PATH => RouterInterface::RELATIVE_PATH,
        UrlGeneratorInterface::NET_PATH => RouterInterface::NETWORK_PATH,
    ];

    private RequestContext $context;

    public function __construct(private readonly BaseRouter $router, private readonly int $urlGenerationStrategy = UrlGeneratorInterface::ABS_PATH)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function setContext(RequestContext $context): void
    {
        $this->context = $context;
    }

    /**
     * {@inheritdoc}
     */
    public function getContext(): RequestContext
    {
        return $this->context;
    }

    /**
     * {@inheritdoc}
     */
    public function getRouteCollection(): RouteCollection
    {
        /** @var \Illuminate\Routing\RouteCollection $routes */
        $routes = $this->router->getRoutes();

        return $routes->toSymfonyRouteCollection();
    }

    /**
     * {@inheritdoc}
     *
     * @return array<string, mixed>|array{_api_resource_class?: class-string|string, _api_operation_name?: string, uri_variables?: array<string, mixed>}
     */
    public function match(string $pathInfo): array
    {
        $request = LaravelRequest::create($pathInfo, Request::METHOD_GET);
        $route = $this->router->getRoutes()->match($request);

        return $route->defaults + ['uri_variables' => array_diff_key($route->parameters, $route->defaults)];
    }

    /**
     * {@inheritdoc}
     *
     * @param array<string, string> $parameters
     */
    public function generate(string $name, array $parameters = [], ?int $referenceType = null): string
    {
        $routes = $this->getRouteCollection();
        $generator = new UrlGenerator($routes, $this->getContext());
        if (isset($parameters['_format']) && !str_starts_with($parameters['_format'], '.')) {
            $parameters['_format'] = '.'.$parameters['_format'];
        }

        return $generator->generate($name, $parameters, self::CONST_MAP[$referenceType ?? $this->urlGenerationStrategy]);
    }
}
