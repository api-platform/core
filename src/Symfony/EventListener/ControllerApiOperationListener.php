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

namespace ApiPlatform\Symfony\EventListener;

use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Binds a request matching a controller carrying an API operation to that operation.
 *
 * @internal
 */
final class ControllerApiOperationListener
{
    public const REQUEST_ATTRIBUTE = '_api_controller_operation';

    /** @var array<string, class-string> */
    private readonly array $resourceClasses;

    /**
     * @param array<class-string, list<string>> $controllerOperations
     */
    public function __construct(
        private readonly ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory,
        array $controllerOperations,
    ) {
        $resourceClasses = [];
        foreach ($controllerOperations as $resourceClass => $controllers) {
            foreach ($controllers as $controller) {
                $resourceClasses[$controller] = $resourceClass;
            }
        }

        $this->resourceClasses = $resourceClasses;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $controller = $request->attributes->get('_controller');

        if (!$this->resourceClasses || !\is_string($controller) || $request->attributes->has('exception')) {
            return;
        }

        if (!str_contains($controller, '::')) {
            $controller .= '::__invoke';
        }

        if (null === ($resourceClass = $this->resourceClasses[$controller] ?? null)) {
            return;
        }

        $route = $request->attributes->get('_route');
        $canonicalRoute = $request->attributes->get('_canonical_route');
        $mismatch = null;

        foreach ($this->resourceMetadataCollectionFactory->create($resourceClass) as $resource) {
            foreach ($resource->getOperations() ?? [] as $operationName => $operation) {
                if ($operation->getController() !== $controller) {
                    continue;
                }

                if ($operation->getRouteName() !== $route && $operation->getRouteName() !== $canonicalRoute) {
                    $mismatch ??= $operation;

                    continue;
                }

                $request->attributes->set('_api_resource_class', $resourceClass);
                $request->attributes->set('_api_operation_name', $operationName);
                $request->attributes->set('_api_operation', $operation);
                $request->attributes->set(self::REQUEST_ATTRIBUTE, true);

                return;
            }
        }

        if (null !== $mismatch) {
            throw new RuntimeException(\sprintf('Route "%1$s" points to "%2$s", whose "#[ApiOperation]" is bound to route "%3$s". Set "routeName: \'%1$s\'" on the operation.', $route, $controller, $mismatch->getRouteName()));
        }
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $controller = $event->getController();
        if (!$this->resourceClasses || !\is_array($controller) && !\is_object($controller) || $event->getRequest()->attributes->get(self::REQUEST_ATTRIBUTE)) {
            return;
        }

        $name = \is_array($controller) ? (\is_object($controller[0]) ? $controller[0]::class : $controller[0]).'::'.$controller[1] : $controller::class.'::__invoke';
        if (!isset($this->resourceClasses[$name])) {
            return;
        }

        throw new RuntimeException(\sprintf('The controller "%s" carries "#[ApiOperation]" but could not be bound to its operation.', $name));
    }
}
