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
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Add resources to the resource collection and populate operations if necessary.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ResourcePass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $resourceCollectionDefinition = $container->getDefinition('api.resource_collection');

        foreach ($container->findTaggedServiceIds('api.resource') as $serviceId => $tags) {
            $resourceDefinition = $container->getDefinition($serviceId);

            if (!$resourceDefinition->hasMethodCall('addItemOperation')) {
                $this->addOperation($container, $resourceDefinition, $serviceId, 'GET', false);
                $this->addOperation($container, $resourceDefinition, $serviceId, 'PUT', false);
                $this->addOperation($container, $resourceDefinition, $serviceId, 'DELETE', false);
            }

            if (!$resourceDefinition->hasMethodCall('addCollectionOperation')) {
                $this->addOperation($container, $resourceDefinition, $serviceId, 'GET', true);
                $this->addOperation($container, $resourceDefinition, $serviceId, 'POST', true);
            }

            $resourceCollectionDefinition->addMethodCall(
                'add',
                [new Reference($serviceId)]
            );
        }
    }

    /**
     * Adds an operation.
     *
     * @param ContainerBuilder $container
     * @param Definition       $definition
     * @param string           $serviceId
     * @param string           $method
     * @param bool             $collection
     */
    private function addOperation(ContainerBuilder $container, Definition $definition, $serviceId, $method, $collection)
    {
        if ($collection) {
            $factoryMethodName = 'createCollectionOperation';
            $resourceMethodName = 'addCollectionOperation';
            $operationId = '.collection_operation.';
        } else {
            $factoryMethodName = 'createItemOperation';
            $resourceMethodName = 'addItemOperation';
            $operationId = '.item_operation.';
        }

        $operation = new Definition(
            'Dunglas\JsonLdApiBundle\Api\Operation\Operation',
            [new Reference($serviceId), $method]
        );
        $operation->setFactory([new Reference('api.operation_factory'), $factoryMethodName]);

        $operationId = $serviceId.$operationId.$method;
        $container->setDefinition($operationId, $operation);

        $definition->addMethodCall($resourceMethodName, [new Reference($operationId)]);
    }
}
