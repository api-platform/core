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
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Finds and groups resources. Inject the default manager if necessary.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ResourceCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $resourceCollectionDefinition = $container->getDefinition('dunglas_json_ld_api.resource_collection');

        foreach ($container->findTaggedServiceIds('json-ld.resource') as $serviceId => $tags) {
            $resourceDefinition = $container->getDefinition($serviceId);

            if (!isset($resourceDefinition->getArguments()[1])) {
                $managerServiceId = sprintf('%s.manager', $serviceId);

                $container->setDefinition($managerServiceId, new DefinitionDecorator('dunglas_json_ld_api.data_provider.doctrine.orm'));
                $resourceDefinition->addArgument(new Reference($managerServiceId));
            }

            $resourceCollectionDefinition->addMethodCall(
                'add',
                [new Reference($serviceId)]
            );
        }
    }
}
