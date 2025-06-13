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

class ResourceMutatorPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('api_platform.metadata.mutator_collection.resource')) {
            return;
        }

        $definition = $container->getDefinition('api_platform.metadata.mutator_collection.resource');

        $mutators = $container->findTaggedServiceIds('api_platform.resource_mutator');

        foreach ($mutators as $id => $tags) {
            foreach ($tags as $tag) {
                $definition->addMethodCall('addMutator', [
                    $tag['resourceClass'],
                    new Reference($id),
                ]);
            }
        }
    }
}
