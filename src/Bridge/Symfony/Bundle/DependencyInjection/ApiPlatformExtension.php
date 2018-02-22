<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ApiPlatform\Core\Bridge\Symfony\Bundle\DependencyInjection;

use ApiPlatform\Core\Api\FilterInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\EagerLoadingExtension;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\FilterEagerLoadingExtension;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use ApiPlatform\Core\DataProvider\CollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\SubresourceDataProviderInterface;
use ApiPlatform\Core\Exception\RuntimeException;
use Doctrine\Common\Annotations\Annotation;
use Doctrine\ORM\Version;
use phpDocumentor\Reflection\DocBlockFactoryInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Resource\DirectoryResource;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Yaml\Yaml;

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
        if (!$frameworkConfiguration = $container->getExtensionConfig('framework')) {
            return;
        }

        foreach ($frameworkConfiguration as $frameworkParameters) {
            if (isset($frameworkParameters['serializer'])) {
                $serializerConfig = $serializerConfig ?? $frameworkParameters['serializer'];
            }

            if (isset($frameworkParameters['property_info'])) {
                $propertyInfoConfig = $propertyInfoConfig ?? $frameworkParameters['property_info'];
            }
        }

        if (!isset($serializerConfig['enabled'])) {
            $container->prependExtensionConfig('framework', ['serializer' => ['enabled' => true]]);
        }

        if (!isset($propertyInfoConfig['enabled'])) {
            $container->prependExtensionConfig('framework', ['property_info' => ['enabled' => true]]);
        }

        if (isset($serializerConfig['name_converter'])) {
            $container->prependExtensionConfig('api_platform', ['name_converter' => $serializerConfig['name_converter']]);
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
        $loader->load('data_persister.xml');
        $loader->load('data_provider.xml');
        $loader->load('filter.xml');

        $container->registerForAutoconfiguration(DataPersisterInterface::class)
            ->addTag('api_platform.data_persister');
        $container->registerForAutoconfiguration(ItemDataProviderInterface::class)
            ->addTag('api_platform.item_data_provider');
        $container->registerForAutoconfiguration(CollectionDataProviderInterface::class)
            ->addTag('api_platform.collection_data_provider');
        $container->registerForAutoconfiguration(SubresourceDataProviderInterface::class)
            ->addTag('api_platform.subresource_data_provider');
        $container->registerForAutoconfiguration(QueryItemExtensionInterface::class)
            ->addTag('api_platform.doctrine.orm.query_extension.item');
        $container->registerForAutoconfiguration(QueryCollectionExtensionInterface::class)
            ->addTag('api_platform.doctrine.orm.query_extension.collection');
        $container->registerForAutoconfiguration(FilterInterface::class)
            ->addTag('api_platform.filter');

        if (interface_exists(ValidatorInterface::class)) {
            $loader->load('validator.xml');
        }

        $bundles = $container->getParameter('kernel.bundles');
        if (isset($bundles['SecurityBundle'])) {
            $loader->load('security.xml');
        }

        $useDoctrine = isset($bundles['DoctrineBundle']) && class_exists(Version::class);

        $this->registerMetadataConfiguration($container, $config, $loader);
        $this->registerOAuthConfiguration($container, $config, $loader);
        $this->registerApiKeysConfiguration($container, $config, $loader);
        $this->registerSwaggerConfiguration($container, $config, $loader);
        $this->registerJsonApiConfiguration($formats, $loader);
        $this->registerJsonLdConfiguration($formats, $loader);
        $this->registerJsonHalConfiguration($formats, $loader);
        $this->registerJsonProblemConfiguration($errorFormats, $loader);
        $this->registerGraphqlConfiguration($container, $config, $loader);
        $this->registerBundlesConfiguration($bundles, $config, $loader, $useDoctrine);
        $this->registerCacheConfiguration($container);
        $this->registerDoctrineExtensionConfiguration($container, $config, $useDoctrine);
        $this->registerHttpCache($container, $config, $loader, $useDoctrine);
        $this->registerValidatorConfiguration($container, $config, $loader);
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
        $container->setParameter('api_platform.enable_entrypoint', $config['enable_entrypoint']);
        $container->setParameter('api_platform.enable_docs', $config['enable_docs']);
        $container->setParameter('api_platform.title', $config['title']);
        $container->setParameter('api_platform.description', $config['description']);
        $container->setParameter('api_platform.version', $config['version']);
        $container->setParameter('api_platform.exception_to_status', $config['exception_to_status']);
        $container->setParameter('api_platform.formats', $formats);
        $container->setParameter('api_platform.error_formats', $errorFormats);
        $container->setParameter('api_platform.allow_plain_identifiers', $config['allow_plain_identifiers']);
        $container->setParameter('api_platform.eager_loading.enabled', $config['eager_loading']['enabled']);
        $container->setParameter('api_platform.eager_loading.max_joins', $config['eager_loading']['max_joins']);
        $container->setParameter('api_platform.eager_loading.fetch_partial', $config['eager_loading']['fetch_partial']);
        $container->setParameter('api_platform.eager_loading.force_eager', $config['eager_loading']['force_eager']);
        $container->setParameter('api_platform.collection.order', $config['collection']['order']);
        $container->setParameter('api_platform.collection.order_parameter_name', $config['collection']['order_parameter_name']);
        $container->setParameter('api_platform.collection.pagination.enabled', $config['collection']['pagination']['enabled']);
        $container->setParameter('api_platform.collection.pagination.partial', $config['collection']['pagination']['partial']);
        $container->setParameter('api_platform.collection.pagination.client_enabled', $config['collection']['pagination']['client_enabled']);
        $container->setParameter('api_platform.collection.pagination.client_items_per_page', $config['collection']['pagination']['client_items_per_page']);
        $container->setParameter('api_platform.collection.pagination.client_partial', $config['collection']['pagination']['client_partial']);
        $container->setParameter('api_platform.collection.pagination.items_per_page', $config['collection']['pagination']['items_per_page']);
        $container->setParameter('api_platform.collection.pagination.maximum_items_per_page', $config['collection']['pagination']['maximum_items_per_page']);
        $container->setParameter('api_platform.collection.pagination.page_parameter_name', $config['collection']['pagination']['page_parameter_name']);
        $container->setParameter('api_platform.collection.pagination.enabled_parameter_name', $config['collection']['pagination']['enabled_parameter_name']);
        $container->setParameter('api_platform.collection.pagination.items_per_page_parameter_name', $config['collection']['pagination']['items_per_page_parameter_name']);
        $container->setParameter('api_platform.collection.pagination.partial_parameter_name', $config['collection']['pagination']['partial_parameter_name']);
        $container->setParameter('api_platform.http_cache.etag', $config['http_cache']['etag']);
        $container->setParameter('api_platform.http_cache.max_age', $config['http_cache']['max_age']);
        $container->setParameter('api_platform.http_cache.shared_max_age', $config['http_cache']['shared_max_age']);
        $container->setParameter('api_platform.http_cache.vary', $config['http_cache']['vary']);
        $container->setParameter('api_platform.http_cache.public', $config['http_cache']['public']);

        $container->setAlias('api_platform.operation_path_resolver.default', $config['default_operation_path_resolver']);
        $container->setAlias('api_platform.path_segment_name_generator', $config['path_segment_name_generator']);

        if ($config['name_converter']) {
            $container->setAlias('api_platform.name_converter', $config['name_converter']);
        }
    }

    /**
     * Registers metadata configuration.
     *
     * @param ContainerBuilder $container
     * @param array            $config
     * @param XmlFileLoader    $loader
     */
    private function registerMetadataConfiguration(ContainerBuilder $container, array $config, XmlFileLoader $loader)
    {
        $loader->load('metadata/metadata.xml');
        $loader->load('metadata/xml.xml');

        list($xmlResources, $yamlResources) = $this->getResourcesToWatch($container, $config['mapping']['paths']);

        if (isset($config['resource_class_directories']) && $config['resource_class_directories']) {
            $container->setParameter('api_platform.resource_class_directories', array_merge(
                $config['resource_class_directories'], $container->getParameter('api_platform.resource_class_directories')
            ));
        }

        $container->getDefinition('api_platform.metadata.extractor.xml')->addArgument($xmlResources);

        if (class_exists(Annotation::class)) {
            $loader->load('metadata/annotation.xml');
        }

        if (class_exists(Yaml::class)) {
            $loader->load('metadata/yaml.xml');
            $container->getDefinition('api_platform.metadata.extractor.yaml')->addArgument($yamlResources);
        }

        if (interface_exists(DocBlockFactoryInterface::class)) {
            $loader->load('metadata/php_doc.xml');
        }
    }

    private function getBundlesResourcesPaths(ContainerBuilder $container): array
    {
        $bundlesResourcesPaths = [];

        foreach ($container->getParameter('kernel.bundles_metadata') as $bundle) {
            $paths = [];
            $dirname = $bundle['path'];
            foreach (['.yaml', '.yml', '.xml', ''] as $extension) {
                $paths[] = "$dirname/Resources/config/api_resources$extension";
            }
            $paths[] = "$dirname/Entity";

            foreach ($paths as $path) {
                if ($container->fileExists($path, false)) {
                    $bundlesResourcesPaths[] = $path;
                }
            }
        }

        return $bundlesResourcesPaths;
    }

    private function getResourcesToWatch(ContainerBuilder $container, array $resourcesPaths): array
    {
        $paths = array_unique(array_merge($resourcesPaths, $this->getBundlesResourcesPaths($container)));

        // Flex structure (only if nothing specified)
        $projectDir = $container->getParameter('kernel.project_dir');
        if (!$paths && is_dir($dir = "$projectDir/config/api_platform")) {
            $paths = [$dir];
        }

        $resources = ['yml' => [], 'xml' => [], 'dir' => []];

        foreach ($paths as $path) {
            if (is_dir($path)) {
                foreach (Finder::create()->followLinks()->files()->in($path)->name('/\.(xml|ya?ml)$/') as $file) {
                    $resources['yaml' === ($extension = $file->getExtension()) ? 'yml' : $extension][] = $file->getRealPath();
                }

                $resources['dir'][] = $path;
                $container->addResource(new DirectoryResource($path, '/\.(xml|ya?ml|php)$/'));

                continue;
            }

            if ($container->fileExists($path, false)) {
                if (!preg_match('/\.(xml|ya?ml)$/', $path, $matches)) {
                    throw new RuntimeException(sprintf('Unsupported mapping type in "%s", supported types are XML & Yaml.', $path));
                }

                $resources['yaml' === $matches[1] ? 'yml' : $matches[1]][] = $path;

                continue;
            }

            throw new RuntimeException(sprintf('Could not open file or directory "%s".', $path));
        }

        $container->setParameter('api_platform.resource_class_directories', $resources['dir']);

        return [$resources['xml'], $resources['yml']];
    }

    /**
     * Registers the OAuth configuration.
     *
     * @param ContainerBuilder $container
     * @param array            $config
     * @param XmlFileLoader    $loader
     */
    private function registerOAuthConfiguration(ContainerBuilder $container, array $config, XmlFileLoader $loader)
    {
        if (!$config['oauth']) {
            return;
        }

        $container->setParameter('api_platform.oauth.enabled', $config['oauth']['enabled']);
        $container->setParameter('api_platform.oauth.clientId', $config['oauth']['clientId']);
        $container->setParameter('api_platform.oauth.clientSecret', $config['oauth']['clientSecret']);
        $container->setParameter('api_platform.oauth.type', $config['oauth']['type']);
        $container->setParameter('api_platform.oauth.flow', $config['oauth']['flow']);
        $container->setParameter('api_platform.oauth.tokenUrl', $config['oauth']['tokenUrl']);
        $container->setParameter('api_platform.oauth.authorizationUrl', $config['oauth']['authorizationUrl']);
        $container->setParameter('api_platform.oauth.scopes', $config['oauth']['scopes']);
    }

    /**
     * Registers the api keys configuration.
     *
     * @param ContainerBuilder $container
     * @param array            $config
     * @param XmlFileLoader    $loader
     */
    private function registerApiKeysConfiguration(ContainerBuilder $container, array $config, XmlFileLoader $loader)
    {
        $container->setParameter('api_platform.swagger.api_keys', $config['swagger']['api_keys']);
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

        if ($config['enable_swagger_ui']) {
            $loader->load('swagger-ui.xml');
            $container->setParameter('api_platform.enable_swagger_ui', $config['enable_swagger_ui']);
        }

        $container->setParameter('api_platform.enable_swagger', $config['enable_swagger']);
    }

    /**
     * Registers the JsonApi configuration.
     *
     * @param array         $formats
     * @param XmlFileLoader $loader
     */
    private function registerJsonApiConfiguration(array $formats, XmlFileLoader $loader)
    {
        if (!isset($formats['jsonapi'])) {
            return;
        }

        $loader->load('jsonapi.xml');
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
     * Registers the GraphQL configuration.
     *
     * @param ContainerBuilder $container
     * @param array            $config
     * @param XmlFileLoader    $loader
     */
    private function registerGraphqlConfiguration(ContainerBuilder $container, array $config, XmlFileLoader $loader)
    {
        if (!$config['graphql']) {
            return;
        }

        $container->setParameter('api_platform.graphql.enabled', $config['graphql']['enabled']);
        $container->setParameter('api_platform.graphql.graphiql.enabled', $config['graphql']['graphiql']['enabled']);

        $loader->load('graphql.xml');
    }

    /**
     * Registers configuration for integration with third-party bundles.
     *
     * @param string[]      $bundles
     * @param array         $config
     * @param XmlFileLoader $loader
     * @param bool          $useDoctrine
     */
    private function registerBundlesConfiguration(array $bundles, array $config, XmlFileLoader $loader, bool $useDoctrine)
    {
        // Doctrine ORM support
        if ($useDoctrine) {
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
        $container->register('api_platform.cache.identifiers_extractor', ArrayAdapter::class);
        $container->register('api_platform.cache.subresource_operation_factory', ArrayAdapter::class);
    }

    /**
     * Manipulate doctrine extension services according to the configuration.
     *
     * @param ContainerBuilder $container
     * @param array            $config
     * @param bool             $useDoctrine
     */
    private function registerDoctrineExtensionConfiguration(ContainerBuilder $container, array $config, bool $useDoctrine)
    {
        if (!$useDoctrine || $config['eager_loading']['enabled']) {
            return;
        }

        $container->removeAlias(EagerLoadingExtension::class);
        $container->removeDefinition('api_platform.doctrine.orm.query_extension.eager_loading');
        $container->removeAlias(FilterEagerLoadingExtension::class);
        $container->removeDefinition('api_platform.doctrine.orm.query_extension.filter_eager_loading');
    }

    private function registerHttpCache(ContainerBuilder $container, array $config, XmlFileLoader $loader, bool $useDoctrine)
    {
        $loader->load('http_cache.xml');

        if (!$config['http_cache']['invalidation']['enabled']) {
            return;
        }

        if ($useDoctrine) {
            $loader->load('doctrine_orm_http_cache_purger.xml');
        }

        $loader->load('http_cache_tags.xml');

        $definitions = [];
        foreach ($config['http_cache']['invalidation']['varnish_urls'] as $key => $url) {
            $definition = new ChildDefinition('api_platform.http_cache.purger.varnish_client');
            $definition->addArgument(['base_uri' => $url]);

            $definitions[] = $definition;
        }

        $container->getDefinition('api_platform.http_cache.purger.varnish')->addArgument($definitions);
        $container->setAlias('api_platform.http_cache.purger', 'api_platform.http_cache.purger.varnish');
    }

    /**
     * Normalizes the format from config to the one accepted by Symfony HttpFoundation.
     *
     * @param array $configFormats
     *
     * @return array
     */
    private function getFormats(array $configFormats): array
    {
        $formats = [];
        foreach ($configFormats as $format => $value) {
            foreach ($value['mime_types'] as $mimeType) {
                $formats[$format][] = $mimeType;
            }
        }

        return $formats;
    }

    /**
     * Registers the Validator configuration.
     *
     * @param ContainerBuilder $container
     * @param array            $config
     * @param XmlFileLoader    $loader
     */
    private function registerValidatorConfiguration(ContainerBuilder $container, array $config, XmlFileLoader $loader)
    {
        if (!$config['validator']) {
            return;
        }

        $container->setParameter('api_platform.validator.serialize_payload_fields', $config['validator']['serialize_payload_fields']);
    }
}
