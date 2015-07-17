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
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Add resources to the resource collection and populate operations if necessary.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ResourcePass implements CompilerPassInterface
{
    const BUILTIN_RESOURCE = 'Dunglas\ApiBundle\Api\Resource';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $resourceCollectionDefinition = $container->getDefinition('api.resource_collection');
        $resourceReferences = [];

        foreach ($container->findTaggedServiceIds('api.resource') as $serviceId => $tags) {
            $resourceReferences[] = new Reference($serviceId);
            $resourceDefinition = $container->getDefinition($serviceId);
            $serviceProperties = $resourceDefinition->getProperties();

            if (self::BUILTIN_RESOURCE !== $this->getClass($container, $resourceDefinition)) {
                continue;
            }

            // get the properties and reset them to avoid property-injection
            $serviceProperties = $resourceDefinition->getProperties();
            $resourceDefinition->setProperties([]);

            if (!$resourceDefinition->hasMethodCall('initItemOperations')) {
                if (empty($serviceProperties['itemOperations'])) {
                    $serviceProperties['itemOperations'] = ['GET', 'PUT', 'DELETE'];
                }

                $operations = [];
                foreach ($serviceProperties['itemOperations'] as $itemOperation) {
                    $operations[] = $this->createOperation($container, $serviceId, $itemOperation, false);
                }

                $resourceDefinition->addMethodCall('initItemOperations', [$operations]);
            }

            if (!$resourceDefinition->hasMethodCall('initCollectionOperations')) {
                if (empty($serviceProperties['collectionOperations'])) {
                    $serviceProperties['collectionOperations'] = ['GET', 'POST'];
                }

                $operations = [];
                foreach ($serviceProperties['collectionOperations'] as $itemOperation) {
                    $operations[] = $this->createOperation($container, $serviceId, $itemOperation, true);
                }

                $resourceDefinition->addMethodCall('initCollectionOperations', [$operations]);
            }
        }

        $resourceCollectionDefinition->addMethodCall('init', [$resourceReferences]);
    }

    /**
     * Adds an operation.
     *
     * @param ContainerBuilder $container
     * @param string           $serviceId
     * @param string           $method
     * @param bool             $collection
     *
     * @return Reference
     */
    private function createOperation(ContainerBuilder $container, $serviceId, $method, $collection)
    {
        if ($collection) {
            $factoryMethodName = 'createCollectionOperation';
            $operationId = '.collection_operation.';
        } else {
            $factoryMethodName = 'createItemOperation';
            $operationId = '.item_operation.';
        }

        $operation = new Definition(
            'Dunglas\ApiBundle\Api\Operation\Operation',
            [new Reference($serviceId), $method]
        );
        $operation->setFactory([new Reference('api.operation_factory'), $factoryMethodName]);
        $operation->setLazy(true);

        $operationId = $serviceId.$operationId.$method;
        $container->setDefinition($operationId, $operation);

        return new Reference($operationId);
    }

    /**
     * Gets class of the given definition.
     *
     * @param ContainerBuilder $container
     * @param Definition       $definition
     *
     * @return string|null
     */
    private function getClass(ContainerBuilder $container, Definition $definition)
    {
        if ($class = $definition->getClass()) {
            return $class;
        }

        if ($definition instanceof DefinitionDecorator) {
            return $container->getDefinition($definition->getParent())->getClass();
        }
    }
}
