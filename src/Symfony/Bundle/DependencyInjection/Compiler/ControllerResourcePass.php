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

namespace ApiPlatform\Symfony\Bundle\DependencyInjection\Compiler;

use ApiPlatform\Exception\RuntimeException;
use ApiPlatform\Metadata\ApiResource;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Finds resources declared within controllers and builds a Controller <=> ApiResource link.
 * This is then transformed into metadata within the ControllerAttributeResourceMetadataCollectionFactory.
 *
 * @internal
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class ControllerResourcePass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException
     */
    public function process(ContainerBuilder $container): void
    {
        $attributes = [];
        $resourceClasses = [];

        foreach ($container->findTaggedServiceIds('controller.service_arguments') as $serviceId => $tags) {
            $definition = $container->getDefinition($serviceId);
            $reflection = new \ReflectionClass($controllerClass = $definition->getClass());

            foreach ($reflection->getMethods() as $method) {
                if (!$routeAttribute = $this->getRouteAttribute($method)) {
                    continue;
                }

                foreach ($method->getParameters() as $parameter) {
                    $type = $parameter->getType();

                    if (!$type instanceof \ReflectionNamedType) {
                        continue;
                    }

                    if (!$this->hasApiResourceAttribute($parameter)) {
                        continue;
                    }

                    $class = $type->getName();
                    $resourceClasses[] = $class;

                    if (!isset($attributes[$class])) {
                        $attributes[$class] = [];
                    }

                    $attributes[$class][] = [
                        'controller' => $controllerClass,
                        'method' => $method->getName(),
                        'parameter' => $parameter->getName(),
                        'route_name' => $routeAttribute->getName() ?? $this->getDefaultRouteName($reflection, $method),
                    ];
                }
            }
        }

        $definition = $container->getDefinition('api_platform.metadata.resource.name_collection_factory.array');
        $definition->setArgument(0, $resourceClasses);

        $definition = $container->getDefinition('api_platform.metadata.resource.metadata_collection_factory.array');
        $definition->setArgument(0, $attributes);
    }

    private function hasApiResourceAttribute(\ReflectionParameter $reflection): bool
    {
        if ($reflection->getAttributes(ApiResource::class, \ReflectionAttribute::IS_INSTANCEOF)) {
            return true;
        }

        return false;
    }

    /**
     * @param \ReflectionClass|\ReflectionMethod $reflection
     */
    private function getRouteAttribute(object $reflection): ?Route
    {
        $attributes = $reflection->getAttributes(Route::class, \ReflectionAttribute::IS_INSTANCEOF);

        if (!$attributes) {
            return null;
        }

        return $attributes[0]->newInstance();
    }

    /**
     * When need a link between the Route and the ApiResource, to do that we use the route name
     * that symfony generates. Obviously if a route name is used inside the attribute we use it instead.
     *
     * @see https://github.com/symfony/symfony/blob/cb39a7fbff574350e5cc4858133ebb87191b12f0/src/Symfony/Bundle/FrameworkBundle/Routing/AnnotatedRouteControllerLoader.php#L42
     */
    private function getDefaultRouteName(\ReflectionClass $class, \ReflectionMethod $method): string
    {
        $name = str_replace('\\', '_', $class->name).'_'.$method->name;
        $name = \function_exists('mb_strtolower') && preg_match('//u', $name) ? mb_strtolower($name, 'UTF-8') : strtolower($name);
        $name = preg_replace('/(bundle|controller)_/', '_', $name);

        if (str_ends_with($method->name, 'Action') || str_ends_with($method->name, '_action')) {
            $name = preg_replace('/action(_\d+)?$/', '\\1', $name);
        }

        return str_replace('__', '_', $name);
    }
}
