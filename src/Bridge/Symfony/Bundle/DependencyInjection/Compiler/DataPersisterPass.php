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
 * Registers data persisters.
 *
 * @internal
 *
 * @author Baptiste Meyer <baptiste@les-tilleuls.coop>
 */
final class DataPersisterPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $persisters = [];
        $services = $container->findTaggedServiceIds('api_platform.data_persister', true);

        foreach ($services as $serviceId => $tags) {
            $persisters[] = new Reference($serviceId);
        }

        $container->getDefinition('api_platform.data_persister')->addArgument($persisters);
    }
}
