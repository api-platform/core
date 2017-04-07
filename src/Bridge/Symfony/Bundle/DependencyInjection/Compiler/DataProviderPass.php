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
 * Registers data providers.
 *
 * @internal
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class DataProviderPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $this->registerDataProviders($container, 'collection');
        $this->registerDataProviders($container, 'item');
    }

    /**
     * The priority sorting algorithm has been backported from Symfony 3.2.
     *
     * @see https://github.com/symfony/symfony/blob/master/src/Symfony/Component/DependencyInjection/Compiler/PriorityTaggedServiceTrait.php
     *
     * @param ContainerBuilder $container
     * @param string           $type
     */
    private function registerDataProviders(ContainerBuilder $container, string $type)
    {
        $services = $container->findTaggedServiceIds('api_platform.'.$type.'_data_provider');

        $queue = new \SplPriorityQueue();

        foreach ($services as $serviceId => $tags) {
            foreach ($tags as $attributes) {
                $priority = $attributes['priority'] ?? 0;
                $queue->insert(new Reference($serviceId), $priority);
            }
        }

        $container->getDefinition('api_platform.'.$type.'_data_provider')->addArgument(iterator_to_array($queue, false));
    }
}
