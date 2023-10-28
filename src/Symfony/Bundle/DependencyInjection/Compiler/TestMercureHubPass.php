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
use Symfony\Component\Mercure\Debug\TraceableHub;

/**
 * Decorate each Mercure Hub with TraceableHub for test purpose.
 * Prevents enabling debug mode on tests for Mercure assertions.
 */
final class TestMercureHubPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        // Only enable this if class exists and "framework.test" is enabled
        if (!class_exists(TraceableHub::class) || !$container->has('test.service_container')) {
            return;
        }

        foreach ($container->findTaggedServiceIds('mercure.hub', true) as $serviceId => $attributes) {
            $container->register("test.api_platform.mercure.hub.$serviceId", TraceableHub::class)
                ->setDecoratedService($serviceId)
                ->addArgument(new Reference("test.api_platform.mercure.hub.$serviceId.inner"))
                ->addArgument(new Reference('debug.stopwatch'));
        }
    }
}
