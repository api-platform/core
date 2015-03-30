<?php

/*
 * This file is part of the DunglasJsonLdApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\JsonLdApiBundle\DependencyInjection;

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
class DunglasJsonLdApiExtension extends Extension implements PrependExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container)
    {
        if (isset($container->getParameter('kernel.bundles')['FOSUserBundle'])) {
            $container->prependExtensionConfig($this->getAlias(), ['enable_fos_user_event_subscriber' => true]);
        }

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

        $container->setParameter('dunglas_json_ld_api.title', $config['title']);
        $container->setParameter('dunglas_json_ld_api.description', $config['description']);
        $container->setParameter('dunglas_json_ld_api.default.items_per_page', $config['default']['items_per_page']);
        $container->setParameter('dunglas_json_ld_api.default.order', $config['default']['order']);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('doctrine_orm.xml');
        $loader->load('property_info.xml');
        $loader->load('serializer.xml');
        $loader->load('metadata.xml');
        $loader->load('api.xml');

        if ($config['enable_fos_user_event_subscriber']) {
            $definition = new Definition(
                'Dunglas\JsonLdApiBundle\FosUser\EventSubscriber',
                [new Reference('event_dispatcher'), new Reference('fos_user.user_manager')]
            );
            $definition->setTags(['kernel.event_subscriber' => []]);

            $container->setDefinition('dunglas_json_ld_api.event_subscriber.fos_user', $definition);
        }

        if (isset($config['cache']) && $config['cache']) {
            $container->setParameter(
                'dunglas_json_ld_api.mapping.cache.prefix',
                'dunglas_json_ld_api_'.hash('sha256', $container->getParameter('kernel.root_dir'))
            );

            $container->getDefinition('dunglas_json_ld_api.mapping.class_metadata_factory')->replaceArgument(
                4, new Reference($config['cache'])
            );
        } else {
            $container->removeDefinition('dunglas_json_ld_api.cache_warmer.metadata');
        }
    }
}
