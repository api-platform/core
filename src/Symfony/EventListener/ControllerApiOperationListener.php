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

use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
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

        $routeName = $request->attributes->get('_route');
        foreach ($this->resourceMetadataCollectionFactory->create($resourceClass) as $resource) {
            foreach ($resource->getOperations() ?? [] as $operationName => $operation) {
                if ($operation->getRouteName() !== $routeName || $operation->getController() !== $controller) {
                    continue;
                }

                $request->attributes->set('_api_resource_class', $resourceClass);
                $request->attributes->set('_api_operation_name', $operationName);
                $request->attributes->set('_api_operation', $operation);
                $request->attributes->set(self::REQUEST_ATTRIBUTE, true);

                return;
            }
        }
    }
}
