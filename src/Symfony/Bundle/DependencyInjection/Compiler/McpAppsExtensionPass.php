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

use Mcp\Schema\Extension\Apps\McpApps;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @internal
 */
final class McpAppsExtensionPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('api_platform.mcp.apps_configurator')) {
            return;
        }

        foreach (array_keys($container->findTaggedServiceIds('mcp.server.builder')) as $serviceId) {
            $builder = $container->getDefinition($serviceId);

            if (null !== $builder->getConfigurator() || $this->extensionAlreadyEnabled($builder)) {
                continue;
            }

            $builder->setConfigurator([new Reference('api_platform.mcp.apps_configurator'), '__invoke']);
        }
    }

    private function extensionAlreadyEnabled(Definition $builder): bool
    {
        foreach ($builder->getMethodCalls() as [$method, $arguments]) {
            if ('enableExtension' !== $method) {
                continue;
            }

            foreach ($arguments as $argument) {
                if ($argument instanceof Definition && McpApps::class === $argument->getClass()) {
                    return true;
                }
            }
        }

        return false;
    }
}
