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
