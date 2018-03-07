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
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\OutOfBoundsException;

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
    use PriorityTaggedServiceTrait;

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        // if doctrine not loaded
        if (!$container->hasDefinition('api_platform.doctrine.metadata_factory')) {
            return;
        }

        $collectionDataProviders = $this->findAndSortTaggedServices('api_platform.collection_data_provider', $container);
        $itemDataProviders = $this->findAndSortTaggedServices('api_platform.item_data_provider', $container);
        $subresourceDataProviders = $this->findAndSortTaggedServices('api_platform.subresource_data_provider', $container);

        $collectionExtensions = $this->findAndSortTaggedServices('api_platform.doctrine.orm.query_extension.collection', $container);
        $itemExtensions = $this->findAndSortTaggedServices('api_platform.doctrine.orm.query_extension.item', $container);

        foreach ($collectionDataProviders as $collectionDataProvider) {
            $definition = $container->getDefinition((string) $collectionDataProvider);
            try {
                $definition->replaceArgument(1, $collectionExtensions);
            } catch (OutOfBoundsException $exception) {
                $definition->addArgument($collectionExtensions);
            }
        }

        foreach ($itemDataProviders as $itemDataProvider) {
            $container->getDefinition((string) $itemDataProvider)->replaceArgument(3, $itemExtensions);
        }

        foreach ($subresourceDataProviders as $subresourceDataProvider) {
            $definition = $container->getDefinition((string) $subresourceDataProvider);
            try {
                $definition->replaceArgument(3, $collectionExtensions);
                $definition->replaceArgument(4, $itemExtensions);
            } catch (OutOfBoundsException $exception) {
                $definition->addArgument($collectionExtensions);
                $definition->addArgument($itemExtensions);
            }
        }
    }
}
