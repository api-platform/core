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

            if (self::BUILTIN_RESOURCE !== $this->getClass($container, $resourceDefinition)) {
                continue;
            }

            $this->setDefaultValue($resourceDefinition, 'initItemOperations', [[
                $this->createOperation($container, $serviceId, 'GET', false),
                $this->createOperation($container, $serviceId, 'PUT', false),
                $this->createOperation($container, $serviceId, 'DELETE', false),
            ]]);

            $this->setDefaultValue($resourceDefinition, 'initCollectionOperations', [[
                $this->createOperation($container, $serviceId, 'GET', true),
                $this->createOperation($container, $serviceId, 'POST', true),
            ]]);

            $this->setDefaultValue($resourceDefinition, 'initPaginationEnabledByDefault', [
                $container->getParameter('api.collection.pagination.enabled'),
            ]);

            $this->setDefaultValue($resourceDefinition, 'initPaginationEnabledByDefault', [
                $container->getParameter('api.collection.pagination.enabled'),
            ]);

            $this->setDefaultValue($resourceDefinition, 'initClientAllowedToEnablePagination', [
                $container->getParameter('api.collection.pagination.client_can_enable'),
            ]);

            $this->setDefaultValue($resourceDefinition, 'initEnablePaginationParameter', [
                $container->getParameter('api.collection.pagination.enable_parameter'),
            ]);

            $this->setDefaultValue($resourceDefinition, 'initPageParameter', [
                $container->getParameter('api.collection.pagination.page_parameter'),
            ]);

            $this->setDefaultValue($resourceDefinition, 'initItemsPerPageByDefault', [
                $container->getParameter('api.collection.pagination.items_per_page.default'),
            ]);

            $this->setDefaultValue($resourceDefinition, 'initClientAllowedToChangeItemsPerPage', [
                $container->getParameter('api.collection.pagination.items_per_page.client_can_change'),
            ]);

            $this->setDefaultValue($resourceDefinition, 'initItemsPerPageParameter', [
                $container->getParameter('api.collection.pagination.items_per_page.parameter'),
            ]);
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

    private function setDefaultValue(Definition $definition, $method, $values)
    {
        if (!$definition->hasMethodCall($method)) {
            $definition->addMethodCall($method, $values);
        }
    }
}
