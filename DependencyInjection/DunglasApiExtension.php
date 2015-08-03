<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * The extension of this bundle.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class DunglasApiExtension extends Extension implements PrependExtensionInterface
{
    const BUILTIN_RESOURCE = 'Dunglas\ApiBundle\Api\Resource';

    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container)
    {
        if (null !== ($frameworkConfiguration = $container->getExtensionConfig('framework'))) {
            if (!isset($frameworkConfiguration['serializer']) || !isset($frameworkConfiguration['serializer']['enabled'])) {
                $container->prependExtensionConfig('framework', [
                    'serializer' => [
                        'enabled' => true,
                    ],
                ]);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('api.title', $config['title']);
        $container->setParameter('api.description', $config['description']);
        $container->setParameter('api.supported_formats', $config['supported_formats']);
        $container->setParameter('api.collection.filter_name.order', $config['collection']['filter_name']['order']);
        $container->setParameter('api.collection.order', $config['collection']['order']);
        $container->setParameter('api.collection.pagination.enabled', $config['collection']['pagination']['enabled']);
        $container->setParameter('api.collection.pagination.client_can_enable', $config['collection']['pagination']['client_can_enable']);
        $container->setParameter('api.collection.pagination.enable_parameter', $config['collection']['pagination']['enable_parameter']);
        $container->setParameter('api.collection.pagination.page_parameter', $config['collection']['pagination']['page_parameter']);
        $container->setParameter('api.collection.pagination.items_per_page.default', $config['collection']['pagination']['items_per_page']['default']);
        $container->setParameter('api.collection.pagination.items_per_page.client_can_change', $config['collection']['pagination']['items_per_page']['client_can_change']);
        $container->setParameter('api.collection.pagination.items_per_page.parameter', $config['collection']['pagination']['items_per_page']['parameter']);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('api.xml');
        $loader->load('property_info.xml');
        $loader->load('mapping.xml');
        $loader->load('doctrine_orm.xml');

        $this->enableJsonLd($container, $loader);

        // FOSUser support
        if ($config['enable_fos_user']) {
            $loader->load('fos_user.xml');
        }

        // Cache
        if (isset($config['cache']) && $config['cache']) {
            $container->setParameter(
                'api.mapping.cache.prefix',
                'api_'.hash('sha256', $container->getParameter('kernel.root_dir'))
            );

            $container->getDefinition('api.mapping.class_metadata_factory')->addArgument(
                new Reference($config['cache'])
            );
        } else {
            $container->removeDefinition('api.cache_warmer.metadata');
        }

        $this->processResources($container, $config['resources']);
    }

    /**
     * process resources configuration
     *
     * @param ContainerBuilder $container
     * @param array $resources
     * @access public
     * @return void
     */
    public function processResources(ContainerBuilder $container, array $resources)
    {
        $resourceReferences = [];
        foreach ($resources as $key => $config) {
            $serviceId = sprintf('api.resources.%s', $key);
            $resourceReferences[] = new Reference($serviceId);
            $resourceDefinition = new Definition($config['resource_class']);
            $resourceDefinition->addArgument($config['entry_class']);

            // resource configuration
            if (!empty($config['short_name'])) {
                $resourceDefinition->addMethodCall('initShortName', [$config['short_name']]);
            }

            // operations
            $resourceDefinition = $this->addItemOperations(
                $resourceDefinition,
                $container,
                $serviceId,
                $config
            );

            $resourceDefinition = $this->addCollectionOperations(
                $resourceDefinition,
                $container,
                $serviceId,
                $config
            );

            // (de)normalization contexts
            if (!empty($config['normalization_groups']) || !empty($config['jsonld_context_embedded'])) {
                $context = [];
                if (!empty($config['normalization_groups'])) {
                    $context['groups'] = $config['normalization_groups'];
                }
                if (!empty($config['jsonld_context_embedded'])) {
                    $context['jsonld_context_embedded'] = $config['jsonld_context_embedded'];
                }
                $resourceDefinition->addMethodCall('initNormalizationContext', [$context]);
            }
            if (!empty($config['denormalization_groups'])) {
                $context = ['groups' => $config['denormalization_groups']];
                $resourceDefinition->addMethodCall('initDenormalizationContext', [$context]);
            }

            // validation group
            if (!empty($config['validation_groups'])) {
                $resourceDefinition->addMethodCall('initDenormalizationContext', [$config['validation_groups']]);
            }

            // filters
            if (!empty($config['filters'])) {
                $filters = array_map(
                    function ($itemName) {
                        return new Reference($itemName);
                    },
                    $config['filters']
                );

                $resourceDefinition->addMethodCall('initFilters', [$filters]);
            }

            $container->setDefinition($serviceId, $resourceDefinition);
        }

        if ($resourceReferences) {
            $container->getDefinition('api.resource_collection')->addMethodCall('init', [$resourceReferences]);
        }
    }

    /**
     * addItemOperations
     *
     * @param Definition $resourceDefinition
     * @param ContainerBuilder $container
     * @param string $serviceId
     * @param array $itemOperations
     * @access private
     * @return Definition
     */
    private function addItemOperations(
        Definition $resourceDefinition,
        ContainerBuilder $container,
        $serviceId,
        $config
    ) {
        $operations = [];
        // standard operations
        foreach ($config['item_operations'] as $operation) {
            $operations[] = $this->createOperation($container, $serviceId, $operation, $operation, false);
        }

        // custom operations
        foreach ($config['item_custom_operations'] as $key => $operation) {
            $operations[] = $this->createOperation(
                $container,
                $serviceId,
                $operation['methods'],
                $key,
                false,
                $operation['path'],
                $operation['controller'],
                $operation['route'],
                $operation['context']
            );
        }

        $resourceDefinition->addMethodCall('initItemOperations', [$operations]);

        return $resourceDefinition;
    }

    /**
     * addCollectionOperations
     *
     * @param Definition $resourceDefinition
     * @param ContainerBuilder $container
     * @param string $serviceId
     * @param array $collectionOperations
     * @access private
     * @return Definition
     */
    private function addCollectionOperations(
        Definition $resourceDefinition,
        ContainerBuilder $container,
        $serviceId,
        $config
    ) {
        $operations = [];
        foreach ($config['collection_operations'] as $operation) {
            $operations[] = $this->createOperation($container, $serviceId, $operation, $operation, true);
        }

        // custom operations
        foreach ($config['collection_custom_operations'] as $key => $operation) {
            $operations[] = $this->createOperation(
                $container,
                $serviceId,
                $operation['methods'],
                $key,
                true,
                $operation['path'],
                $operation['controller'],
                $operation['route'],
                $operation['context']
            );
        }

        $resourceDefinition->addMethodCall('initCollectionOperations', [$operations]);

        return $resourceDefinition;
    }

    /**
     * Adds an operation.
     *
     * @param ContainerBuilder $container
     * @param string           $serviceId
     * @param string           $method
     * @param bool             $collection
     * @param string           $path
     * @param string           $controller
     * @param string           $routeName
     * @param array            $context
     *
     * @return Reference
     */
    private function createOperation(
        ContainerBuilder $container,
        $serviceId,
        $method,
        $methodName,
        $collection,
        $path = null,
        $controller = null,
        $routeName = null,
        array $context = []
    ) {
        if ($collection) {
            $factoryMethodName = 'createCollectionOperation';
            $operationId = '.collection_operation.';
        } else {
            $factoryMethodName = 'createItemOperation';
            $operationId = '.item_operation.';
        }

        $operation = new Definition(
            'Dunglas\ApiBundle\Api\Operation\Operation',
            [new Reference($serviceId), $method, $path, $controller, $routeName, $context]
        );
        $operation->setFactory([new Reference('api.operation_factory'), $factoryMethodName]);
        $operation->setLazy(true);

        $operationId = $serviceId.$operationId.$methodName;
        $container->setDefinition($operationId, $operation);

        return new Reference($operationId);
    }

    /**
     * Enables JSON-LD and Hydra support.
     *
     * @param ContainerBuilder     $container
     * @param Loader\XmlFileLoader $loader
     */
    private function enableJsonLd(ContainerBuilder $container, Loader\XmlFileLoader $loader)
    {
        $loader->load('jsonld.xml');
        $loader->load('hydra.xml');
    }
}
