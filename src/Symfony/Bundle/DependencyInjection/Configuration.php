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

use ApiPlatform\Doctrine\Common\Filter\OrderFilterInterface;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Symfony\Controller\MainController;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\MongoDBBundle\DoctrineMongoDBBundle;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use GraphQL\GraphQL;
use Symfony\Bundle\FullStack;
use Symfony\Bundle\MakerBundle\MakerBundle;
use Symfony\Bundle\MercureBundle\MercureBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerExceptionInterface;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Yaml\Yaml;

/**
 * The configuration of the bundle.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('api_platform');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->beforeNormalization()
                ->ifTrue(static function ($v) {
                    return false === ($v['enable_swagger'] ?? null);
                })
                ->then(static function ($v) {
                    $v['swagger']['versions'] = [];

                    return $v;
                })
            ->end()
            ->children()
                ->scalarNode('title')
                    ->info('The title of the API.')
                    ->cannotBeEmpty()
                    ->defaultValue('')
                ->end()
                ->scalarNode('description')
                    ->info('The description of the API.')
                    ->cannotBeEmpty()
                    ->defaultValue('')
                ->end()
                ->scalarNode('version')
                    ->info('The version of the API.')
                    ->cannotBeEmpty()
                    ->defaultValue('0.0.0')
                ->end()
                ->booleanNode('show_webby')->defaultTrue()->info('If true, show Webby on the documentation page')->end()
                ->booleanNode('use_symfony_listeners')->defaultFalse()->info(sprintf('Uses Symfony event listeners instead of the %s.', MainController::class))->end()
                ->scalarNode('name_converter')->defaultNull()->info('Specify a name converter to use.')->end()
                ->scalarNode('asset_package')->defaultNull()->info('Specify an asset package name to use.')->end()
                ->scalarNode('path_segment_name_generator')->defaultValue('api_platform.metadata.path_segment_name_generator.underscore')->info('Specify a path name generator to use.')->end()
                ->scalarNode('inflector')->defaultValue('api_platform.metadata.inflector')->info('Specify an inflector to use.')->end()
                ->arrayNode('validator')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->variableNode('serialize_payload_fields')->defaultValue([])->info('Set to null to serialize all payload fields when a validation error is thrown, or set the fields you want to include explicitly.')->end()
                        ->booleanNode('query_parameter_validation')->defaultValue(true)->end()
                    ->end()
                ->end()
                ->arrayNode('eager_loading')
                    ->canBeDisabled()
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('fetch_partial')->defaultFalse()->info('Fetch only partial data according to serialization groups. If enabled, Doctrine ORM entities will not work as expected if any of the other fields are used.')->end()
                        ->integerNode('max_joins')->defaultValue(30)->info('Max number of joined relations before EagerLoading throws a RuntimeException')->end()
                        ->booleanNode('force_eager')->defaultTrue()->info('Force join on every relation. If disabled, it will only join relations having the EAGER fetch mode.')->end()
                    ->end()
                ->end()
                ->booleanNode('handle_symfony_errors')->defaultFalse()->info('Allows to handle symfony exceptions.')->end()
                ->booleanNode('enable_swagger')->defaultTrue()->info('Enable the Swagger documentation and export.')->end()
                ->booleanNode('enable_swagger_ui')->defaultValue(class_exists(TwigBundle::class))->info('Enable Swagger UI')->end()
                ->booleanNode('enable_re_doc')->defaultValue(class_exists(TwigBundle::class))->info('Enable ReDoc')->end()
                ->booleanNode('enable_entrypoint')->defaultTrue()->info('Enable the entrypoint')->end()
                ->booleanNode('enable_docs')->defaultTrue()->info('Enable the docs')->end()
                ->booleanNode('enable_profiler')->defaultTrue()->info('Enable the data collector and the WebProfilerBundle integration.')->end()
                ->booleanNode('enable_link_security')->defaultFalse()->info('Enable security for Links (sub resources)')->end()
                ->arrayNode('collection')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('exists_parameter_name')->defaultValue('exists')->cannotBeEmpty()->info('The name of the query parameter to filter on nullable field values.')->end()
                        ->scalarNode('order')->defaultValue('ASC')->info('The default order of results.')->end() // Default ORDER is required for postgresql and mysql >= 5.7 when using LIMIT/OFFSET request
                        ->scalarNode('order_parameter_name')->defaultValue('order')->cannotBeEmpty()->info('The name of the query parameter to order results.')->end()
                        ->enumNode('order_nulls_comparison')->defaultNull()->values(interface_exists(OrderFilterInterface::class) ? array_merge(array_keys(OrderFilterInterface::NULLS_DIRECTION_MAP), [null]) : [null])->info('The nulls comparison strategy.')->end()
                        ->arrayNode('pagination')
                            ->canBeDisabled()
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('page_parameter_name')->defaultValue('page')->cannotBeEmpty()->info('The default name of the parameter handling the page number.')->end()
                                ->scalarNode('enabled_parameter_name')->defaultValue('pagination')->cannotBeEmpty()->info('The name of the query parameter to enable or disable pagination.')->end()
                                ->scalarNode('items_per_page_parameter_name')->defaultValue('itemsPerPage')->cannotBeEmpty()->info('The name of the query parameter to set the number of items per page.')->end()
                                ->scalarNode('partial_parameter_name')->defaultValue('partial')->cannotBeEmpty()->info('The name of the query parameter to enable or disable partial pagination.')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('mapping')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('paths')
                            ->prototype('scalar')->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('resource_class_directories')
                    ->prototype('scalar')->end()
                ->end()
                ->arrayNode('serializer')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('hydra_prefix')->defaultFalse()->info('Use the "hydra:" prefix.')->end()
                    ->end()
                ->end()
            ->end();

        $this->addDoctrineOrmSection($rootNode);
        $this->addDoctrineMongoDbOdmSection($rootNode);
        $this->addOAuthSection($rootNode);
        $this->addGraphQlSection($rootNode);
        $this->addSwaggerSection($rootNode);
        $this->addHttpCacheSection($rootNode);
        $this->addMercureSection($rootNode);
        $this->addMessengerSection($rootNode);
        $this->addElasticsearchSection($rootNode);
        $this->addOpenApiSection($rootNode);
        $this->addMakerSection($rootNode);

        $this->addExceptionToStatusSection($rootNode);

        $this->addFormatSection($rootNode, 'formats', [
            'jsonld' => ['mime_types' => ['application/ld+json']],
        ]);
        $this->addFormatSection($rootNode, 'patch_formats', [
            'json' => ['mime_types' => ['application/merge-patch+json']],
        ]);

        $defaultDocFormats = [
            'jsonld' => ['mime_types' => ['application/ld+json']],
            'jsonopenapi' => ['mime_types' => ['application/vnd.openapi+json']],
            'html' => ['mime_types' => ['text/html']],
        ];

        if (class_exists(Yaml::class)) {
            $defaultDocFormats['yamlopenapi'] = ['mime_types' => ['application/vnd.openapi+yaml']];
        }

        $this->addFormatSection($rootNode, 'docs_formats', $defaultDocFormats);

        $this->addFormatSection($rootNode, 'error_formats', [
            'jsonld' => ['mime_types' => ['application/ld+json']],
            'jsonproblem' => ['mime_types' => ['application/problem+json']],
            'json' => ['mime_types' => ['application/problem+json', 'application/json']],
        ]);
        $rootNode
            ->children()
                ->arrayNode('jsonschema_formats')
                    ->scalarPrototype()->end()
                    ->defaultValue([])
                    ->info('The JSON formats to compute the JSON Schemas for.')
                ->end()
            ->end();

        $this->addDefaultsSection($rootNode);

        return $treeBuilder;
    }

    private function addDoctrineOrmSection(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->children()
                ->arrayNode('doctrine')
                    ->{class_exists(DoctrineBundle::class) && interface_exists(EntityManagerInterface::class) ? 'canBeDisabled' : 'canBeEnabled'}()
                ->end()
            ->end();
    }

    private function addDoctrineMongoDbOdmSection(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->children()
                ->arrayNode('doctrine_mongodb_odm')
                    ->{class_exists(DoctrineMongoDBBundle::class) ? 'canBeDisabled' : 'canBeEnabled'}()
                ->end()
            ->end();
    }

    private function addOAuthSection(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->children()
                ->arrayNode('oauth')
                    ->canBeEnabled()
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('clientId')->defaultValue('')->info('The oauth client id.')->end()
                        ->scalarNode('clientSecret')
                            ->defaultValue('')
                            ->info('The OAuth client secret. Never use this parameter in your production environment. It exposes crucial security information. This feature is intended for dev/test environments only. Enable "oauth.pkce" instead')
                        ->end()
                        ->booleanNode('pkce')->defaultFalse()->info('Enable the oauth PKCE.')->end()
                        ->scalarNode('type')->defaultValue('oauth2')->info('The oauth type.')->end()
                        ->scalarNode('flow')->defaultValue('application')->info('The oauth flow grant type.')->end()
                        ->scalarNode('tokenUrl')->defaultValue('')->info('The oauth token url.')->end()
                        ->scalarNode('authorizationUrl')->defaultValue('')->info('The oauth authentication url.')->end()
                        ->scalarNode('refreshUrl')->defaultValue('')->info('The oauth refresh url.')->end()
                        ->arrayNode('scopes')
                            ->prototype('scalar')->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    private function addGraphQlSection(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->children()
                ->arrayNode('graphql')
                    ->{class_exists(GraphQL::class) ? 'canBeDisabled' : 'canBeEnabled'}()
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('default_ide')->defaultValue('graphiql')->end()
                        ->arrayNode('graphiql')
                            ->{class_exists(GraphQL::class) && class_exists(TwigBundle::class) ? 'canBeDisabled' : 'canBeEnabled'}()
                        ->end()
                        ->arrayNode('graphql_playground')
                            ->{class_exists(GraphQL::class) && class_exists(TwigBundle::class) ? 'canBeDisabled' : 'canBeEnabled'}()
                        ->end()
                        ->arrayNode('introspection')
                            ->canBeDisabled()
                        ->end()
                        ->integerNode('max_query_depth')->defaultValue(20)
                        ->end()
                        ->integerNode('max_query_complexity')->defaultValue(500)
                        ->end()
                        ->scalarNode('nesting_separator')->defaultValue('_')->info('The separator to use to filter nested fields.')->end()
                        ->arrayNode('collection')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->arrayNode('pagination')
                                    ->canBeDisabled()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    private function addSwaggerSection(ArrayNodeDefinition $rootNode): void
    {
        $supportedVersions = [3];

        $rootNode
            ->children()
                ->arrayNode('swagger')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('persist_authorization')->defaultValue(false)->info('Persist the SwaggerUI Authorization in the localStorage.')->end()
                        ->arrayNode('versions')
                            ->info('The active versions of OpenAPI to be exported or used in Swagger UI. The first value is the default.')
                            ->defaultValue($supportedVersions)
                            ->beforeNormalization()
                                ->always(static function ($v): array {
                                    if (!\is_array($v)) {
                                        $v = [$v];
                                    }

                                    foreach ($v as &$version) {
                                        $version = (int) $version;
                                    }

                                    return $v;
                                })
                            ->end()
                            ->validate()
                                ->ifTrue(static fn ($v): bool => $v !== array_intersect($v, $supportedVersions))
                                ->thenInvalid(sprintf('Only the versions %s are supported. Got %s.', implode(' and ', $supportedVersions), '%s'))
                            ->end()
                            ->prototype('scalar')->end()
                        ->end()
                        ->arrayNode('api_keys')
                            ->useAttributeAsKey('key')
                            ->validate()
                                ->ifTrue(static fn ($v): bool => (bool) array_filter(array_keys($v), fn ($item) => !preg_match('/^[a-zA-Z0-9._-]+$/', $item)))
                                ->thenInvalid('The api keys "key" is not valid according to the pattern enforced by OpenAPI 3.1 ^[a-zA-Z0-9._-]+$.')
                            ->end()
                            ->prototype('array')
                                ->children()
                                    ->scalarNode('name')
                                        ->info('The name of the header or query parameter containing the api key.')
                                    ->end()
                                    ->enumNode('type')
                                        ->info('Whether the api key should be a query parameter or a header.')
                                        ->values(['query', 'header'])
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('http_auth')
                            ->info('Creates http security schemes for OpenAPI.')
                            ->useAttributeAsKey('key')
                            ->validate()
                                ->ifTrue(static fn ($v): bool => (bool) array_filter(array_keys($v), fn ($item) => !preg_match('/^[a-zA-Z0-9._-]+$/', $item)))
                                ->thenInvalid('The api keys "key" is not valid according to the pattern enforced by OpenAPI 3.1 ^[a-zA-Z0-9._-]+$.')
                            ->end()
                            ->prototype('array')
                                ->children()
                                    ->scalarNode('scheme')
                                        ->info('The OpenAPI HTTP auth scheme, for example "bearer"')
                                    ->end()
                                    ->scalarNode('bearerFormat')
                                        ->info('The OpenAPI HTTP bearer format')
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                        ->variableNode('swagger_ui_extra_configuration')
                            ->defaultValue([])
                            ->validate()
                                ->ifTrue(static fn ($v): bool => false === \is_array($v))
                                ->thenInvalid('The swagger_ui_extra_configuration parameter must be an array.')
                            ->end()
                            ->info('To pass extra configuration to Swagger UI, like docExpansion or filter.')
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    private function addHttpCacheSection(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->children()
                ->arrayNode('http_cache')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('public')->defaultNull()->info('To make all responses public by default.')->end()
                        ->arrayNode('invalidation')
                            ->info('Enable the tags-based cache invalidation system.')
                            ->canBeEnabled()
                            ->children()
                                ->arrayNode('varnish_urls')
                                    ->setDeprecated('api-platform/core', '3.0', 'The "varnish_urls" configuration is deprecated, use "urls" or "scoped_clients".')
                                    ->defaultValue([])
                                    ->prototype('scalar')->end()
                                    ->info('URLs of the Varnish servers to purge using cache tags when a resource is updated.')
                                ->end()
                                ->arrayNode('urls')
                                    ->defaultValue([])
                                    ->prototype('scalar')->end()
                                    ->info('URLs of the Varnish servers to purge using cache tags when a resource is updated.')
                                ->end()
                                ->arrayNode('scoped_clients')
                                    ->defaultValue([])
                                    ->prototype('scalar')->end()
                                    ->info('Service names of scoped client to use by the cache purger.')
                                ->end()
                                ->integerNode('max_header_length')
                                    ->defaultValue(7500)
                                    ->info('Max header length supported by the cache server.')
                                ->end()
                                ->variableNode('request_options')
                                    ->defaultValue([])
                                    ->validate()
                                        ->ifTrue(static fn ($v): bool => false === \is_array($v))
                                        ->thenInvalid('The request_options parameter must be an array.')
                                    ->end()
                                    ->info('To pass options to the client charged with the request.')
                                ->end()
                                ->scalarNode('purger')
                                    ->defaultValue('api_platform.http_cache.purger.varnish')
                                    ->info('Specify a purger to use (available values: "api_platform.http_cache.purger.varnish.ban", "api_platform.http_cache.purger.varnish.xkey", "api_platform.http_cache.purger.souin").')
                                ->end()
                                ->arrayNode('xkey')
                                    ->setDeprecated('api-platform/core', '3.0', 'The "xkey" configuration is deprecated, use your own purger to customize surrogate keys or the appropriate paramters.')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->scalarNode('glue')
                                        ->defaultValue(' ')
                                        ->info('xkey glue between keys')
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    private function addMercureSection(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->children()
                ->arrayNode('mercure')
                    ->{class_exists(MercureBundle::class) ? 'canBeDisabled' : 'canBeEnabled'}()
                    ->children()
                        ->scalarNode('hub_url')
                            ->defaultNull()
                            ->info('The URL sent in the Link HTTP header. If not set, will default to the URL for MercureBundle\'s default hub.')
                        ->end()
                        ->booleanNode('include_type')
                            ->defaultFalse()
                            ->info('Always include @type in updates (including delete ones).')
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    private function addMessengerSection(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->children()
                ->arrayNode('messenger')
                    ->{!class_exists(FullStack::class) && interface_exists(MessageBusInterface::class) ? 'canBeDisabled' : 'canBeEnabled'}()
                ->end()
            ->end();
    }

    private function addElasticsearchSection(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->children()
                ->arrayNode('elasticsearch')
                    ->canBeEnabled()
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')
                            ->defaultFalse()
                            ->validate()
                                ->ifTrue()
                                ->then(static function (bool $v): bool {
                                    if (
                                        // ES v7
                                        !class_exists(\Elasticsearch\Client::class)
                                        // ES v8 and up
                                        && !class_exists(\Elastic\Elasticsearch\Client::class)
                                    ) {
                                        throw new InvalidConfigurationException('The elasticsearch/elasticsearch package is required for Elasticsearch support.');
                                    }

                                    return $v;
                                })
                            ->end()
                        ->end()
                        ->arrayNode('hosts')
                            ->beforeNormalization()->castToArray()->end()
                            ->defaultValue([])
                            ->prototype('scalar')->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    private function addOpenApiSection(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->children()
                ->arrayNode('openapi')
                    ->addDefaultsIfNotSet()
                        ->children()
                        ->arrayNode('contact')
                        ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('name')->defaultNull()->info('The identifying name of the contact person/organization.')->end()
                                ->scalarNode('url')->defaultNull()->info('The URL pointing to the contact information. MUST be in the format of a URL.')->end()
                                ->scalarNode('email')->defaultNull()->info('The email address of the contact person/organization. MUST be in the format of an email address.')->end()
                            ->end()
                        ->end()
                        ->scalarNode('termsOfService')->defaultNull()->info('A URL to the Terms of Service for the API. MUST be in the format of a URL.')->end()
                        ->arrayNode('tags')
                            ->info('Global OpenApi tags overriding the default computed tags if specified.')
                            ->prototype('array')
                                ->children()
                                    ->scalarNode('name')->isRequired()->end()
                                    ->scalarNode('description')->defaultNull()->end()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('license')
                        ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('name')->defaultNull()->info('The license name used for the API.')->end()
                                ->scalarNode('url')->defaultNull()->info('URL to the license used for the API. MUST be in the format of a URL.')->end()
                                ->scalarNode('identifier')->defaultNull()->info('An SPDX license expression for the API. The identifier field is mutually exclusive of the url field.')->end()
                            ->end()
                        ->end()
                        ->variableNode('swagger_ui_extra_configuration')
                            ->defaultValue([])
                            ->validate()
                                ->ifTrue(static fn ($v): bool => false === \is_array($v))
                                ->thenInvalid('The swagger_ui_extra_configuration parameter must be an array.')
                            ->end()
                            ->info('To pass extra configuration to Swagger UI, like docExpansion or filter.')
                        ->end()
                        ->booleanNode('overrideResponses')->defaultTrue()->info('Whether API Platform adds automatic responses to the OpenAPI documentation.')
                    ->end()
                ->end()
            ->end();
    }

    /**
     * @throws InvalidConfigurationException
     */
    private function addExceptionToStatusSection(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->children()
                ->arrayNode('exception_to_status')
                    ->defaultValue([
                        SerializerExceptionInterface::class => Response::HTTP_BAD_REQUEST,
                        InvalidArgumentException::class => Response::HTTP_BAD_REQUEST,
                        OptimisticLockException::class => Response::HTTP_CONFLICT,
                    ])
                    ->info('The list of exceptions mapped to their HTTP status code.')
                    ->normalizeKeys(false)
                    ->useAttributeAsKey('exception_class')
                    ->prototype('integer')->end()
                    ->validate()
                        ->ifArray()
                        ->then(static function (array $exceptionToStatus): array {
                            foreach ($exceptionToStatus as $httpStatusCode) {
                                if ($httpStatusCode < 100 || $httpStatusCode >= 600) {
                                    throw new InvalidConfigurationException(sprintf('The HTTP status code "%s" is not valid.', $httpStatusCode));
                                }
                            }

                            return $exceptionToStatus;
                        })
                    ->end()
                ->end()
            ->end();
    }

    private function addFormatSection(ArrayNodeDefinition $rootNode, string $key, array $defaultValue): void
    {
        $rootNode
            ->children()
                ->arrayNode($key)
                    ->defaultValue($defaultValue)
                    ->info('The list of enabled formats. The first one will be the default.')
                    ->normalizeKeys(false)
                    ->useAttributeAsKey('format')
                    ->beforeNormalization()
                        ->ifArray()
                        ->then(static function ($v) {
                            foreach ($v as $format => $value) {
                                if (isset($value['mime_types'])) {
                                    continue;
                                }

                                $v[$format] = ['mime_types' => $value];
                            }

                            return $v;
                        })
                    ->end()
                    ->prototype('array')
                        ->children()
                            ->arrayNode('mime_types')->prototype('scalar')->end()->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    private function addDefaultsSection(ArrayNodeDefinition $rootNode): void
    {
        $nameConverter = new CamelCaseToSnakeCaseNameConverter();
        $defaultsNode = $rootNode->children()->arrayNode('defaults');

        $defaultsNode
            ->ignoreExtraKeys(false)
            ->beforeNormalization()
            ->always(static function (array $defaults) use ($nameConverter): array {
                $normalizedDefaults = [];
                foreach ($defaults as $option => $value) {
                    $option = $nameConverter->normalize($option);
                    $normalizedDefaults[$option] = $value;
                }

                return $normalizedDefaults;
            });

        $this->defineDefault($defaultsNode, new \ReflectionClass(ApiResource::class), $nameConverter);
        $this->defineDefault($defaultsNode, new \ReflectionClass(Put::class), $nameConverter);
        $this->defineDefault($defaultsNode, new \ReflectionClass(Post::class), $nameConverter);
    }

    private function addMakerSection(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->children()
                ->arrayNode('maker')
                    ->{class_exists(MakerBundle::class) ? 'canBeDisabled' : 'canBeEnabled'}()
                ->end()
            ->end();
    }

    private function defineDefault(ArrayNodeDefinition $defaultsNode, \ReflectionClass $reflectionClass, CamelCaseToSnakeCaseNameConverter $nameConverter): void
    {
        foreach ($reflectionClass->getConstructor()->getParameters() as $parameter) {
            $defaultsNode->children()->variableNode($nameConverter->normalize($parameter->getName()));
        }
    }
}
