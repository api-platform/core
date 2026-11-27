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

namespace ApiPlatform\Symfony\Metadata\Resource\Factory;

use ApiPlatform\Metadata\ApiOperation;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Resource\Factory\OperationDefaultsTrait;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\Util\CamelCaseToSnakeCaseNameConverter;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Adds the operations declared with {@see ApiOperation} on controller methods to their resource.
 *
 * @internal
 */
final class ControllerApiOperationResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    use OperationDefaultsTrait;

    /**
     * @param array<class-string, list<string>> $controllerOperations resource class to "Class::method" controllers
     */
    public function __construct(
        private readonly array $controllerOperations,
        private readonly ?ResourceMetadataCollectionFactoryInterface $decorated = null,
        ?LoggerInterface $logger = null,
        array $defaults = [],
    ) {
        $this->logger = $logger ?? new NullLogger();
        $this->defaults = $defaults;
        $this->camelCaseToSnakeCaseNameConverter = new CamelCaseToSnakeCaseNameConverter();
    }

    public function create(string $resourceClass): ResourceMetadataCollection
    {
        $resourceMetadataCollection = $this->decorated?->create($resourceClass) ?? new ResourceMetadataCollection($resourceClass);

        foreach ($this->controllerOperations[$resourceClass] ?? [] as $controller) {
            [$controllerClass, $methodName] = explode('::', $controller, 2);
            $method = new \ReflectionMethod($controllerClass, $methodName);

            foreach ($method->getAttributes(ApiOperation::class) as $attribute) {
                $operation = $attribute->newInstance()->operation;

                if (null !== $operation->getUriTemplate()) {
                    throw new RuntimeException(\sprintf('The "#[%s]" on "%s" must not define a "uriTemplate", the path is owned by the route.', ApiOperation::class, $controller));
                }

                $operation = $operation
                    ->withRouteName($this->getRouteName($controllerClass, $method, $operation, $controller))
                    ->withController($controller);

                if (($operation instanceof Get || $operation instanceof GetCollection) && null === $operation->canRead()) {
                    $operation = $operation->withRead(false);
                }

                if (isset($resourceMetadataCollection[0])) {
                    $resource = $resourceMetadataCollection[0];
                    [$key, $operation] = $this->getOperationWithDefaults($resource, $operation);
                    $resourceMetadataCollection[0] = $resource->withOperations(new Operations([...iterator_to_array($resource->getOperations() ?? []), $key => $operation]));

                    continue;
                }

                $resource = $this->getResourceWithDefaults($resourceClass, $this->getDefaultShortname($resourceClass), new ApiResource());
                [$key, $operation] = $this->getOperationWithDefaults($resource, $operation);
                $resourceMetadataCollection[] = $resource->withOperations(new Operations([$key => $operation]));
            }
        }

        return $resourceMetadataCollection;
    }

    private function getRouteName(string $controllerClass, \ReflectionMethod $method, HttpOperation $operation, string $controller): string
    {
        $routeAttributes = array_map(static fn (\ReflectionAttribute $attribute): Route => $attribute->newInstance(), $method->getAttributes(Route::class));

        foreach ($routeAttributes as $route) {
            if ($route->methods && !\in_array(strtoupper($operation->getMethod()), array_map('strtoupper', $route->methods), true)) {
                throw new RuntimeException(\sprintf('The "#[%s]" on "%s" uses the "%s" method but its "#[%s]" only allows "%s".', ApiOperation::class, $controller, $operation->getMethod(), Route::class, implode('", "', $route->methods)));
            }
        }

        if (null !== ($name = $operation->getRouteName())) {
            return $name;
        }

        foreach ($routeAttributes as $route) {
            if (null === $route->name) {
                continue;
            }

            $prefix = '';
            foreach ((new \ReflectionClass($controllerClass))->getAttributes(Route::class) as $classRouteAttribute) {
                $prefix = $classRouteAttribute->newInstance()->name ?? '';
            }

            return $prefix.$route->name;
        }

        throw new RuntimeException(\sprintf('The "#[%s]" on "%s" requires a named "#[%s]" or a "routeName" on its operation.', ApiOperation::class, $controller, Route::class));
    }
}
