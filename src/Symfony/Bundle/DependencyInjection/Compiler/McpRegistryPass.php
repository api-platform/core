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

use ApiPlatform\Mcp\Capability\Registry\SecureRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * symfony/mcp-bundle 0.13 registers one registry per configured server under a dynamic id
 * (mcp.server.<name>.registry), so decorating it can no longer be done with a static
 * decorate() call in configuration: this pass discovers every server via its builder tag
 * and decorates its registry individually.
 *
 * Decoration keeps the SDK's own list handlers in charge (they receive the configured
 * mcp.pagination_limit, which a custom handler would silently override), while loading
 * API Platform elements on first read heals a persistent runtime (e.g. FrankenPHP worker
 * mode) where the SDK builds the registry once and may capture an empty state.
 */
final class McpRegistryPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('api_platform.mcp.loader') || !$container->hasDefinition('api_platform.mcp.security.expression_access_checker')) {
            return;
        }

        foreach ($container->findTaggedServiceIds('mcp.server.builder') as $tags) {
            foreach ($tags as $tag) {
                $server = $tag['server'] ?? null;

                if (null === $server) {
                    continue;
                }

                $registryId = \sprintf('mcp.server.%s.registry', $server);

                if (!$container->hasDefinition($registryId)) {
                    continue;
                }

                $decoratorId = \sprintf('api_platform.mcp.secure_registry.%s', $server);

                $definition = new Definition(SecureRegistry::class);
                $definition->setDecoratedService($registryId);
                $definition->setArguments([
                    new Reference($decoratorId.'.inner'),
                    new Reference('api_platform.mcp.loader'),
                    new Reference('api_platform.mcp.security.expression_access_checker'),
                ]);

                $container->setDefinition($decoratorId, $definition);
            }
        }
    }
}
