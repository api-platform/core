<?php

/*
 * This file is part of the API Platform project.
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

            if (self::BUILTIN_RESOURCE !== $this->getClass($container, $resourceDefinition)) {
                continue;
            }

            $isAbstract = $this->isAbstract($resourceDefinition->getArgument(0));
            if (!$resourceDefinition->hasMethodCall('initItemOperations')) {
                $methods = $isAbstract ? ['GET', 'DELETE'] : ['GET', 'PUT', 'DELETE'];
                $resourceDefinition->addMethodCall('initItemOperations', [$this->createOperations($container, $serviceId, $methods, false)]);
            }

            if (!$resourceDefinition->hasMethodCall('initCollectionOperations')) {
                $methods = $isAbstract ? ['GET'] : ['GET', 'POST'];
                $resourceDefinition->addMethodCall('initCollectionOperations', [$this->createOperations($container, $serviceId, $methods, true)]);
            }
        }

        $resourceCollectionDefinition->addMethodCall('init', [$resourceReferences]);
    }

    /**
     * Adds a list of operations.
     *
     * @param ContainerBuilder $container
     * @param string           $serviceId
     * @param array            $methods
     * @param bool             $collection
     *
     * @return Reference[]
     */
    private function createOperations(ContainerBuilder $container, $serviceId, $methods, $collection)
    {
        $operations = [];
        foreach ($methods as $method) {
            $operations[] = $this->createOperation($container, $serviceId, $method, $collection);
        }

        return $operations;
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
     * Returns if the given class is abstract.
     *
     * @param string $instanceClass
     *
     * @return bool
     */
    private function isAbstract($instanceClass)
    {
        $reflectionClass = new \ReflectionClass($instanceClass);

        return $reflectionClass->isAbstract();
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
