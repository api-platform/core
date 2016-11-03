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

use Doctrine\ORM\Version;
use phpDocumentor\Reflection\DocBlockFactoryInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
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

        if (!isset($frameworkConfiguration['serializer'], $frameworkConfiguration['serializer']['enabled'])) {
            $container->prependExtensionConfig('framework', ['serializer' => ['enabled' => true]]);
        }

        if (!isset($frameworkConfiguration['property_info'], $frameworkConfiguration['property_info']['enabled'])) {
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
        $formats = $this->getFormats($config['formats']);
        $errorFormats = $this->getFormats($config['error_formats']);
        $this->handleConfig($container, $config, $formats, $errorFormats);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('api.xml');
        $loader->load('data_provider.xml');

        $bundles = $container->getParameter('kernel.bundles');

        $this->registerMetadataConfiguration($container, $loader);
        $this->registerDoctrineExtensionConfiguration($container, $config);
        $this->registerSwaggerConfiguration($container, $config, $loader);
        $this->registerSwaggerConfiguration($container, $config, $loader);
        $this->registerJsonLdConfiguration($formats, $loader);
        $this->registerJsonHalConfiguration($formats, $loader);
        $this->registerJsonProblemConfiguration($errorFormats, $loader);
        $this->registerLoaders($container, $bundles);
        $this->registerBundlesConfiguration($bundles, $config, $loader);
        $this->registerCacheConfiguration($container);
    }

    /**
     * Handles configuration.
     *
     * @param ContainerBuilder $container
     * @param array            $config
     * @param array            $formats
     * @param array            $errorFormats
     */
    private function handleConfig(ContainerBuilder $container, array $config, array $formats, array $errorFormats)
    {
        $container->setParameter('api_platform.title', $config['title']);
        $container->setParameter('api_platform.description', $config['description']);
        $container->setParameter('api_platform.version', $config['version']);
        $container->setParameter('api_platform.exception_to_status', $config['exception_to_status']);
        $container->setParameter('api_platform.formats', $formats);
        $container->setParameter('api_platform.error_formats', $errorFormats);
        $container->setParameter('api_platform.eager_loading.enabled', $config['eager_loading']['enabled']);
        $container->setParameter('api_platform.eager_loading.max_joins', $config['eager_loading']['max_joins']);
        $container->setParameter('api_platform.eager_loading.eager_only', $config['eager_loading']['eager_only']);
        $container->setParameter('api_platform.collection.order', $config['collection']['order']);
        $container->setParameter('api_platform.collection.order_parameter_name', $config['collection']['order_parameter_name']);
        $container->setParameter('api_platform.collection.pagination.enabled', $config['collection']['pagination']['enabled']);
        $container->setParameter('api_platform.collection.pagination.client_enabled', $config['collection']['pagination']['client_enabled']);
        $container->setParameter('api_platform.collection.pagination.client_items_per_page', $config['collection']['pagination']['client_items_per_page']);
        $container->setParameter('api_platform.collection.pagination.items_per_page', $config['collection']['pagination']['items_per_page']);
        $container->setParameter('api_platform.collection.pagination.maximum_items_per_page', $config['collection']['pagination']['maximum_items_per_page']);
        $container->setParameter('api_platform.collection.pagination.page_parameter_name', $config['collection']['pagination']['page_parameter_name']);
        $container->setParameter('api_platform.collection.pagination.enabled_parameter_name', $config['collection']['pagination']['enabled_parameter_name']);
        $container->setParameter('api_platform.collection.pagination.items_per_page_parameter_name', $config['collection']['pagination']['items_per_page_parameter_name']);

        $container->setAlias('api_platform.operation_path_resolver.default', $config['default_operation_path_resolver']);

        if ($config['name_converter']) {
            $container->setAlias('api_platform.name_converter', $config['name_converter']);
        }
    }

    /**
     * Registers metadata configuration.
     *
     * @param ContainerBuilder $container
     * @param XmlFileLoader    $loader
     */
    private function registerMetadataConfiguration(ContainerBuilder $container, XmlFileLoader $loader)
    {
        $loader->load('metadata.xml');

        if (!interface_exists(DocBlockFactoryInterface::class)) {
            $container->removeDefinition('api_platform.metadata.resource.metadata_factory.php_doc');
        }
    }

    /**
     * Registers the Swagger and Swagger UI configuration.
     *
     * @param ContainerBuilder $container
     * @param array            $config
     * @param XmlFileLoader    $loader
     */
    private function registerSwaggerConfiguration(ContainerBuilder $container, array $config, XmlFileLoader $loader)
    {
        if (!$config['enable_swagger']) {
            return;
        }

        $loader->load('swagger.xml');
        $container->setParameter('api_platform.enable_swagger', (string) $config['enable_swagger']);
    }

    /**
     * Registers the JSON-LD and Hydra configuration.
     *
     * @param array         $formats
     * @param XmlFileLoader $loader
     */
    private function registerJsonLdConfiguration(array $formats, XmlFileLoader $loader)
    {
        if (!isset($formats['jsonld'])) {
            return;
        }

        $loader->load('jsonld.xml');
        $loader->load('hydra.xml');
    }

    /**
     * Registers the HAL configuration.
     *
     * @param array         $formats
     * @param XmlFileLoader $loader
     */
    private function registerJsonHalConfiguration(array $formats, XmlFileLoader $loader)
    {
        if (!isset($formats['jsonhal'])) {
            return;
        }

        $loader->load('hal.xml');
    }

    /**
     * Registers the JSON Problem configuration.
     *
     * @param array         $errorFormats
     * @param XmlFileLoader $loader
     */
    private function registerJsonProblemConfiguration(array $errorFormats, XmlFileLoader $loader)
    {
        if (!isset($errorFormats['jsonproblem'])) {
            return;
        }

        $loader->load('problem.xml');
    }

    /**
     * Registers configuration for integration with third-party bundles.
     *
     * @param string[]      $bundles
     * @param array         $config
     * @param XmlFileLoader $loader
     */
    private function registerBundlesConfiguration(array $bundles, array $config, XmlFileLoader $loader)
    {
        // Doctrine ORM support
        if (isset($bundles['DoctrineBundle']) && class_exists(Version::class)) {
            $loader->load('doctrine_orm.xml');
        }

        // FOSUser support
        if (isset($bundles['FOSUserBundle']) && $config['enable_fos_user']) {
            $loader->load('fos_user.xml');
        }

        // NelmioApiDoc support
        if (isset($bundles['NelmioApiDocBundle']) && $config['enable_nelmio_api_doc']) {
            $loader->load('nelmio_api_doc.xml');
        }
    }

    /**
     * Registers the cache configuration.
     *
     * @param ContainerBuilder $container
     */
    private function registerCacheConfiguration(ContainerBuilder $container)
    {
        // Don't use system cache pool in dev
        if (!$container->getParameter('kernel.debug')) {
            return;
        }

        $container->register('api_platform.cache.metadata.property', ArrayAdapter::class);
        $container->register('api_platform.cache.metadata.resource', ArrayAdapter::class);
        $container->register('api_platform.cache.route_name_resolver', ArrayAdapter::class);
    }

    /**
     * Registers configuration file loaders.
     *
     * @param ContainerBuilder $container
     * @param string[]         $bundles
     */
    private function registerLoaders(ContainerBuilder $container, array $bundles)
    {
        $annotationPaths = [];
        $yamlResources = [];
        $xmlResources = [];

        foreach ($bundles as $bundle) {
            $bundleDirectory = dirname((new \ReflectionClass($bundle))->getFileName());
            $this->addFileResources($bundleDirectory, $xmlResources, $yamlResources);

            if (file_exists($entityDirectory = $bundleDirectory.'/Entity')) {
                $annotationPaths[] = $entityDirectory;
                $container->addResource(new DirectoryResource($entityDirectory, '/\.php$/'));
            }
        }

        $container->getDefinition('api_platform.metadata.resource.name_collection_factory.annotation')->addArgument($annotationPaths);

        $container->getDefinition('api_platform.metadata.resource.name_collection_factory.yaml')->replaceArgument(0, $yamlResources);
        $container->getDefinition('api_platform.metadata.resource.metadata_factory.yaml')->replaceArgument(0, $yamlResources);

        $container->getDefinition('api_platform.metadata.property.name_collection_factory.yaml')->replaceArgument(0, $yamlResources);
        $container->getDefinition('api_platform.metadata.property.metadata_factory.yaml')->replaceArgument(0, $yamlResources);

        $container->getDefinition('api_platform.metadata.resource.name_collection_factory.xml')->replaceArgument(0, $xmlResources);
        $container->getDefinition('api_platform.metadata.resource.metadata_factory.xml')->replaceArgument(0, $xmlResources);

        $container->getDefinition('api_platform.metadata.property.name_collection_factory.xml')->replaceArgument(0, $xmlResources);
        $container->getDefinition('api_platform.metadata.property.metadata_factory.xml')->replaceArgument(0, $xmlResources);
    }

    /**
     * Manipulate doctrine extension services according to the configuration.
     *
     * @param ContainerBuilder $container
     * @param array            $config
     */
    private function registerDoctrineExtensionConfiguration(ContainerBuilder $container, array $config)
    {
        if (false === $config['eager_loading']['enabled']) {
            $container->removeDefinition('api_platform.doctrine.orm.query_extension.eager_loading');
        }
    }

    /**
     * Populates file resources lists.
     *
     * @param string   $bundleDirectory
     * @param string[] $xmlResources
     * @param string[] $yamlResources
     */
    private function addFileResources(string $bundleDirectory, array &$xmlResources, array &$yamlResources)
    {
        try {
            foreach (Finder::create()->files()->in($bundleDirectory.'/Resources/config/')->path('api_resources')->name('*.{yml,yaml,xml}') as $file) {
                if ('xml' === $file->getExtension()) {
                    $xmlResources[] = $file->getRealPath();
                } else {
                    $yamlResources[] = $file->getRealPath();
                }
            }
        } catch (\InvalidArgumentException $e) {
            // Ignore invalid paths
        }
    }

    /**
     * Normalizes the format from config to the one accepted by Symfony HttpFoundation.
     *
     * @param array $configFormats
     *
     * @return array
     */
    private function getFormats(array $configFormats) : array
    {
        $formats = [];
        foreach ($configFormats as $format => $value) {
            foreach ($value['mime_types'] as $mimeType) {
                $formats[$format][] = $mimeType;
            }
        }

        return $formats;
    }
}
