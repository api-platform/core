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

use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\Symfony\Bundle\State\TraceableProcessor;
use ApiPlatform\Symfony\Bundle\State\TraceableProvider;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class ProfilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has('debug.stopwatch')) {
            return;
        }

        $this->decorateProviders($container);
        $this->decorateProcessors($container);
    }

    private function decorateProviders(ContainerBuilder $container): void
    {
        foreach ($this->findServiceIds($container, ProviderInterface::class, TraceableProvider::class) as $providerId) {
            $decoratorId = $providerId.'.traceable';
            $container->register($decoratorId, TraceableProvider::class)
                ->setDecoratedService($providerId, null, -\PHP_INT_MAX)
                ->setArguments([
                    new Reference($decoratorId.'.inner'),
                    new Reference('debug.stopwatch'),
                    $providerId,
                ]);
        }
    }

    private function decorateProcessors(ContainerBuilder $container): void
    {
        foreach ($this->findServiceIds($container, ProcessorInterface::class, TraceableProcessor::class) as $processorId) {
            $decoratorId = $processorId.'.traceable';
            $container->register($decoratorId, TraceableProcessor::class)
                ->setDecoratedService($processorId, null, -\PHP_INT_MAX)
                ->setArguments([
                    new Reference($decoratorId.'.inner'),
                    new Reference('debug.stopwatch'),
                    $processorId,
                ]);
        }
    }

    /**
     * @param class-string<object> $interface
     * @param class-string<object> $excludeClass
     *
     * @return string[]
     */
    private function findServiceIds(ContainerBuilder $container, string $interface, string $excludeClass): array
    {
        $serviceIds = [];
        foreach (array_keys($container->getDefinitions()) as $id) {
            if (!$container->hasDefinition($id)) {
                continue;
            }

            $definition = $container->getDefinition($id);
            if ($definition->isAbstract() || $definition->isSynthetic() || !$definition->getClass()) {
                continue;
            }

            if (is_a($definition->getClass(), $excludeClass, true)) {
                continue;
            }

            try {
                $class = $container->getParameterBag()->resolveValue($definition->getClass());
                if (!$class || (!class_exists($class) && !interface_exists($class))) {
                    continue;
                }
                $reflectionClass = new \ReflectionClass($class);
                if ($reflectionClass->implementsInterface($interface)) {
                    $serviceIds[] = $id;
                }
            } catch (\ReflectionException) {
                // ignore
            }
        }

        return $serviceIds;
    }
}
