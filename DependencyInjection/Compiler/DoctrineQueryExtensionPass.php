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
        $dataProviderDefinition = $container->getDefinition('api.doctrine.orm.data_provider');
        foreach ($this->findSortedExtensions($container, 'api.doctrine.orm.query_extension.item') as $extension) {
            $dataProviderDefinition->addMethodCall('addItemExtension', [$extension]);
        }
        foreach ($this->findSortedExtensions($container, 'api.doctrine.orm.query_extension.collection') as $extension) {
            $dataProviderDefinition->addMethodCall('addCollectionExtension', [$extension]);
        }
    }

    private function findSortedExtensions(ContainerBuilder $container, $tag)
    {
        $extensions = [];
        foreach ($container->findTaggedServiceIds($tag) as $serviceId => $tags) {
            foreach ($tags as $tag) {
                $priority = isset($tag['priority']) ? $tag['priority'] : 0;
                $extensions[$priority][] = new Reference($serviceId);
            }
        }
        krsort($extensions);

        // Flatten the array
        return empty($extensions) ? [] : call_user_func_array('array_merge', $extensions);
    }
}
