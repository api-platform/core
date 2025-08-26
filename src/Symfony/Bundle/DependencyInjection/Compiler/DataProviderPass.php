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

use ApiPlatform\State\SerializerAwareProviderInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers data providers.
 *
 * @internal since 4.2
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 *
 * TODO: remove in 5.x
 */
final class DataProviderPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        $services = $container->findTaggedServiceIds('api_platform.state_provider', true);

        foreach ($services as $id => $tags) {
            $definition = $container->getDefinition((string) $id);
            if (is_a($definition->getClass(), SerializerAwareProviderInterface::class, true)) {
                $definition->addMethodCall('setSerializerLocator', [new Reference('api_platform.serializer_locator')]);
            }
        }
    }
}
