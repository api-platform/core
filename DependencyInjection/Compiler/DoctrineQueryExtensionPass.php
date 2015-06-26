<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Injects query extensions.
 *
 * @author Samuel ROZE <samuel.roze@gmail.com>
 */
class DoctrineQueryExtensionPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $sortedServices = [];
        foreach ($container->findTaggedServiceIds('api.doctrine.orm.query_extension') as $serviceId => $tags) {
            foreach ($tags as $tag) {
                $priority = isset($tag['priority']) ? $tag['priority'] : 0;
                $sortedServices[$priority][] = new Reference($serviceId);
            }
        }
        krsort($sortedServices);

        // Flatten the array
        $extensions = call_user_func_array('array_merge', $sortedServices);
        $dataProviderDefinition = $container->getDefinition('api.doctrine.orm.data_provider');
        foreach ($extensions as $extension) {
            $dataProviderDefinition->addMethodCall('addExtension', [$extension]);
        }
    }
}
