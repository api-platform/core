<?php

/*
 * This file is part of the DunglasJsonLdApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\JsonLdApiBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Finds and groups resources.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ResourcesCompilerPass implements CompilerPassInterface
{

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $resourcesDefinition = $container->getDefinition('dunglas_json_ld_api.resources');
        foreach ($container->findTaggedServiceIds('json-ld.resource') as $serviceId => $tags) {
            $resourcesDefinition->addMethodCall(
                'append',
                [new Reference($serviceId)]
            );

            $resourceDefinition = $container->getDefinition($serviceId);
            $resourceDefinition->addMethodCall('setServiceId', [$serviceId]);
        }
    }
}
