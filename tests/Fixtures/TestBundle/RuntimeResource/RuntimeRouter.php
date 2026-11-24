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

namespace ApiPlatform\Tests\Fixtures\TestBundle\RuntimeResource;

use ApiPlatform\Metadata\UrlGeneratorInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

final class RuntimeRouter implements RouterInterface, UrlGeneratorInterface
{
    public const COLLECTION_ROUTE = 'runtime_collection';
    public const ITEM_ROUTE = 'runtime_item';

    private const OPERATIONS = [
        self::COLLECTION_ROUTE => ['GET' => 'get_collection', 'HEAD' => 'get_collection', 'POST' => 'post'],
        self::ITEM_ROUTE => ['GET' => 'get', 'HEAD' => 'get', 'PUT' => 'put', 'PATCH' => 'patch', 'DELETE' => 'delete'],
    ];

    public function __construct(
        private readonly RouterInterface&UrlGeneratorInterface $router,
        private readonly RuntimeResources $resources,
    ) {
    }

    public static function operationName(string $name, string $suffix): string
    {
        return 'runtime_'.$name.'_'.$suffix;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if (!isset(self::OPERATIONS[$request->attributes->get('_route')])) {
            return;
        }

        $request->attributes->add($this->resolve($request->attributes->all(), $request->getMethod()) ?? throw new NotFoundHttpException(\sprintf('No route found for "%s %s".', $request->getMethod(), $request->getPathInfo())));
    }

    public function match(string $pathinfo): array
    {
        $parameters = $this->router->match($pathinfo);
        if (!isset(self::OPERATIONS[$parameters['_route'] ?? ''])) {
            return $parameters;
        }

        return ($this->resolve($parameters, 'GET') ?? throw new ResourceNotFoundException(\sprintf('No runtime resource matches "%s".', $pathinfo))) + $parameters;
    }

    public function generate(string $name, array $parameters = [], int $referenceType = self::ABSOLUTE_PATH): string
    {
        return $this->router->generate($name, $parameters, $referenceType);
    }

    public function setContext(RequestContext $context): void
    {
        $this->router->setContext($context);
    }

    public function getContext(): RequestContext
    {
        return $this->router->getContext();
    }

    public function getRouteCollection(): RouteCollection
    {
        return $this->router->getRouteCollection();
    }

    /**
     * @param array<string, mixed> $parameters
     *
     * @return array{_api_resource_class: string, _api_operation_name: string}|null
     */
    private function resolve(array $parameters, string $method): ?array
    {
        $suffix = self::OPERATIONS[$parameters['_route']][$method] ?? null;
        $name = $parameters['resource'] ?? null;
        if (null === $suffix || !\is_string($name) || null === $class = $this->resources->classOf($name)) {
            return null;
        }

        return ['_api_resource_class' => $class, '_api_operation_name' => self::operationName($name, $suffix)];
    }
}
