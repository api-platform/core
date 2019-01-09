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
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface as DoctrineQueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\Extension\FullBodySearchCollectionExtensionInterface as ElasticSearchQueryCollectionExtensionInterface;
use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use ApiPlatform\Core\DataProvider\CollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\SubresourceDataProviderInterface;
use ApiPlatform\Core\Exception\RuntimeException;
use Doctrine\Common\Annotations\Annotation;
use Doctrine\ORM\Version;
use Elasticsearch\Client;
use phpDocumentor\Reflection\DocBlockFactoryInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Resource\DirectoryResource;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Messenger\MessageBusInterface;
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

        $serializerConfig = $propertyInfoConfig = null;

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
        $container->registerForAutoconfiguration(FilterInterface::class)
            ->addTag('api_platform.filter');

        if (interface_exists(ValidatorInterface::class)) {
            $loader->load('validator.xml');
        }

        $bundles = $container->getParameter('kernel.bundles');
        if (isset($bundles['SecurityBundle'])) {
            if (class_exists(ExpressionLanguage::class)) {
                $loader->load('security_expression_language.xml');
            }
            $loader->load('security.xml');
        }

        if (class_exists(Uuid::class)) {
            $loader->load('ramsey_uuid.xml');
        }

        $useDoctrine = isset($bundles['DoctrineBundle']) && class_exists(Version::class);

        $this->registerMetadataConfiguration($container, $config, $loader);
        $this->registerOAuthConfiguration($container, $config);
        $this->registerApiKeysConfiguration($container, $config);
        $this->registerSwaggerConfiguration($container, $config, $loader);
        $this->registerJsonApiConfiguration($formats, $loader);
        $this->registerJsonLdConfiguration($container, $formats, $loader, $config['enable_docs']);
        $this->registerJsonHalConfiguration($formats, $loader);
        $this->registerJsonProblemConfiguration($errorFormats, $loader);
        $this->registerGraphqlConfiguration($container, $config, $loader);
        $this->registerBundlesConfiguration($bundles, $config, $loader, $useDoctrine);
        $this->registerCacheConfiguration($container);
        $this->registerDoctrineExtensionConfiguration($container, $config, $useDoctrine);
        $this->registerHttpCacheConfiguration($container, $config, $loader, $useDoctrine);
        $this->registerValidatorConfiguration($container, $config);
        $this->registerDataCollectorConfiguration($container, $config, $loader);
        $this->registerMercureConfiguration($container, $config, $loader, $useDoctrine);
        $this->registerElasticsearchConfiguration($container, $config, $loader);

        if (interface_exists(MessageBusInterface::class)) {
            $loader->load('messenger.xml');
        }
    }

    /**
     * Handles configuration.
     */
    private function handleConfig(ContainerBuilder $container, array $config, array $formats, array $errorFormats)
    {
        $container->setParameter('api_platform.enable_entrypoint', $config['enable_entrypoint']);
        $container->setParameter('api_platform.enable_docs', $config['enable_docs']);
        $container->setParameter('api_platform.title', $config['title']);
        $container->setParameter('api_platform.description', $config['description']);
        $container->setParameter('api_platform.version', $config['version']);
        $container->setParameter('api_platform.show_webby', $config['show_webby']);
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
        $container->setParameter('api_platform.collection.pagination', $config['collection']['pagination']);
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
     */
    private function registerMetadataConfiguration(ContainerBuilder $container, array $config, XmlFileLoader $loader)
    {
        $loader->load('metadata/metadata.xml');
        $loader->load('metadata/xml.xml');

        list($xmlResources, $yamlResources) = $this->getResourcesToWatch($container, $config['mapping']['paths']);

        if (!empty($config['resource_class_directories'])) {
            $container->setParameter('api_platform.resource_class_directories', array_merge(
                $config['resource_class_directories'], $container->getParameter('api_platform.resource_class_directories')
            ));
        }

        $container->getDefinition('api_platform.metadata.extractor.xml')->replaceArgument(0, $xmlResources);

        if (class_exists(Annotation::class)) {
            $loader->load('metadata/annotation.xml');
        }

        if (class_exists(Yaml::class)) {
            $loader->load('metadata/yaml.xml');
            $container->getDefinition('api_platform.metadata.extractor.yaml')->replaceArgument(0, $yamlResources);
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
     */
    private function registerOAuthConfiguration(ContainerBuilder $container, array $config)
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
     */
    private function registerApiKeysConfiguration(ContainerBuilder $container, array $config)
    {
        $container->setParameter('api_platform.swagger.api_keys', $config['swagger']['api_keys']);
    }

    /**
     * Registers the Swagger, ReDoc and Swagger UI configuration.
     */
    private function registerSwaggerConfiguration(ContainerBuilder $container, array $config, XmlFileLoader $loader)
    {
        if (!$config['enable_swagger']) {
            return;
        }

        $loader->load('swagger.xml');

        if ($config['enable_swagger_ui'] || $config['enable_re_doc']) {
            $loader->load('swagger-ui.xml');
            $container->setParameter('api_platform.enable_swagger_ui', $config['enable_swagger_ui']);
            $container->setParameter('api_platform.enable_re_doc', $config['enable_re_doc']);
        }

        $container->setParameter('api_platform.enable_swagger', $config['enable_swagger']);
    }

    /**
     * Registers the JsonApi configuration.
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
     */
    private function registerJsonLdConfiguration(ContainerBuilder $container, array $formats, XmlFileLoader $loader, bool $docEnabled)
    {
        if (!isset($formats['jsonld'])) {
            return;
        }

        $loader->load('jsonld.xml');
        $loader->load('hydra.xml');

        if (!$docEnabled) {
            $container->removeDefinition('api_platform.hydra.listener.response.add_link_header');
        }
    }

    /**
     * Registers the HAL configuration.
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
     * @param string[] $bundles
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
     */
    private function registerCacheConfiguration(ContainerBuilder $container)
    {
        // Don't use system cache pool in dev
        if ($container->hasParameter('api_platform.metadata_cache') ? $container->getParameter('api_platform.metadata_cache') : !$container->getParameter('kernel.debug')) {
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
     */
    private function registerDoctrineExtensionConfiguration(ContainerBuilder $container, array $config, bool $useDoctrine)
    {
        if (!$useDoctrine) {
            return;
        }

        $container->registerForAutoconfiguration(QueryItemExtensionInterface::class)
            ->addTag('api_platform.doctrine.orm.query_extension.item');
        $container->registerForAutoconfiguration(DoctrineQueryCollectionExtensionInterface::class)
            ->addTag('api_platform.doctrine.orm.query_extension.collection');

        if ($config['eager_loading']['enabled']) {
            return;
        }

        $container->removeAlias(EagerLoadingExtension::class);
        $container->removeDefinition('api_platform.doctrine.orm.query_extension.eager_loading');
        $container->removeAlias(FilterEagerLoadingExtension::class);
        $container->removeDefinition('api_platform.doctrine.orm.query_extension.filter_eager_loading');
    }

    private function registerHttpCacheConfiguration(ContainerBuilder $container, array $config, XmlFileLoader $loader, bool $useDoctrine)
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
            $definition->addArgument(['base_uri' => $url] + $config['http_cache']['invalidation']['request_options']);

            $definitions[] = $definition;
        }

        $container->getDefinition('api_platform.http_cache.purger.varnish')->addArgument($definitions);
        $container->setAlias('api_platform.http_cache.purger', 'api_platform.http_cache.purger.varnish');
    }

    /**
     * Normalizes the format from config to the one accepted by Symfony HttpFoundation.
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
     */
    private function registerValidatorConfiguration(ContainerBuilder $container, array $config)
    {
        if (!$config['validator']) {
            return;
        }

        $container->setParameter('api_platform.validator.serialize_payload_fields', $config['validator']['serialize_payload_fields']);
    }

    /**
     * Registers the DataCollector configuration.
     */
    private function registerDataCollectorConfiguration(ContainerBuilder $container, array $config, XmlFileLoader $loader)
    {
        if (!$config['enable_profiler']) {
            return;
        }

        $loader->load('data_collector.xml');

        if ($container->hasParameter('kernel.debug') && $container->getParameter('kernel.debug')) {
            $loader->load('debug.xml');
        }
    }

    private function registerMercureConfiguration(ContainerBuilder $container, array $config, XmlFileLoader $loader, bool $useDoctrine)
    {
        if (!$config['mercure']['enabled']) {
            return;
        }

        $loader->load('mercure.xml');
        $container->getDefinition('api_platform.mercure.listener.response.add_link_header')->addArgument($config['mercure']['hub_url'] ?? '%mercure.default_hub%');

        if ($useDoctrine) {
            $loader->load('doctrine_orm_mercure_publisher.xml');
        }
    }

    private function registerElasticsearchConfiguration(ContainerBuilder $container, array $config, XmlFileLoader $loader)
    {
        $enabled = $config['elasticsearch']['enabled'] && class_exists(Client::class);

        $container->setParameter('api_platform.elasticsearch.enabled', $enabled);

        if (!$enabled) {
            return;
        }

        $loader->load('elasticsearch.xml');

        $container->registerForAutoconfiguration(ElasticSearchQueryCollectionExtensionInterface::class)
            ->addTag('api_platform.elasticsearch.query_extension.collection');

        $container->setParameter('api_platform.elasticsearch.host', $config['elasticsearch']['host']);
        $container->setParameter('api_platform.elasticsearch.mapping', $config['elasticsearch']['mapping']);
    }
}
