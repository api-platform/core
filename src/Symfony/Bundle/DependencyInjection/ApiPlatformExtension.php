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

use ApiPlatform\Doctrine\Odm\Extension\AggregationCollectionExtensionInterface;
use ApiPlatform\Doctrine\Odm\Extension\AggregationItemExtensionInterface;
use ApiPlatform\Doctrine\Odm\Filter\AbstractFilter as DoctrineMongoDbOdmAbstractFilter;
use ApiPlatform\Doctrine\Odm\State\LinksHandlerInterface as OdmLinksHandlerInterface;
use ApiPlatform\Doctrine\Orm\Extension\EagerLoadingExtension;
use ApiPlatform\Doctrine\Orm\Extension\FilterEagerLoadingExtension;
use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface as DoctrineQueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter as DoctrineOrmAbstractFilter;
use ApiPlatform\Doctrine\Orm\State\LinksHandlerInterface as OrmLinksHandlerInterface;
use ApiPlatform\Elasticsearch\Extension\RequestBodySearchCollectionExtensionInterface;
use ApiPlatform\GraphQl\Error\ErrorHandlerInterface;
use ApiPlatform\GraphQl\Resolver\MutationResolverInterface;
use ApiPlatform\GraphQl\Resolver\QueryCollectionResolverInterface;
use ApiPlatform\GraphQl\Resolver\QueryItemResolverInterface;
use ApiPlatform\GraphQl\Type\Definition\TypeInterface as GraphQlTypeInterface;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\FilterInterface;
use ApiPlatform\Metadata\UriVariableTransformerInterface;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use ApiPlatform\Metadata\Util\Inflector;
use ApiPlatform\State\ApiResource\Error;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\Symfony\GraphQl\Resolver\Factory\DataCollectorResolverFactory;
use ApiPlatform\Symfony\Validator\Exception\ValidationException;
use ApiPlatform\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaRestrictionMetadataInterface;
use ApiPlatform\Symfony\Validator\ValidationGroupsGeneratorInterface;
use Doctrine\Persistence\ManagerRegistry;
use phpDocumentor\Reflection\DocBlockFactoryInterface;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Resource\DirectoryResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpClient\ScopingHttpClient;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Uid\AbstractUid;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Yaml\Yaml;
use Twig\Environment;

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
        if (isset($container->getExtensions()['lexik_jwt_authentication'])) {
            $container->prependExtensionConfig('lexik_jwt_authentication', [
                'api_platform' => [
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

        if (!$config['formats']) {
            trigger_deprecation('api-platform/core', '3.2', 'Setting the "formats" section will be mandatory in API Platform 4.');
            $config['formats'] = [
                'jsonld' => ['mime_types' => ['application/ld+json']],
                // Note that in API Platform 4 this will be removed as it was used for documentation only and are is now present in the docsFormats
                'json' => ['mime_types' => ['application/json']], // Swagger support
            ];
        }

        $formats = $this->getFormats($config['formats']);
        $patchFormats = $this->getFormats($config['patch_formats']);
        $errorFormats = $this->getFormats($config['error_formats']);
        $docsFormats = $this->getFormats($config['docs_formats']);

        if (!isset($errorFormats['json'])) {
            $errorFormats['json'] = ['application/problem+json', 'application/json'];
        }

        if (!isset($errorFormats['jsonproblem'])) {
            $errorFormats['jsonproblem'] = ['application/problem+json'];
        }

        if ($this->isConfigEnabled($container, $config['graphql']) && !isset($formats['json'])) {
            trigger_deprecation('api-platform/core', '3.2', 'Add the "json" format to the configuration to use GraphQL.');
            $formats['json'] = ['application/json'];
        }

        // Backward Compatibility layer
        if (isset($formats['jsonapi']) && !isset($patchFormats['jsonapi'])) {
            $patchFormats['jsonapi'] = ['application/vnd.api+json'];
        }

        if (isset($docsFormats['json']) && !isset($docsFormats['jsonopenapi'])) {
            trigger_deprecation('api-platform/core', '3.2', 'The "json" format is too broad, use ["jsonopenapi" => ["application/vnd.openapi+json"]] instead.');
            $docsFormats['jsonopenapi'] = ['application/vnd.openapi+json'];
        }

        $this->registerCommonConfiguration($container, $config, $loader, $formats, $patchFormats, $errorFormats, $docsFormats);
        $this->registerMetadataConfiguration($container, $config, $loader);
        $this->registerOAuthConfiguration($container, $config);
        $this->registerOpenApiConfiguration($container, $config, $loader);
        $this->registerSwaggerConfiguration($container, $config, $loader);
        $this->registerJsonApiConfiguration($formats, $loader, $config);
        $this->registerJsonLdHydraConfiguration($container, $formats, $loader, $config);
        $this->registerJsonHalConfiguration($formats, $loader);
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
        $this->registerSecurityConfiguration($container, $config, $loader);
        $this->registerMakerConfiguration($container, $config, $loader);
        $this->registerArgumentResolverConfiguration($loader);

        $container->registerForAutoconfiguration(FilterInterface::class)
            ->addTag('api_platform.filter');
        $container->registerForAutoconfiguration(ProviderInterface::class)
            ->addTag('api_platform.state_provider');
        $container->registerForAutoconfiguration(ProcessorInterface::class)
            ->addTag('api_platform.state_processor');
        $container->registerForAutoconfiguration(UriVariableTransformerInterface::class)
            ->addTag('api_platform.uri_variables.transformer');

        if (!$container->has('api_platform.state.item_provider')) {
            $container->setAlias('api_platform.state.item_provider', 'api_platform.state_provider.object');
        }

        $this->registerInflectorConfiguration($config);
    }

    private function registerCommonConfiguration(ContainerBuilder $container, array $config, XmlFileLoader $loader, array $formats, array $patchFormats, array $errorFormats, array $docsFormats): void
    {
        $loader->load('symfony/events.xml');
        $loader->load('symfony/controller.xml');
        $loader->load('api.xml');
        $loader->load('state.xml');
        $loader->load('filter.xml');

        if (class_exists(Uuid::class)) {
            $loader->load('ramsey_uuid.xml');
        }

        if (class_exists(AbstractUid::class)) {
            $loader->load('symfony/uid.xml');
        }

        // TODO: remove in 4.x
        $container->setParameter('api_platform.event_listeners_backward_compatibility_layer', $config['event_listeners_backward_compatibility_layer']);
        $loader->load('legacy/events.xml');

        $container->setParameter('api_platform.enable_entrypoint', $config['enable_entrypoint']);
        $container->setParameter('api_platform.enable_docs', $config['enable_docs']);
        $container->setParameter('api_platform.keep_legacy_inflector', $config['keep_legacy_inflector']);
        $container->setParameter('api_platform.title', $config['title']);
        $container->setParameter('api_platform.description', $config['description']);
        $container->setParameter('api_platform.version', $config['version']);
        $container->setParameter('api_platform.show_webby', $config['show_webby']);
        $container->setParameter('api_platform.url_generation_strategy', $config['defaults']['url_generation_strategy'] ?? UrlGeneratorInterface::ABS_PATH);
        $container->setParameter('api_platform.exception_to_status', $config['exception_to_status']);
        $container->setParameter('api_platform.formats', $formats);
        $container->setParameter('api_platform.patch_formats', $patchFormats);
        $container->setParameter('api_platform.error_formats', $errorFormats);
        $container->setParameter('api_platform.docs_formats', $docsFormats);
        $container->setParameter('api_platform.eager_loading.enabled', $this->isConfigEnabled($container, $config['eager_loading']));
        $container->setParameter('api_platform.eager_loading.max_joins', $config['eager_loading']['max_joins']);
        $container->setParameter('api_platform.eager_loading.fetch_partial', $config['eager_loading']['fetch_partial']);
        $container->setParameter('api_platform.eager_loading.force_eager', $config['eager_loading']['force_eager']);
        $container->setParameter('api_platform.collection.exists_parameter_name', $config['collection']['exists_parameter_name']);
        $container->setParameter('api_platform.collection.order', $config['collection']['order']);
        $container->setParameter('api_platform.collection.order_parameter_name', $config['collection']['order_parameter_name']);
        $container->setParameter('api_platform.collection.order_nulls_comparison', $config['collection']['order_nulls_comparison']);
        $container->setParameter('api_platform.collection.pagination.enabled', $config['defaults']['pagination_enabled'] ?? true);
        $container->setParameter('api_platform.collection.pagination.partial', $config['defaults']['pagination_partial'] ?? false);
        $container->setParameter('api_platform.collection.pagination.client_enabled', $config['defaults']['pagination_client_enabled'] ?? false);
        $container->setParameter('api_platform.collection.pagination.client_items_per_page', $config['defaults']['pagination_client_items_per_page'] ?? false);
        $container->setParameter('api_platform.collection.pagination.client_partial', $config['defaults']['pagination_client_partial'] ?? false);
        $container->setParameter('api_platform.collection.pagination.items_per_page', $config['defaults']['pagination_items_per_page'] ?? 30);
        $container->setParameter('api_platform.collection.pagination.maximum_items_per_page', $config['defaults']['pagination_maximum_items_per_page'] ?? null);
        $container->setParameter('api_platform.collection.pagination.page_parameter_name', $config['defaults']['pagination_page_parameter_name'] ?? $config['collection']['pagination']['page_parameter_name']);
        $container->setParameter('api_platform.collection.pagination.enabled_parameter_name', $config['defaults']['pagination_enabled_parameter_name'] ?? $config['collection']['pagination']['enabled_parameter_name']);
        $container->setParameter('api_platform.collection.pagination.items_per_page_parameter_name', $config['defaults']['pagination_items_per_page_parameter_name'] ?? $config['collection']['pagination']['items_per_page_parameter_name']);
        $container->setParameter('api_platform.collection.pagination.partial_parameter_name', $config['defaults']['pagination_partial_parameter_name'] ?? $config['collection']['pagination']['partial_parameter_name']);
        $container->setParameter('api_platform.collection.pagination', $this->getPaginationDefaults($config['defaults'] ?? [], $config['collection']['pagination']));
        $container->setParameter('api_platform.handle_symfony_errors', $config['handle_symfony_errors'] ?? false);
        $container->setParameter('api_platform.http_cache.etag', $config['defaults']['cache_headers']['etag'] ?? true);
        $container->setParameter('api_platform.http_cache.max_age', $config['defaults']['cache_headers']['max_age'] ?? null);
        $container->setParameter('api_platform.http_cache.shared_max_age', $config['defaults']['cache_headers']['shared_max_age'] ?? null);
        $container->setParameter('api_platform.http_cache.vary', $config['defaults']['cache_headers']['vary'] ?? ['Accept']);
        $container->setParameter('api_platform.http_cache.public', $config['defaults']['cache_headers']['public'] ?? $config['http_cache']['public']);
        $container->setParameter('api_platform.http_cache.invalidation.max_header_length', $config['defaults']['cache_headers']['invalidation']['max_header_length'] ?? $config['http_cache']['invalidation']['max_header_length']);
        $container->setParameter('api_platform.http_cache.invalidation.xkey.glue', $config['defaults']['cache_headers']['invalidation']['xkey']['glue'] ?? $config['http_cache']['invalidation']['xkey']['glue']);

        $container->setAlias('api_platform.path_segment_name_generator', $config['path_segment_name_generator']);

        if ($config['name_converter']) {
            $container->setAlias('api_platform.name_converter', $config['name_converter']);
        }
        $container->setParameter('api_platform.asset_package', $config['asset_package']);
        $container->setParameter('api_platform.defaults', $this->normalizeDefaults($config['defaults'] ?? []));
        $container->setParameter('api_platform.rfc_7807_compliant_errors', $config['defaults']['extra_properties']['rfc_7807_compliant_errors'] ?? false);

        if ($container->getParameter('kernel.debug')) {
            $container->removeDefinition('api_platform.serializer.mapping.cache_class_metadata_factory');
        }
    }

    /**
     * This method will be removed in 3.0 when "defaults" will be the regular configuration path for the pagination.
     */
    private function getPaginationDefaults(array $defaults, array $collectionPaginationConfiguration): array
    {
        $paginationOptions = [];

        foreach ($defaults as $key => $value) {
            if (!str_starts_with($key, 'pagination_')) {
                continue;
            }

            $paginationOptions[str_replace('pagination_', '', $key)] = $value;
        }

        return array_merge($collectionPaginationConfiguration, $paginationOptions);
    }

    private function normalizeDefaults(array $defaults): array
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

        $container->setParameter('api_platform.class_name_resources', $this->getClassNameResources());

        $loader->load('metadata/resource_name.xml');
        $loader->load('metadata/property_name.xml');

        if (!empty($config['resource_class_directories'])) {
            $container->setParameter('api_platform.resource_class_directories', array_merge(
                $config['resource_class_directories'],
                $container->getParameter('api_platform.resource_class_directories')
            ));
        }

        // V3 metadata
        $loader->load('metadata/xml.xml');
        $loader->load('metadata/links.xml');
        $loader->load('metadata/property.xml');
        $loader->load('metadata/resource.xml');
        $loader->load('metadata/operation.xml');

        $container->getDefinition('api_platform.metadata.resource_extractor.xml')->replaceArgument(0, $xmlResources);
        $container->getDefinition('api_platform.metadata.property_extractor.xml')->replaceArgument(0, $xmlResources);

        if (class_exists(PhpDocParser::class) || interface_exists(DocBlockFactoryInterface::class)) {
            $loader->load('metadata/php_doc.xml');
        }

        if (class_exists(Yaml::class)) {
            $loader->load('metadata/yaml.xml');
            $container->getDefinition('api_platform.metadata.resource_extractor.yaml')->replaceArgument(0, $yamlResources);
            $container->getDefinition('api_platform.metadata.property_extractor.yaml')->replaceArgument(0, $yamlResources);
        }
    }

    private function getClassNameResources(): array
    {
        return [
            Error::class,
            ValidationException::class,
        ];
    }

    private function getBundlesResourcesPaths(ContainerBuilder $container, array $config): array
    {
        $bundlesResourcesPaths = [];

        foreach ($container->getParameter('kernel.bundles_metadata') as $bundle) {
            $dirname = $bundle['path'];
            $paths = [
                "$dirname/ApiResource",
                "$dirname/src/ApiResource",
            ];
            foreach (['.yaml', '.yml', '.xml', ''] as $extension) {
                $paths[] = "$dirname/Resources/config/api_resources$extension";
                $paths[] = "$dirname/config/api_resources$extension";
            }
            if ($this->isConfigEnabled($container, $config['doctrine'])) {
                $paths[] = "$dirname/Entity";
                $paths[] = "$dirname/src/Entity";
            }
            if ($this->isConfigEnabled($container, $config['doctrine_mongodb_odm'])) {
                $paths[] = "$dirname/Document";
                $paths[] = "$dirname/src/Document";
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

        if (!$config['mapping']['paths']) {
            $projectDir = $container->getParameter('kernel.project_dir');
            foreach (["$projectDir/config/api_platform", "$projectDir/src/ApiResource"] as $dir) {
                if (is_dir($dir)) {
                    $paths[] = $dir;
                }
            }

            if ($this->isConfigEnabled($container, $config['doctrine']) && is_dir($doctrinePath = "$projectDir/src/Entity")) {
                $paths[] = $doctrinePath;
            }

            if ($this->isConfigEnabled($container, $config['doctrine_mongodb_odm']) && is_dir($documentPath = "$projectDir/src/Document")) {
                $paths[] = $documentPath;
            }
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
                if (!preg_match('/\.(xml|ya?ml)$/', (string) $path, $matches)) {
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

        if ($container->hasDefinition('api_platform.swagger_ui.action')) {
            $container->getDefinition('api_platform.swagger_ui.action')->setArgument(10, $config['oauth']['pkce']);
        }
    }

    /**
     * Registers the Swagger, ReDoc and Swagger UI configuration.
     */
    private function registerSwaggerConfiguration(ContainerBuilder $container, array $config, XmlFileLoader $loader): void
    {
        foreach (array_keys($config['swagger']['api_keys']) as $keyName) {
            if (!preg_match('/^[a-zA-Z0-9._-]+$/', $keyName)) {
                trigger_deprecation('api-platform/core', '3.1', sprintf('The swagger api_keys key "%s" is not valid with OpenAPI 3.1 it should match "^[a-zA-Z0-9._-]+$"', $keyName));
            }
        }

        $container->setParameter('api_platform.swagger.versions', $config['swagger']['versions']);

        if (!$config['enable_swagger'] && $config['enable_swagger_ui']) {
            throw new RuntimeException('You can not enable the Swagger UI without enabling Swagger, fix this by enabling swagger via the configuration "enable_swagger: true".');
        }

        if (!$config['enable_swagger']) {
            return;
        }

        $loader->load('openapi.xml');
        $loader->load('swagger_ui.xml');

        $loader->load('legacy/swagger_ui.xml');

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
    }

    private function registerJsonApiConfiguration(array $formats, XmlFileLoader $loader, array $config): void
    {
        if (!isset($formats['jsonapi'])) {
            return;
        }

        $loader->load('jsonapi.xml');
        $loader->load('legacy/jsonapi.xml');
    }

    private function registerJsonLdHydraConfiguration(ContainerBuilder $container, array $formats, XmlFileLoader $loader, array $config): void
    {
        if (!isset($formats['jsonld'])) {
            return;
        }

        $loader->load('jsonld.xml');
        $loader->load('legacy/hydra.xml');
        $loader->load('hydra.xml');

        if (!$container->has('api_platform.json_schema.schema_factory')) {
            $container->removeDefinition('api_platform.hydra.json_schema.schema_factory');
        }

        if (!$config['enable_docs']) {
            $container->removeDefinition('api_platform.hydra.listener.response.add_link_header');
            $container->removeDefinition('api_platform.hydra.processor.link');
        }
    }

    private function registerJsonHalConfiguration(array $formats, XmlFileLoader $loader): void
    {
        if (!isset($formats['jsonhal'])) {
            return;
        }

        $loader->load('hal.xml');
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

        $graphqlIntrospectionEnabled = $enabled && $this->isConfigEnabled($container, $config['graphql']['introspection']);

        $graphiqlEnabled = $enabled && $this->isConfigEnabled($container, $config['graphql']['graphiql']);
        $graphqlPlayGroundEnabled = $enabled && $this->isConfigEnabled($container, $config['graphql']['graphql_playground']);
        if ($graphqlPlayGroundEnabled) {
            trigger_deprecation('api-platform/core', '3.1', 'GraphQL Playground is deprecated and will be removed in API Platform 4.0. Only GraphiQL will be available in the future. Set api_platform.graphql.graphql_playground to false in the configuration to remove this deprecation.');
        }

        $container->setParameter('api_platform.graphql.enabled', $enabled);
        $container->setParameter('api_platform.graphql.introspection.enabled', $graphqlIntrospectionEnabled);
        $container->setParameter('api_platform.graphql.graphiql.enabled', $graphiqlEnabled);
        $container->setParameter('api_platform.graphql.graphql_playground.enabled', $graphqlPlayGroundEnabled);
        $container->setParameter('api_platform.graphql.collection.pagination', $config['graphql']['collection']['pagination']);

        if (!$enabled) {
            return;
        }

        $container->setParameter('api_platform.graphql.default_ide', $config['graphql']['default_ide']);
        $container->setParameter('api_platform.graphql.nesting_separator', $config['graphql']['nesting_separator']);

        $loader->load('graphql.xml');

        // @phpstan-ignore-next-line because PHPStan uses the container of the test env cache and in test the parameter kernel.bundles always contains the key TwigBundle
        if (!class_exists(Environment::class) || !isset($container->getParameter('kernel.bundles')['TwigBundle'])) {
            if ($graphiqlEnabled || $graphqlPlayGroundEnabled) {
                throw new RuntimeException(sprintf('GraphiQL and GraphQL Playground interfaces depend on Twig. Please activate TwigBundle for the %s environnement or disable GraphiQL and GraphQL Playground.', $container->getParameter('kernel.environment')));
            }
            $container->removeDefinition('api_platform.graphql.action.graphiql');
            $container->removeDefinition('api_platform.graphql.action.graphql_playground');
        }

        $container->registerForAutoconfiguration(QueryItemResolverInterface::class)
            ->addTag('api_platform.graphql.resolver');
        $container->registerForAutoconfiguration(QueryCollectionResolverInterface::class)
            ->addTag('api_platform.graphql.resolver');
        $container->registerForAutoconfiguration(MutationResolverInterface::class)
            ->addTag('api_platform.graphql.resolver');
        $container->registerForAutoconfiguration(GraphQlTypeInterface::class)
            ->addTag('api_platform.graphql.type');
        $container->registerForAutoconfiguration(ErrorHandlerInterface::class)
            ->addTag('api_platform.graphql.error_handler');

        /* TODO: remove these in 4.x only one resolver factory is used and we're using providers/processors */
        if ($config['event_listeners_backward_compatibility_layer'] ?? true) {
            // @TODO: API Platform 3.3 trigger_deprecation('api-platform/core', '3.3', 'In API Platform 4 only one factory "api_platform.graphql.resolver.factory.item" will remain. Stages are deprecated in favor of using a provider/processor.');
            // + deprecate every service from legacy/graphql.xml
            $loader->load('legacy/graphql.xml');

            if (!$container->getParameter('kernel.debug')) {
                return;
            }

            $requestStack = new Reference('request_stack', ContainerInterface::NULL_ON_INVALID_REFERENCE);
            $collectionDataCollectorResolverFactory = (new Definition(DataCollectorResolverFactory::class))
                ->setDecoratedService('api_platform.graphql.resolver.factory.collection')
                ->setArguments([new Reference('api_platform.graphql.data_collector.resolver.factory.collection.inner'), $requestStack]);

            $itemDataCollectorResolverFactory = (new Definition(DataCollectorResolverFactory::class))
                ->setDecoratedService('api_platform.graphql.resolver.factory.item')
                ->setArguments([new Reference('api_platform.graphql.data_collector.resolver.factory.item.inner'), $requestStack]);

            $itemMutationDataCollectorResolverFactory = (new Definition(DataCollectorResolverFactory::class))
                ->setDecoratedService('api_platform.graphql.resolver.factory.item_mutation')
                ->setArguments([new Reference('api_platform.graphql.data_collector.resolver.factory.item_mutation.inner'), $requestStack]);

            $itemSubscriptionDataCollectorResolverFactory = (new Definition(DataCollectorResolverFactory::class))
                ->setDecoratedService('api_platform.graphql.resolver.factory.item_subscription')
                ->setArguments([new Reference('api_platform.graphql.data_collector.resolver.factory.item_subscription.inner'), $requestStack]);

            $container->addDefinitions([
                'api_platform.graphql.data_collector.resolver.factory.collection' => $collectionDataCollectorResolverFactory,
                'api_platform.graphql.data_collector.resolver.factory.item' => $itemDataCollectorResolverFactory,
                'api_platform.graphql.data_collector.resolver.factory.item_mutation' => $itemMutationDataCollectorResolverFactory,
                'api_platform.graphql.data_collector.resolver.factory.item_subscription' => $itemSubscriptionDataCollectorResolverFactory,
            ]);
        }
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

        // For older versions of doctrine bridge this allows autoconfiguration for filters
        if (!$container->has(ManagerRegistry::class)) {
            $container->setAlias(ManagerRegistry::class, 'doctrine');
        }

        $container->registerForAutoconfiguration(QueryItemExtensionInterface::class)
            ->addTag('api_platform.doctrine.orm.query_extension.item');
        $container->registerForAutoconfiguration(DoctrineQueryCollectionExtensionInterface::class)
            ->addTag('api_platform.doctrine.orm.query_extension.collection');
        $container->registerForAutoconfiguration(DoctrineOrmAbstractFilter::class);

        $container->registerForAutoconfiguration(OrmLinksHandlerInterface::class)
            ->addTag('api_platform.doctrine.orm.links_handler');

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
        $container->registerForAutoconfiguration(OdmLinksHandlerInterface::class)
            ->addTag('api_platform.doctrine.odm.links_handler');

        $loader->load('doctrine_mongodb_odm.xml');
    }

    private function registerHttpCacheConfiguration(ContainerBuilder $container, array $config, XmlFileLoader $loader): void
    {
        $loader->load('http_cache.xml');
        $loader->load('legacy/http_cache.xml');

        if (!$this->isConfigEnabled($container, $config['http_cache']['invalidation'])) {
            return;
        }

        if ($this->isConfigEnabled($container, $config['doctrine'])) {
            $loader->load('doctrine_orm_http_cache_purger.xml');
        }

        $loader->load('http_cache_purger.xml');
        $loader->load('legacy/http_cache_purger.xml');

        foreach ($config['http_cache']['invalidation']['scoped_clients'] as $client) {
            $definition = $container->getDefinition($client);
            $definition->addTag('api_platform.http_cache.http_client');
        }

        if (!($urls = $config['http_cache']['invalidation']['urls'])) {
            $urls = $config['http_cache']['invalidation']['varnish_urls'];
        }

        foreach ($urls as $key => $url) {
            $definition = new Definition(ScopingHttpClient::class, [new Reference('http_client'), $url, ['base_uri' => $url] + $config['http_cache']['invalidation']['request_options']]);
            $definition->setFactory([ScopingHttpClient::class, 'forBaseUri']);
            $definition->addTag('api_platform.http_cache.http_client');
            $container->setDefinition('api_platform.invalidation_http_client.'.$key, $definition);
        }

        $serviceName = $config['http_cache']['invalidation']['purger'];

        if (!$container->hasDefinition('api_platform.http_cache.purger')) {
            $container->setAlias('api_platform.http_cache.purger', $serviceName);
        }
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

            if ($this->isConfigEnabled($container, $config['graphql'])) {
                $loader->load('graphql/validator.xml');
            }

            $container->registerForAutoconfiguration(ValidationGroupsGeneratorInterface::class)
                ->addTag('api_platform.validation_groups_generator');
            $container->registerForAutoconfiguration(PropertySchemaRestrictionMetadataInterface::class)
                ->addTag('api_platform.metadata.property_schema_restriction');

            $loader->load('legacy/validator.xml');
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

        $container->setParameter('api_platform.mercure.include_type', $config['mercure']['include_type']);

        $loader->load('legacy/mercure.xml');
        $loader->load('mercure.xml');

        if ($this->isConfigEnabled($container, $config['doctrine'])) {
            $loader->load('doctrine_orm_mercure_publisher.xml');
        }
        if ($this->isConfigEnabled($container, $config['doctrine_mongodb_odm'])) {
            $loader->load('doctrine_odm_mercure_publisher.xml');
        }

        if ($this->isConfigEnabled($container, $config['graphql'])) {
            $loader->load('graphql_mercure.xml');
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

        $clientClass = class_exists(\Elasticsearch\Client::class) ? \Elasticsearch\Client::class : \Elastic\Elasticsearch\Client::class;

        $clientDefinition = new Definition($clientClass);
        $container->setDefinition('api_platform.elasticsearch.client', $clientDefinition);
        $container->registerForAutoconfiguration(RequestBodySearchCollectionExtensionInterface::class)
            ->addTag('api_platform.elasticsearch.request_body_search_extension.collection');
        $container->setParameter('api_platform.elasticsearch.hosts', $config['elasticsearch']['hosts']);
        $loader->load('elasticsearch.xml');

        // @phpstan-ignore-next-line
        if (\Elasticsearch\Client::class === $clientClass) {
            $loader->load('legacy/elasticsearch.xml');
            $container->setParameter('api_platform.elasticsearch.mapping', $config['elasticsearch']['mapping']);
            $container->setDefinition('api_platform.elasticsearch.client_for_metadata', $clientDefinition);
        }
    }

    private function registerSecurityConfiguration(ContainerBuilder $container, array $config, XmlFileLoader $loader): void
    {
        /** @var string[] $bundles */
        $bundles = $container->getParameter('kernel.bundles');

        if (!isset($bundles['SecurityBundle'])) {
            return;
        }

        $loader->load('security.xml');
        $loader->load('legacy/security.xml');

        if (interface_exists(ValidatorInterface::class)) {
            $loader->load('symfony/security_validator.xml');
        }

        if ($this->isConfigEnabled($container, $config['graphql'])) {
            $loader->load('graphql/security.xml');
        }
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
    }

    private function registerMakerConfiguration(ContainerBuilder $container, array $config, XmlFileLoader $loader): void
    {
        if (!$this->isConfigEnabled($container, $config['maker'])) {
            return;
        }

        $loader->load('maker.xml');
    }

    private function registerArgumentResolverConfiguration(XmlFileLoader $loader): void
    {
        $loader->load('argument_resolver.xml');
    }

    private function registerInflectorConfiguration(array $config): void
    {
        if ($config['keep_legacy_inflector']) {
            Inflector::keepLegacyInflector(true);
            trigger_deprecation('api-platform/core', '3.2', 'Using doctrine/inflector is deprecated since API Platform 3.2 and will be removed in API Platform 4. Use symfony/string instead. Run "composer require symfony/string" and set "keep_legacy_inflector" to false in config.');
        } else {
            Inflector::keepLegacyInflector(false);
        }
    }
}
