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
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('api.title', $config['title']);
        $container->setParameter('api.description', $config['description']);
        $container->setParameter('api.collection.filter_name.order', $config['collection']['filter_name']['order']);
        $container->setParameter('api.collection.order', $config['collection']['order']);
        $container->setParameter('api.collection.pagination.page_parameter_name', $config['collection']['pagination']['page_parameter_name']);
        $container->setParameter('api.collection.pagination.items_per_page.number', $config['collection']['pagination']['items_per_page']['number']);
        $container->setParameter('api.collection.pagination.items_per_page.enable_client_request', $config['collection']['pagination']['items_per_page']['enable_client_request']);
        $container->setParameter('api.collection.pagination.items_per_page.parameter_name', $config['collection']['pagination']['items_per_page']['parameter_name']);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('api.xml');
        $loader->load('property_info.xml');
        $loader->load('mapping.xml');
        $loader->load('doctrine_orm.xml');

        // JSON-LD and Hydra support
        $loader->load('json_ld.xml');
        $loader->load('hydra.xml');

        // Normalizer configuration
        $container->getDefinition('api.json_ld.normalizer.item')
            ->addArgument(
                $config['item_normalizer']['ignore_null_value']
            )
            ->addArgument(
                $config['item_normalizer']['ignore_empty_collection']
            );

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
    }
}
