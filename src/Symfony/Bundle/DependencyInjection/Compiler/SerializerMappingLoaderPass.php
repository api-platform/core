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

final class SerializerMappingLoaderPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $chainLoader = $container->getDefinition('serializer.mapping.chain_loader');
        $loaders = $chainLoader->getArgument(0);
        $loaders[] = $container->getDefinition('api_platform.serializer.property_metadata_loader');
        $container->getDefinition('serializer.mapping.cache_warmer')->replaceArgument(0, $loaders);
    }
}
