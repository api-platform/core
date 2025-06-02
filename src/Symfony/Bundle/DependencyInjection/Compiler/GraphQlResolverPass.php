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

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Injects GraphQL resolvers.
 *
 * @internal
 *
 * @author Lukas Lücke <lukas@luecke.me>
 */
final class GraphQlResolverPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        if (!$container->getParameter('api_platform.graphql.enabled')) {
            return;
        }

        $resolvers = [];
        foreach ($container->findTaggedServiceIds('api_platform.graphql.resolver', true) as $serviceId => $tags) {
            foreach ($tags as $tag) {
                $resolvers[$tag['id'] ?? $serviceId] = new Reference($serviceId);
            }
        }

        $container->getDefinition('api_platform.graphql.resolver_locator')->addArgument($resolvers);
    }
}
