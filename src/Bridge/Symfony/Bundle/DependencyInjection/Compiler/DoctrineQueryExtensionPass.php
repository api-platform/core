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
 * Injects query extensions.
 *
 * @internal
 *
 * @author Samuel ROZE <samuel.roze@gmail.com>
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class DoctrineQueryExtensionPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        // if doctrine not loaded
        if (!$container->hasDefinition('api_platform.doctrine.metadata_factory')) {
            return;
        }

        $collectionDataProviderDefinition = $container->getDefinition('api_platform.doctrine.orm.collection_data_provider');
        $itemDataProviderDefinition = $container->getDefinition('api_platform.doctrine.orm.item_data_provider');

        $collectionDataProviderDefinition->replaceArgument(1, $this->findSortedServices($container, 'api_platform.doctrine.orm.query_extension.collection'));
        $itemDataProviderDefinition->replaceArgument(3, $this->findSortedServices($container, 'api_platform.doctrine.orm.query_extension.item'));
    }

    /**
     * Finds services having the given tag and sorts them by their priority attribute.
     *
     * @param ContainerBuilder $container
     * @param string           $tag
     *
     * @return Reference[]
     */
    private function findSortedServices(ContainerBuilder $container, $tag)
    {
        $extensions = [];
        foreach ($container->findTaggedServiceIds($tag) as $serviceId => $tags) {
            foreach ($tags as $tag) {
                $priority = $tag['priority'] ?? 0;
                $extensions[$priority][] = new Reference($serviceId);
            }
        }
        krsort($extensions);

        // Flatten the array
        return empty($extensions) ? [] : call_user_func_array('array_merge', $extensions);
    }
}
