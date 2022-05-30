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

namespace ApiPlatform\Symfony\Bundle\DependencyInjection;

use ApiPlatform\Api\FilterInterface;
use ApiPlatform\Api\UrlGeneratorInterface;
use ApiPlatform\Doctrine\Odm\Extension\AggregationCollectionExtensionInterface;
use ApiPlatform\Doctrine\Odm\Extension\AggregationItemExtensionInterface;
use ApiPlatform\Doctrine\Odm\Filter\AbstractFilter as DoctrineMongoDbOdmAbstractFilter;
use ApiPlatform\Doctrine\Orm\Extension\EagerLoadingExtension;
use ApiPlatform\Doctrine\Orm\Extension\FilterEagerLoadingExtension;
use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface as DoctrineQueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter as DoctrineOrmAbstractFilter;
use ApiPlatform\Elasticsearch\Extension\RequestBodySearchCollectionExtensionInterface;
use ApiPlatform\GraphQl\Error\ErrorHandlerInterface;
use ApiPlatform\GraphQl\Resolver\MutationResolverInterface;
use ApiPlatform\GraphQl\Resolver\QueryCollectionResolverInterface;
use ApiPlatform\GraphQl\Resolver\QueryItemResolverInterface;
use ApiPlatform\GraphQl\Type\Definition\TypeInterface as GraphQlTypeInterface;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaRestrictionMetadataInterface;
use ApiPlatform\Symfony\Validator\ValidationGroupsGeneratorInterface;
use phpDocumentor\Reflection\DocBlockFactoryInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Resource\DirectoryResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpClient\ScopingHttpClient;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Mercure\Discovery;
use Symfony\Component\Mercure\HubRegistry;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Uid\AbstractUid;
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
    public function prepend(ContainerBuilder $container): void
    {
        if (isset($container->getExtensions()['framework'])) {
            $container->prependExtensionConfig('framework', [
                'serializer' => [
                    'enabled' => true,
                ],
            ]);
            $container->prependExtensionConfig('framework', [
                'property_info' => [
                    'enabled' => true,
                ],
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $formats = $this->getFormats($config['formats']);
        $patchFormats = $this->getFormats($config['patch_formats']);
        $errorFormats = $this->getFormats($config['error_formats']);

        // Backward Compatibility layer
        if (isset($formats['jsonapi']) && !isset($patchFormats['jsonapi'])) {
            $patchFormats['jsonapi'] = ['application/vnd.api+json'];
        }

        $this->registerCommonConfiguration($container, $config, $loader, $formats, $patchFormats, $errorFormats);
        $this->registerMetadataConfiguration($container, $config, $loader);
        $this->registerOAuthConfiguration($container, $config);
        $this->registerOpenApiConfiguration($container, $config, $loader);
        $this->registerSwaggerConfiguration($container, $config, $loader);
        $this->registerJsonApiConfiguration($formats, $loader);
        $this->registerJsonLdHydraConfiguration($container, $formats, $loader, $config);
        $this->registerJsonHalConfiguration($formats, $loader, $config);
        $this->registerJsonProblemConfiguration($errorFormats, $loader);
        $this->registerGraphQlConfiguration($container, $config, $loader);
        $this->registerCacheConfiguration($container);
        $this->registerDoctrineOrmConfiguration($container, $config, $loader);
        $this->registerDoctrineMongoDbOdmConfiguration($container, $config, $loader);
        $this->registerHttpCacheConfiguration($container, $config, $loader);
        $this->registerValidatorConfiguration($container, $config, $loader);
        $this->registerDataCollectorConfiguration($container, $config, $loader);
        $this->registerMercureConfiguration($container, $config, $loader);
        $this->registerMessengerConfiguration($container, $config, $loader);
        $this->registerElasticsearchConfiguration($container, $config, $loader);
        $this->registerDataTransformerConfiguration($container);
        $this->registerSecurityConfiguration($container, $loader, $config);
        $this->registerMakerConfiguration($container, $config, $loader);
        $this->registerArgumentResolverConfiguration($container, $loader, $config);

        $container->registerForAutoconfiguration(FilterInterface::class)
            ->addTag('api_platform.filter');
        $container->registerForAutoconfiguration(ProviderInterface::class)
            ->addTag('api_platform.state_provider');
        $container->registerForAutoconfiguration(ProcessorInterface::class)
            ->addTag('api_platform.state_processor');
    }

    private function registerCommonConfiguration(ContainerBuilder $container, array $config, XmlFileLoader $loader, array $formats, array $patchFormats, array $errorFormats): void
    {
        $loader->load('symfony/events.xml');
        $loader->load('api.xml');
        $loader->load('state.xml');
        $loader->load('filter.xml');

        if (class_exists(Uuid::class)) {
            $loader->load('ramsey_uuid.xml');
        }

        if (class_exists(AbstractUid::class)) {
            $loader->load('symfony/uid.xml');
        }

        $container->setParameter('api_platform.enable_entrypoint', $config['enable_entrypoint']);
        $container->setParameter('api_platform.enable_docs', $config['enable_docs']);
        $container->setParameter('api_platform.title', $config['title']);
        $container->setParameter('api_platform.description', $config['description']);
        $container->setParameter('api_platform.version', $config['version']);
        $container->setParameter('api_platform.show_webby', $config['show_webby']);
        $container->setParameter('api_platform.url_generation_strategy', $config['defaults']['url_generation_strategy'] ?? UrlGeneratorInterface::ABS_PATH);
        $container->setParameter('api_platform.exception_to_status', $config['exception_to_status']);
        $container->setParameter('api_platform.formats', $formats);
        $container->setParameter('api_platform.patch_formats', $patchFormats);
        $container->setParameter('api_platform.error_formats', $errorFormats);
        // TODO: to remove in 3.0
        $container->setParameter('api_platform.allow_plain_identifiers', $config['allow_plain_identifiers']);
        $container->setParameter('api_platform.eager_loading.enabled', $this->isConfigEnabled($container, $config['eager_loading']));
        $container->setParameter('api_platform.eager_loading.max_joins', $config['eager_loading']['max_joins']);
        $container->setParameter('api_platform.eager_loading.fetch_partial', $config['eager_loading']['fetch_partial']);
        $container->setParameter('api_platform.eager_loading.force_eager', $config['eager_loading']['force_eager']);
        $container->setParameter('api_platform.collection.exists_parameter_name', $config['collection']['exists_parameter_name']);
        $container->setParameter('api_platform.collection.order', $config['collection']['order']);
        $container->setParameter('api_platform.collection.order_parameter_name', $config['collection']['order_parameter_name']);
        $container->setParameter('api_platform.collection.order_nulls_comparison', $config['collection']['order_nulls_comparison']);
        $container->setParameter('api_platform.collection.pagination.enabled', $config['defaults']['pagination_enabled'] ?? $this->isConfigEnabled($container, $config['collection']['pagination']));
        $container->setParameter('api_platform.collection.pagination.partial', $config['defaults']['pagination_partial'] ?? $config['collection']['pagination']['partial']);
        $container->setParameter('api_platform.collection.pagination.client_enabled', $config['defaults']['pagination_client_enabled'] ?? $config['collection']['pagination']['client_enabled']);
        $container->setParameter('api_platform.collection.pagination.client_items_per_page', $config['defaults']['pagination_client_items_per_page'] ?? $config['collection']['pagination']['client_items_per_page']);
        $container->setParameter('api_platform.collection.pagination.client_partial', $config['defaults']['pagination_client_partial'] ?? $config['collection']['pagination']['client_partial']);
        $container->setParameter('api_platform.collection.pagination.items_per_page', $config['defaults']['pagination_items_per_page'] ?? $config['collection']['pagination']['items_per_page']);
        $container->setParameter('api_platform.collection.pagination.maximum_items_per_page', $config['defaults']['pagination_maximum_items_per_page'] ?? $config['collection']['pagination']['maximum_items_per_page']);
        $container->setParameter('api_platform.collection.pagination.page_parameter_name', $config['defaults']['pagination_page_parameter_name'] ?? $config['collection']['pagination']['page_parameter_name']);
        $container->setParameter('api_platform.collection.pagination.enabled_parameter_name', $config['defaults']['pagination_enabled_parameter_name'] ?? $config['collection']['pagination']['enabled_parameter_name']);
        $container->setParameter('api_platform.collection.pagination.items_per_page_parameter_name', $config['defaults']['pagination_items_per_page_parameter_name'] ?? $config['collection']['pagination']['items_per_page_parameter_name']);
        $container->setParameter('api_platform.collection.pagination.partial_parameter_name', $config['defaults']['pagination_partial_parameter_name'] ?? $config['collection']['pagination']['partial_parameter_name']);
        $container->setParameter('api_platform.collection.pagination', $this->getPaginationDefaults($config['defaults'] ?? [], $config['collection']['pagination']));
        $container->setParameter('api_platform.http_cache.etag', $config['defaults']['cache_headers']['etag'] ?? $config['http_cache']['etag']);
        $container->setParameter('api_platform.http_cache.max_age', $config['defaults']['cache_headers']['max_age'] ?? $config['http_cache']['max_age']);
        $container->setParameter('api_platform.http_cache.shared_max_age', $config['defaults']['cache_headers']['shared_max_age'] ?? $config['http_cache']['shared_max_age']);
        $container->setParameter('api_platform.http_cache.vary', $config['defaults']['cache_headers']['vary'] ?? $config['http_cache']['vary']);
        $container->setParameter('api_platform.http_cache.public', $config['defaults']['cache_headers']['public'] ?? $config['http_cache']['public']);
        $container->setParameter('api_platform.http_cache.invalidation.max_header_length', $config['defaults']['cache_headers']['invalidation']['max_header_length'] ?? $config['http_cache']['invalidation']['max_header_length']);
        $container->setParameter('api_platform.http_cache.invalidation.xkey.glue', $config['defaults']['cache_headers']['invalidation']['xkey']['glue'] ?? $config['http_cache']['invalidation']['xkey']['glue']);

        $container->setAlias('api_platform.operation_path_resolver.default', $config['default_operation_path_resolver']);
        $container->setAlias('api_platform.path_segment_name_generator', $config['path_segment_name_generator']);

        if ($config['name_converter']) {
            $container->setAlias('api_platform.name_converter', $config['name_converter']);
        }
        $container->setParameter('api_platform.asset_package', $config['asset_package']);
        $container->setParameter('api_platform.defaults', $this->normalizeDefaults($config['defaults'] ?? [], $config['metadata_backward_compatibility_layer'] ?? false));
    }

    /**
     * This method will be removed in 3.0 when "defaults" will be the regular configuration path for the pagination.
     */
    private function getPaginationDefaults(array $defaults, array $collectionPaginationConfiguration): array
    {
        $paginationOptions = [];

        foreach ($defaults as $key => $value) {
            if (0 !== strpos($key, 'pagination_')) {
                continue;
            }

            $paginationOptions[str_replace('pagination_', '', $key)] = $value;
        }

        return array_merge($collectionPaginationConfiguration, $paginationOptions);
    }

    private function normalizeDefaults(array $defaults, bool $compatibility = false): array
    {
        $normalizedDefaults = ['extra_properties' => $defaults['extra_properties'] ?? []];
        unset($defaults['extra_properties']);

        $rc = new \ReflectionClass(ApiResource::class);
        $publicProperties = [];
        foreach ($rc->getConstructor()->getParameters() as $param) {
            $publicProperties[$param->getName()] = true;
        }

        $nameConverter = new CamelCaseToSnakeCaseNameConverter();
        foreach ($defaults as $option => $value) {
            if (isset($publicProperties[$nameConverter->denormalize($option)])) {
                $normalizedDefaults[$option] = $value;

                continue;
            }

            $normalizedDefaults['extra_properties'][$option] = $value;
        }

        return $normalizedDefaults;
    }

    private function registerMetadataConfiguration(ContainerBuilder $container, array $config, XmlFileLoader $loader): void
    {
        [$xmlResources, $yamlResources] = $this->getResourcesToWatch($container, $config);

        $loader->load('metadata/resource_name.xml');
        $loader->load('metadata/property_name.xml');

        if (!empty($config['resource_class_directories'])) {
            $container->setParameter('api_platform.resource_class_directories', array_merge(
                $config['resource_class_directories'], $container->getParameter('api_platform.resource_class_directories')
            ));
        }

        // V3 metadata
        $loader->load('metadata/xml.xml');
        $loader->load('metadata/links.xml');
        $loader->load('metadata/property.xml');
        $loader->load('metadata/resource.xml');

        $container->getDefinition('api_platform.metadata.resource_extractor.xml')->replaceArgument(0, $xmlResources);
        $container->getDefinition('api_platform.metadata.property_extractor.xml')->replaceArgument(0, $xmlResources);

        if (interface_exists(DocBlockFactoryInterface::class)) {
            $loader->load('metadata/php_doc.xml');
        }

        if (class_exists(Yaml::class)) {
            $loader->load('metadata/yaml.xml');
            $container->getDefinition('api_platform.metadata.resource_extractor.yaml')->replaceArgument(0, $yamlResources);
            $container->getDefinition('api_platform.metadata.property_extractor.yaml')->replaceArgument(0, $yamlResources);
        }
    }

    private function getBundlesResourcesPaths(ContainerBuilder $container, array $config): array
    {
        $bundlesResourcesPaths = [];

        foreach ($container->getParameter('kernel.bundles_metadata') as $bundle) {
            $paths = [];
            $dirname = $bundle['path'];
            foreach (['.yaml', '.yml', '.xml', ''] as $extension) {
                $paths[] = "$dirname/Resources/config/api_resources$extension";
            }
            if ($this->isConfigEnabled($container, $config['doctrine'])) {
                $paths[] = "$dirname/Entity";
            }
            if ($this->isConfigEnabled($container, $config['doctrine_mongodb_odm'])) {
                $paths[] = "$dirname/Document";
            }

            foreach ($paths as $path) {
                if ($container->fileExists($path, false)) {
                    $bundlesResourcesPaths[] = $path;
                }
            }
        }

        return $bundlesResourcesPaths;
    }

    private function getResourcesToWatch(ContainerBuilder $container, array $config): array
    {
        $paths = array_unique(array_merge($this->getBundlesResourcesPaths($container, $config), $config['mapping']['paths']));

        // Flex structure (only if nothing specified)
        $projectDir = $container->getParameter('kernel.project_dir');
        if (!$paths && is_dir($dir = "$projectDir/config/api_platform")) {
            $paths = [$dir];
        }

        $resources = ['yml' => [], 'xml' => [], 'dir' => []];

        foreach ($paths as $path) {
            if (is_dir($path)) {
                foreach (Finder::create()->followLinks()->files()->in($path)->name('/\.(xml|ya?ml)$/')->sortByName() as $file) {
                    $resources['yaml' === ($extension = $file->getExtension()) ? 'yml' : $extension][] = $file->getRealPath();
                }

                $resources['dir'][] = $path;
                $container->addResource(new DirectoryResource($path, '/\.(xml|ya?ml|php)$/'));

                continue;
            }

            if ($container->fileExists($path, false)) {
                if (!preg_match('/\.(xml|ya?ml)$/', $path, $matches)) {
                    throw new RuntimeException(sprintf('Unsupported mapping type in "%s", supported types are XML & YAML.', $path));
                }

                $resources['yaml' === $matches[1] ? 'yml' : $matches[1]][] = $path;

                continue;
            }

            throw new RuntimeException(sprintf('Could not open file or directory "%s".', $path));
        }

        $container->setParameter('api_platform.resource_class_directories', $resources['dir']);

        return [$resources['xml'], $resources['yml']];
    }

    private function registerOAuthConfiguration(ContainerBuilder $container, array $config): void
    {
        if (!$config['oauth']) {
            return;
        }

        $container->setParameter('api_platform.oauth.enabled', $this->isConfigEnabled($container, $config['oauth']));
        $container->setParameter('api_platform.oauth.clientId', $config['oauth']['clientId']);
        $container->setParameter('api_platform.oauth.clientSecret', $config['oauth']['clientSecret']);
        $container->setParameter('api_platform.oauth.type', $config['oauth']['type']);
        $container->setParameter('api_platform.oauth.flow', $config['oauth']['flow']);
        $container->setParameter('api_platform.oauth.tokenUrl', $config['oauth']['tokenUrl']);
        $container->setParameter('api_platform.oauth.authorizationUrl', $config['oauth']['authorizationUrl']);
        $container->setParameter('api_platform.oauth.refreshUrl', $config['oauth']['refreshUrl']);
        $container->setParameter('api_platform.oauth.scopes', $config['oauth']['scopes']);
        $container->setParameter('api_platform.oauth.pkce', $config['oauth']['pkce']);

        if ($container->hasDefinition('api_platform.swagger.action.ui')) {
            $container->getDefinition('api_platform.swagger.action.ui')->setArgument(27, $config['oauth']['pkce']);
        }
        if ($container->hasDefinition('api_platform.swagger_ui.action')) {
            $container->getDefinition('api_platform.swagger_ui.action')->setArgument(10, $config['oauth']['pkce']);
        }
    }

    /**
     * Registers the Swagger, ReDoc and Swagger UI configuration.
     */
    private function registerSwaggerConfiguration(ContainerBuilder $container, array $config, XmlFileLoader $loader): void
    {
        $container->setParameter('api_platform.swagger.versions', $config['swagger']['versions']);

        if (!$config['enable_swagger'] && $config['enable_swagger_ui']) {
            throw new RuntimeException('You can not enable the Swagger UI without enabling Swagger, fix this by enabling swagger via the configuration "enable_swagger: true".');
        }

        if (!$config['enable_swagger']) {
            return;
        }

        $loader->load('openapi.xml');
        $loader->load('swagger_ui.xml');

        if (!$config['enable_swagger_ui'] && !$config['enable_re_doc']) {
            // Remove the listener but keep the controller to allow customizing the path of the UI
            $container->removeDefinition('api_platform.swagger.listener.ui');
        }

        $container->setParameter('api_platform.enable_swagger_ui', $config['enable_swagger_ui']);
        $container->setParameter('api_platform.enable_re_doc', $config['enable_re_doc']);
        $container->setParameter('api_platform.swagger.api_keys', $config['swagger']['api_keys']);
        if ($config['openapi']['swagger_ui_extra_configuration'] && $config['swagger']['swagger_ui_extra_configuration']) {
            throw new RuntimeException('You can not set "swagger_ui_extra_configuration" twice - in "openapi" and "swagger" section.');
        }
        $container->setParameter('api_platform.swagger_ui.extra_configuration', $config['openapi']['swagger_ui_extra_configuration'] ?: $config['swagger']['swagger_ui_extra_configuration']);

        // for swagger 2 support
        $loader->load('openapi.xml');
        $loader->load('swagger_ui.xml');
    }

    private function registerJsonApiConfiguration(array $formats, XmlFileLoader $loader): void
    {
        if (!isset($formats['jsonapi'])) {
            return;
        }

        $loader->load('jsonapi.xml');
    }

    private function registerJsonLdHydraConfiguration(ContainerBuilder $container, array $formats, XmlFileLoader $loader, array $config): void
    {
        if (!isset($formats['jsonld'])) {
            return;
        }

        $loader->load('jsonld.xml');
        $loader->load('hydra.xml');

        if ($config['metadata_backward_compatibility_layer']) {
            $loader->load('legacy/hydra.xml');
        } else {
            $loader->load('v3/hydra.xml');
        }

        if (!$container->has('api_platform.json_schema.schema_factory')) {
            $container->removeDefinition('api_platform.hydra.json_schema.schema_factory');
        }

        if (!$config['enable_docs']) {
            $container->removeDefinition('api_platform.hydra.listener.response.add_link_header');
        }
    }

    private function registerJsonHalConfiguration(array $formats, XmlFileLoader $loader, array $config): void
    {
        if (!isset($formats['jsonhal'])) {
            return;
        }

        $loader->load('hal.xml');

        if ($config['metadata_backward_compatibility_layer']) {
            $loader->load('legacy/hal.xml');
        } else {
            $loader->load('v3/hal.xml');
        }
    }

    private function registerJsonProblemConfiguration(array $errorFormats, XmlFileLoader $loader): void
    {
        if (!isset($errorFormats['jsonproblem'])) {
            return;
        }

        $loader->load('problem.xml');
    }

    private function registerGraphQlConfiguration(ContainerBuilder $container, array $config, XmlFileLoader $loader): void
    {
        $enabled = $this->isConfigEnabled($container, $config['graphql']);

        $container->setParameter('api_platform.graphql.enabled', $enabled);
        $container->setParameter('api_platform.graphql.graphiql.enabled', $enabled && $this->isConfigEnabled($container, $config['graphql']['graphiql']));
        $container->setParameter('api_platform.graphql.graphql_playground.enabled', $enabled && $this->isConfigEnabled($container, $config['graphql']['graphql_playground']));
        $container->setParameter('api_platform.graphql.collection.pagination', $config['graphql']['collection']['pagination']);

        if (!$enabled) {
            return;
        }

        $container->setParameter('api_platform.graphql.default_ide', $config['graphql']['default_ide']);
        $container->setParameter('api_platform.graphql.nesting_separator', $config['graphql']['nesting_separator']);

        $loader->load('graphql.xml');

        $container->registerForAutoconfiguration(QueryItemResolverInterface::class)
            ->addTag('api_platform.graphql.query_resolver');
        $container->registerForAutoconfiguration(QueryCollectionResolverInterface::class)
            ->addTag('api_platform.graphql.query_resolver');
        $container->registerForAutoconfiguration(MutationResolverInterface::class)
            ->addTag('api_platform.graphql.mutation_resolver');
        $container->registerForAutoconfiguration(GraphQlTypeInterface::class)
            ->addTag('api_platform.graphql.type');
        $container->registerForAutoconfiguration(ErrorHandlerInterface::class)
            ->addTag('api_platform.graphql.error_handler');
    }

    private function registerCacheConfiguration(ContainerBuilder $container): void
    {
        if (!$container->hasParameter('kernel.debug') || !$container->getParameter('kernel.debug')) {
            $container->removeDefinition('api_platform.cache_warmer.cache_pool_clearer');
        }
    }

    private function registerDoctrineOrmConfiguration(ContainerBuilder $container, array $config, XmlFileLoader $loader): void
    {
        if (!$this->isConfigEnabled($container, $config['doctrine'])) {
            return;
        }

        $container->registerForAutoconfiguration(QueryItemExtensionInterface::class)
            ->addTag('api_platform.doctrine.orm.query_extension.item');
        $container->registerForAutoconfiguration(DoctrineQueryCollectionExtensionInterface::class)
            ->addTag('api_platform.doctrine.orm.query_extension.collection');
        $container->registerForAutoconfiguration(DoctrineOrmAbstractFilter::class)
            ->setBindings(['$requestStack' => null]);

        $loader->load('doctrine_orm.xml');

        if ($this->isConfigEnabled($container, $config['eager_loading'])) {
            return;
        }

        $container->removeAlias(EagerLoadingExtension::class);
        $container->removeDefinition('api_platform.doctrine.orm.query_extension.eager_loading');
        $container->removeAlias(FilterEagerLoadingExtension::class);
        $container->removeDefinition('api_platform.doctrine.orm.query_extension.filter_eager_loading');
    }

    private function registerDoctrineMongoDbOdmConfiguration(ContainerBuilder $container, array $config, XmlFileLoader $loader): void
    {
        if (!$this->isConfigEnabled($container, $config['doctrine_mongodb_odm'])) {
            return;
        }

        $container->registerForAutoconfiguration(AggregationItemExtensionInterface::class)
            ->addTag('api_platform.doctrine_mongodb.odm.aggregation_extension.item');
        $container->registerForAutoconfiguration(AggregationCollectionExtensionInterface::class)
            ->addTag('api_platform.doctrine_mongodb.odm.aggregation_extension.collection');
        $container->registerForAutoconfiguration(DoctrineMongoDbOdmAbstractFilter::class)
            ->setBindings(['$managerRegistry' => new Reference('doctrine_mongodb')]);

        $loader->load('doctrine_mongodb_odm.xml');
    }

    private function registerHttpCacheConfiguration(ContainerBuilder $container, array $config, XmlFileLoader $loader): void
    {
        $loader->load('http_cache.xml');

        if (!$this->isConfigEnabled($container, $config['http_cache']['invalidation'])) {
            return;
        }

        if ($this->isConfigEnabled($container, $config['doctrine'])) {
            $loader->load('doctrine_orm_http_cache_purger.xml');
        }

        $loader->load('http_cache_purger.xml');

        $definitions = [];
        foreach ($config['http_cache']['invalidation']['varnish_urls'] as $key => $url) {
            $definition = new Definition(ScopingHttpClient::class, [new Reference('http_client'), $url, ['base_uri' => $url] + $config['http_cache']['invalidation']['request_options']]);
            $definition->setFactory([ScopingHttpClient::class, 'forBaseUri']);

            $definitions[] = $definition;
        }

        foreach (['api_platform.http_cache.purger.varnish.ban', 'api_platform.http_cache.purger.varnish.xkey'] as $serviceName) {
            $container->findDefinition($serviceName)->setArguments([
                $definitions,
                $config['http_cache']['invalidation']['max_header_length'],
            ]);
        }

        $serviceName = $config['http_cache']['invalidation']['purger'];
        $container->setAlias('api_platform.http_cache.purger', $serviceName);
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

    private function registerValidatorConfiguration(ContainerBuilder $container, array $config, XmlFileLoader $loader): void
    {
        if (interface_exists(ValidatorInterface::class)) {
            $loader->load('metadata/validator.xml');
            $loader->load('symfony/validator.xml');

            $container->registerForAutoconfiguration(ValidationGroupsGeneratorInterface::class)
                ->addTag('api_platform.validation_groups_generator')
                ->setPublic(true); // this line should be removed in 3.0
            $container->registerForAutoconfiguration(PropertySchemaRestrictionMetadataInterface::class)
                ->addTag('api_platform.metadata.property_schema_restriction');
        }

        if (!$config['validator']) {
            return;
        }

        $container->setParameter('api_platform.validator.serialize_payload_fields', $config['validator']['serialize_payload_fields']);
        $container->setParameter('api_platform.validator.query_parameter_validation', $config['validator']['query_parameter_validation']);

        if (!$config['validator']['query_parameter_validation']) {
            $container->removeDefinition('api_platform.listener.view.validate_query_parameters');
            $container->removeDefinition('api_platform.validator.query_parameter_validator');
        }
    }

    private function registerDataCollectorConfiguration(ContainerBuilder $container, array $config, XmlFileLoader $loader): void
    {
        if (!$config['enable_profiler']) {
            return;
        }

        $loader->load('data_collector.xml');

        if ($container->hasParameter('kernel.debug') && $container->getParameter('kernel.debug')) {
            $loader->load('debug.xml');
        }
    }

    private function registerMercureConfiguration(ContainerBuilder $container, array $config, XmlFileLoader $loader): void
    {
        if (!$this->isConfigEnabled($container, $config['mercure'])) {
            return;
        }

        $loader->load('mercure.xml');
        if (!class_exists(Discovery::class)) {
            $container->getDefinition('api_platform.mercure.listener.response.add_link_header')->setArgument(1, $config['mercure']['hub_url'] ?? '%mercure.default_hub%');
        }

        if ($this->isConfigEnabled($container, $config['doctrine'])) {
            $loader->load('doctrine_orm_mercure_publisher.xml');

            if (class_exists(HubRegistry::class)) {
                $container->getDefinition('api_platform.doctrine.orm.listener.mercure.publish')->setArgument(6, new Reference(HubRegistry::class));
            }
        }
        if ($this->isConfigEnabled($container, $config['doctrine_mongodb_odm'])) {
            $loader->load('doctrine_odm_mercure_publisher.xml');
            if (class_exists(HubRegistry::class)) {
                $container->getDefinition('api_platform.doctrine_mongodb.odm.listener.mercure.publish')->setArgument(6, new Reference(HubRegistry::class));
            }
        }

        if ($this->isConfigEnabled($container, $config['graphql'])) {
            $loader->load('graphql_mercure.xml');
            if (class_exists(HubRegistry::class)) {
                $container->getDefinition('api_platform.graphql.subscription.mercure_iri_generator')->addArgument(new Reference(HubRegistry::class));
            } else {
                $container->getDefinition('api_platform.graphql.subscription.mercure_iri_generator')->addArgument($config['mercure']['hub_url'] ?? '%mercure.default_hub%');
            }
        }
    }

    private function registerMessengerConfiguration(ContainerBuilder $container, array $config, XmlFileLoader $loader): void
    {
        if (!$this->isConfigEnabled($container, $config['messenger'])) {
            return;
        }

        $loader->load('messenger.xml');
    }

    private function registerElasticsearchConfiguration(ContainerBuilder $container, array $config, XmlFileLoader $loader): void
    {
        $enabled = $this->isConfigEnabled($container, $config['elasticsearch']);

        $container->setParameter('api_platform.elasticsearch.enabled', $enabled);

        if (!$enabled) {
            return;
        }

        $loader->load('elasticsearch.xml');

        $container->registerForAutoconfiguration(RequestBodySearchCollectionExtensionInterface::class)
            ->addTag('api_platform.elasticsearch.request_body_search_extension.collection');

        $container->setParameter('api_platform.elasticsearch.hosts', $config['elasticsearch']['hosts']);
        $container->setParameter('api_platform.elasticsearch.mapping', $config['elasticsearch']['mapping']);
    }

    private function registerDataTransformerConfiguration(ContainerBuilder $container): void
    {
        $container->registerForAutoconfiguration(DataTransformerInterface::class)
            ->addTag('api_platform.data_transformer');

        $container->registerForAutoconfiguration(DataTransformerInitializerInterface::class)
            ->addTag('api_platform.data_transformer');
    }

    private function registerSecurityConfiguration(ContainerBuilder $container, XmlFileLoader $loader, array $config): void
    {
        /** @var string[] $bundles */
        $bundles = $container->getParameter('kernel.bundles');

        if (!isset($bundles['SecurityBundle'])) {
            return;
        }

        $loader->load('security.xml');
    }

    private function registerOpenApiConfiguration(ContainerBuilder $container, array $config, XmlFileLoader $loader): void
    {
        $container->setParameter('api_platform.openapi.termsOfService', $config['openapi']['termsOfService']);
        $container->setParameter('api_platform.openapi.contact.name', $config['openapi']['contact']['name']);
        $container->setParameter('api_platform.openapi.contact.url', $config['openapi']['contact']['url']);
        $container->setParameter('api_platform.openapi.contact.email', $config['openapi']['contact']['email']);
        $container->setParameter('api_platform.openapi.license.name', $config['openapi']['license']['name']);
        $container->setParameter('api_platform.openapi.license.url', $config['openapi']['license']['url']);

        $loader->load('json_schema.xml');

        if ($config['metadata_backward_compatibility_layer']) {
            $loader->load('legacy/json_schema.xml');
        } else {
            $loader->load('v3/json_schema.xml');
        }
    }

    private function registerMakerConfiguration(ContainerBuilder $container, array $config, XmlFileLoader $loader): void
    {
        if (!$this->isConfigEnabled($container, $config['maker'])) {
            return;
        }

        $loader->load('maker.xml');
    }

    private function registerArgumentResolverConfiguration(ContainerBuilder $container, XmlFileLoader $loader, array $config): void
    {
        $loader->load('argument_resolver.xml');
    }
}
