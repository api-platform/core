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

namespace ApiPlatform\Core\Bridge\Symfony\Bundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Injects GraphQL Exception formatters.
 *
 * @internal
 *
 * @author Mahmood Bazdar <mahmood@bazdar.me>
 */
class GraphQlExceptionFormatterPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->getParameter('api_platform.graphql.enabled')) {
            return;
        }

        $formatters = [];
        foreach ($container->findTaggedServiceIds('api_platform.graphql.exception_formatter', true) as $serviceId => $tags) {
            foreach ($tags as $tag) {
                $formatters[$tag['id'] ?? $serviceId] = new Reference($serviceId);
            }
        }
        $container->getDefinition('api_platform.graphql.exception_formatter_locator')->addArgument($formatters);
        $container->getDefinition('api_platform.graphql.exception_formatter_factory')->addArgument(array_keys($formatters));
    }
}
