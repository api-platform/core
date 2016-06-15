<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Bridge\Symfony\Bundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Resource\DirectoryResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * The extension of this bundle.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ApiPlatformExtension extends Extension implements PrependExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container)
    {
        if (empty($frameworkConfiguration = $container->getExtensionConfig('framework'))) {
            return;
        }

        if (!isset($frameworkConfiguration['serializer']) || !isset($frameworkConfiguration['serializer']['enabled'])) {
            $container->prependExtensionConfig('framework', ['serializer' => ['enabled' => true]]);
        }

        if (!isset($frameworkConfiguration['property_info']) || !isset($frameworkConfiguration['property_info']['enabled'])) {
            $container->prependExtensionConfig('framework', ['property_info' => ['enabled' => true]]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $supportedFormats = [];
        foreach ($config['supported_formats'] as $format => $value) {
            foreach ($value['mime_types'] as $mimeType) {
                $supportedFormats[$mimeType] = $format;
            }
        }

        $container->setAlias('api_platform.routing.resource_path_generator', $config['routing']['resource_path_generator']);

        if ($config['name_converter']) {
            $container->setAlias('api_platform.name_converter', $config['name_converter']);
        }

        $container->setParameter('api_platform.title', $config['title']);
        $container->setParameter('api_platform.description', $config['description']);
        $container->setParameter('api_platform.supported_formats', $supportedFormats);
        $container->setParameter('api_platform.collection.order', $config['collection']['order']);
        $container->setParameter('api_platform.collection.order_parameter_name', $config['collection']['order_parameter_name']);
        $container->setParameter('api_platform.collection.pagination.enabled', $config['collection']['pagination']['enabled']);
        $container->setParameter('api_platform.collection.pagination.client_enabled', $config['collection']['pagination']['client_enabled']);
        $container->setParameter('api_platform.collection.pagination.client_items_per_page', $config['collection']['pagination']['client_items_per_page']);
        $container->setParameter('api_platform.collection.pagination.items_per_page', $config['collection']['pagination']['items_per_page']);
        $container->setParameter('api_platform.collection.pagination.page_parameter_name', $config['collection']['pagination']['page_parameter_name']);
        $container->setParameter('api_platform.collection.pagination.enabled_parameter_name', $config['collection']['pagination']['enabled_parameter_name']);
        $container->setParameter('api_platform.collection.pagination.items_per_page_parameter_name', $config['collection']['pagination']['items_per_page_parameter_name']);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('api.xml');
        $loader->load('metadata.xml');

        $this->enableJsonLd($loader);
        $this->registerAnnotationLoaders($container);
        $this->registerFileLoaders($container);

        if (!interface_exists('phpDocumentor\Reflection\DocBlockFactoryInterface')) {
            $container->removeDefinition('api_platform.metadata.resource.metadata_factory.php_doc');
        }

        $bundles = $container->getParameter('kernel.bundles');

        // Doctrine ORM support
        if (isset($bundles['DoctrineBundle']) && class_exists('Doctrine\ORM\Version')) {
            $loader->load('doctrine_orm.xml');
        }

        // FOSUser support
        if ($config['enable_fos_user']) {
            $loader->load('fos_user.xml');
        }

        // NelmioApiDoc support
        if (isset($bundles['NelmioApiDocBundle']) && $config['enable_nelmio_api_doc']) {
            $loader->load('nelmio_api_doc.xml');
        }
    }

    /**
     * Enables JSON-LD and Hydra support.
     *
     * @param XmlFileLoader $loader
     */
    private function enableJsonLd(XmlFileLoader $loader)
    {
        $loader->load('jsonld.xml');
        $loader->load('hydra.xml');
    }

    /**
     * Registers annotations loaders.
     *
     * @param ContainerBuilder $container
     */
    private function registerAnnotationLoaders(ContainerBuilder $container)
    {
        $paths = [];
        foreach ($container->getParameter('kernel.bundles') as $bundle) {
            $reflectionClass = new \ReflectionClass($bundle);
            $bundleDirectory = dirname($reflectionClass->getFileName());
            $entityDirectory = $bundleDirectory.DIRECTORY_SEPARATOR.'Entity';

            if (file_exists($entityDirectory)) {
                $paths[] = $entityDirectory;
                $container->addResource(new DirectoryResource($entityDirectory, '/\.php$/'));
            }
        }

        $container->getDefinition('api_platform.metadata.resource.name_collection_factory.annotation')->addArgument($paths);
    }

    /**
     * Registers configuration file loaders.
     *
     * @param ContainerBuilder $container
     */
    private function registerFileLoaders(ContainerBuilder $container)
    {
        $prefix = DIRECTORY_SEPARATOR.'Resources'.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR;
        $yamlResources = [];
        $xmlResources = [];

        foreach ($container->getParameter('kernel.bundles') as $bundle) {
            $reflectionClass = new \ReflectionClass($bundle);
            $configDirectory = dirname($reflectionClass->getFileName()).$prefix;
            $yamlResources = array_merge($yamlResources, glob($configDirectory.'api_resources.{yml,yaml}'));

            foreach (Finder::create()->files()->in($configDirectory)->path('api_resources')->name('*.{yml,yaml}') as $file) {
                $yamlResources[] = $file->getRealPath();
            }

            $xmlResources = array_merge($xmlResources, glob($configDirectory.'api_resources.xml'));

            foreach (Finder::create()->files()->in($configDirectory)->path('api_resources')->name('*.xml') as $file) {
                $xmlResources[] = $file->getRealPath();
            }
        }

        $container->getDefinition('api_platform.metadata.resource.name_collection_factory.yaml')->replaceArgument(0, $yamlResources);
        $container->getDefinition('api_platform.metadata.resource.metadata_factory.yaml')->replaceArgument(0, $yamlResources);

        $container->getDefinition('api_platform.metadata.resource.name_collection_factory.xml')->replaceArgument(0, $xmlResources);
        $container->getDefinition('api_platform.metadata.resource.metadata_factory.xml')->replaceArgument(0, $xmlResources);
    }
}
