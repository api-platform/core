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

use ApiPlatform\Core\Serializer\Mapping\Loader\ResourceMetadataLoader;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Add resource metadata loader to Serializer chain loader.
 *
 * @internal
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class ResourceMetadataLoaderPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $chainLoader = $container->getDefinition('serializer.mapping.chain_loader');

        $serializerLoaders = $chainLoader->getArgument(0);

        $definition = new Definition(ResourceMetadataLoader::class);
        $definition->setPublic(false);
        $serializerLoaders[] = $definition;

        $chainLoader->replaceArgument(0, $serializerLoaders);
    }
}
