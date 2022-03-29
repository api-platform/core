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

namespace ApiPlatform\Tests\Symfony\Bundle\DependencyInjection;

use ApiPlatform\Api\FilterInterface;
use ApiPlatform\Api\UrlGeneratorInterface;
use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use ApiPlatform\Core\DataProvider\CollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\SubresourceDataProviderInterface;
use ApiPlatform\Core\Tests\ProphecyTrait;
use ApiPlatform\DataTransformer\DataTransformerInitializerInterface;
use ApiPlatform\DataTransformer\DataTransformerInterface;
use ApiPlatform\Doctrine\Odm\Extension\AggregationCollectionExtensionInterface;
use ApiPlatform\Doctrine\Odm\Extension\AggregationItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface as DoctrineQueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Elasticsearch\Extension\RequestBodySearchCollectionExtensionInterface;
use ApiPlatform\GraphQl\Error\ErrorHandlerInterface;
use ApiPlatform\GraphQl\Resolver\MutationResolverInterface;
use ApiPlatform\GraphQl\Resolver\QueryCollectionResolverInterface;
use ApiPlatform\GraphQl\Resolver\QueryItemResolverInterface;
use ApiPlatform\GraphQl\Type\Definition\TypeInterface as GraphQlTypeInterface;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\Symfony\Bundle\DependencyInjection\ApiPlatformExtension;
use ApiPlatform\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaRestrictionMetadataInterface;
use ApiPlatform\Symfony\Validator\ValidationGroupsGeneratorInterface;
use Doctrine\Common\Annotations\Annotation;
use phpDocumentor\Reflection\DocBlockFactoryInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\AbstractUid;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Yaml\Yaml;

class ApiPlatformExtensionTest extends TestCase
{
    use ProphecyTrait;

    public const DEFAULT_CONFIG = ['api_platform' => [
        'title' => 'title',
        'metadata_backward_compatibility_layer' => false,
        'description' => 'description',
        'version' => 'version',
        'formats' => [
            'jsonld' => ['mime_types' => ['application/ld+json']],
            'jsonhal' => ['mime_types' => ['application/hal+json']],
        ],
        'http_cache' => ['invalidation' => [
            'enabled' => true,
            'varnish_urls' => ['test'],
            'xkey' => [
                'glue' => ' ',
            ],
            'purger' => 'api_platform.http_cache.purger.varnish.ban',
            'request_options' => [
                'allow_redirects' => [
                    'max' => 5,
                    'protocols' => ['http', 'https'],
                    'stric' => false,
                    'referer' => false,
                    'track_redirects' => false,
                ],
                'http_errors' => true,
                'decode_content' => false,
                'verify' => false,
                'cookies' => true,
                'headers' => [
                    'User-Agent' => 'none',
                ],
            ],
        ]],
        'doctrine_mongodb_odm' => [
            'enabled' => true,
        ],
        'defaults' => [
            'attributes' => [],
            'url_generation_strategy' => UrlGeneratorInterface::ABS_URL,
        ],
        'collection' => [
            'exists_parameter_name' => 'exists',
            'order' => 'ASC',
            'order_parameter_name' => 'order',
            'order_nulls_comparison' => null,
            'pagination' => [
                'client_enabled' => false,
                'client_items_per_page' => false,
                'enabled' => true,
                'enabled_parameter_name' => 'pagination',
                'items_per_page' => 30,
                'items_per_page_parameter_name' => 'itemsPerPage',
                'maximum_items_per_page' => 30,
                'page_parameter_name' => 'page',
                'partial' => false,
                'client_partial' => false,
                'partial_parameter_name' => 'partial',
            ],
        ],
        'error_formats' => [
            'jsonproblem' => ['application/problem+json'],
            'jsonld' => ['application/ld+json'],
        ],
        'patch_formats' => [],
        'exception_to_status' => [
            ExceptionInterface::class => Response::HTTP_BAD_REQUEST,
            InvalidArgumentException::class => Response::HTTP_BAD_REQUEST,
            FilterValidationException::class => Response::HTTP_BAD_REQUEST,
            OptimisticLockException::class => Response::HTTP_CONFLICT,
        ],
        'show_webby' => true,
        // TODO: to remove in 3.0
        'allow_plain_identifiers' => false,
        'eager_loading' => [
            'enabled' => true,
            'max_joins' => 30,
            'force_eager' => true,
            'fetch_partial' => false,
        ],
        'asset_package' => null,
        'enable_entrypoint' => true,
        'enable_docs' => true,
    ]];

    /** @var ContainerBuilder */
    private $container;

    protected function setUp(): void
    {
        $containerParameterBag = new ParameterBag([
            'kernel.bundles' => [
                'DoctrineBundle' => DoctrineBundle::class,
                'SecurityBundle' => SecurityBundle::class,
            ],
            'kernel.bundles_metadata' => [
                'TestBundle' => [
                    'parent' => null,
                    'path' => realpath(__DIR__.'/../../../Fixtures/TestBundle'),
                    'namespace' => TestBundle::class,
                ],
            ],
            'kernel.project_dir' => __DIR__.'/../../../Fixtures/app',
            'kernel.debug' => false,
        ]);

        $this->container = new ContainerBuilder($containerParameterBag);
    }

    private function assertContainerHas(array $services, array $aliases = [], array $tags = [])
    {
        foreach ($services as $service) {
            $this->assertTrue($this->container->hasDefinition($service));
        }

        foreach ($aliases as $alias) {
            $this->assertTrue($this->container->hasAlias($alias));
        }

        foreach ($tags as $service => $tag) {
            $this->assertArrayHasKey($tag, $this->container->getDefinition($service)->getTags());
        }
    }

    public function testCommonConfiguration(): void
    {
        $config = self::DEFAULT_CONFIG;
        (new ApiPlatformExtension())->load($config, $this->container);

        $services = [
            // api.xml
            'api_platform.negotiator',
            'api_platform.resource_class_resolver',
            'api_platform.route_name_resolver',
            'api_platform.route_name_resolver.cached',
            'api_platform.router',
            'api_platform.serializer.context_builder',
            'api_platform.serializer.context_builder.filter',
            'api_platform.serializer.property_filter',
            'api_platform.serializer.group_filter',
            'api_platform.serializer.normalizer.item',
            'api_platform.serializer.mapping.class_metadata_factory',
            'api_platform.operation_path_resolver.custom',
            'api_platform.operation_path_resolver.generator',
            'api_platform.path_segment_name_generator.underscore',
            'api_platform.path_segment_name_generator.dash',
            'api_platform.action.placeholder',
            'api_platform.action.not_found',
            'api_platform.action.entrypoint',
            'api_platform.action.documentation',
            'api_platform.action.exception',

            // data_persister.xml
            'api_platform.data_persister',

            // data_provider.xml
            'api_platform.serializer_locator',
            'api_platform.pagination_options',

            // filter.xml
            'api_platform.filter_locator',
            'api_platform.filter_collection_factory',
            'api_platform.filters',

            // ramsey_uuid.xml
            'api_platform.identifier.uuid_normalizer',
            'api_platform.serializer.uuid_denormalizer',
            'api_platform.ramsey_uuid.uri_variables.transformer.uuid',
        ];

        $aliases = [
            // api.xml
            'api_platform.serializer',
            'api_platform.property_accessor',
            'api_platform.property_info',
            'ApiPlatform\Api\ResourceClassResolverInterface',
            'ApiPlatform\Api\UrlGeneratorInterface',
            'ApiPlatform\Serializer\SerializerContextBuilderInterface',
            'ApiPlatform\Serializer\Filter\PropertyFilter',
            'ApiPlatform\Serializer\Filter\GroupFilter',
            'api_platform.operation_path_resolver',
            'api_platform.action.get_collection',
            'api_platform.action.post_collection',
            'api_platform.action.get_item',
            'api_platform.action.patch_item',
            'api_platform.action.put_item',
            'api_platform.action.delete_item',
            'api_platform.action.get_subresource',
            'ApiPlatform\Action\NotFoundAction',

            // data_persister.xml
            'ApiPlatform\Core\DataPersister\DataPersisterInterface',

            // data_provider.xml
            'ApiPlatform\State\Pagination\PaginationOptions',

            'api_platform.operation_path_resolver.default',
            'api_platform.path_segment_name_generator',
        ];

        $tags = [
            'api_platform.cache.route_name_resolver' => 'cache.pool',
            'api_platform.serializer.normalizer.item' => 'serializer.normalizer',
            'api_platform.serializer_locator' => 'container.service_locator',
            'api_platform.filter_locator' => 'container.service_locator',

            // ramsey_uuid.xml
            'api_platform.identifier.uuid_normalizer' => 'api_platform.identifier.denormalizer',
            'api_platform.serializer.uuid_denormalizer' => 'serializer.normalizer',
            'api_platform.ramsey_uuid.uri_variables.transformer.uuid' => 'api_platform.uri_variables.transformer',
        ];

        $this->assertContainerHas($services, $aliases, $tags);
    }

    public function testCommonConfigurationAbstractUid(): void
    {
        if (!class_exists(AbstractUid::class)) {
            $this->markTestSkipped('class Symfony\Component\Uid\AbstractUid does not exist');
        }

        $config = self::DEFAULT_CONFIG;
        (new ApiPlatformExtension())->load($config, $this->container);

        $services = [
            'api_platform.identifier.symfony_ulid_normalizer',
            'api_platform.identifier.symfony_uuid_normalizer',
            'api_platform.symfony.uri_variables.transformer.ulid',
            'api_platform.symfony.uri_variables.transformer.uuid',
        ];

        $tags = [
            'api_platform.identifier.symfony_ulid_normalizer' => 'api_platform.identifier.denormalizer',
            'api_platform.identifier.symfony_uuid_normalizer' => 'api_platform.identifier.denormalizer',
            'api_platform.symfony.uri_variables.transformer.ulid' => 'api_platform.uri_variables.transformer',
            'api_platform.symfony.uri_variables.transformer.uuid' => 'api_platform.uri_variables.transformer',
        ];

        $this->assertContainerHas($services, [], $tags);
    }

    public function dataProviderCommonConfigurationAliasNameConverter()
    {
        return [
            ['dummyValue', true],
            [null, false],
        ];
    }

    /**
     * @dataProvider dataProviderCommonConfigurationAliasNameConverter
     *
     * @param mixed $nameConverterConfig
     * @param mixed $aliasIsExected
     */
    public function testCommonConfigurationAliasNameConverter($nameConverterConfig, $aliasIsExected)
    {
        $config = self::DEFAULT_CONFIG;
        $config['api_platform']['name_converter'] = $nameConverterConfig;

        (new ApiPlatformExtension())->load($config, $this->container);

        $this->assertSame($aliasIsExected, $this->container->hasAlias('api_platform.name_converter'));
    }

    public function testCommonConfigurationWithMetadataBackwardCompatibilityLayer()
    {
        $config = self::DEFAULT_CONFIG;
        $config['api_platform']['metadata_backward_compatibility_layer'] = true;

        (new ApiPlatformExtension())->load($config, $this->container);

        $services = [
            // legacy/api.xml
            'api_platform.operation_method_resolver',
            'api_platform.formats_provider',
            'api_platform.route_loader.legacy',
            'api_platform.operation_path_resolver.router',
            'api_platform.iri_converter.legacy',
            'api_platform.listener.request.add_format',
            'api_platform.listener.request.deserialize',
            'api_platform.listener.view.serialize',
            'api_platform.listener.view.respond',
            'api_platform.listener.exception.validation',
            'api_platform.listener.exception',
            'api_platform.identifier.integer',
            'api_platform.identifier.date_normalizer',
            'api_platform.operation_path_resolver.underscore',
            'api_platform.operation_path_resolver.dash',
            'api_platform.listener.view.write.legacy',
            'api_platform.listener.request.read.legacy',
            'api_platform.operation_path_resolver.router',

            // legacy/data_provider.xml
            'api_platform.item_data_provider',
            'api_platform.collection_data_provider',
            'api_platform.subresource_data_provider',
            'api_platform.pagination.legacy',
        ];
        $aliases = [
            // legacy/api.xml
            'api_platform.operation_path_resolver',
            'api_platform.metadata.resource.metadata_collection_factory.retro_compatible',
            'ApiPlatform\Core\Api\OperationAwareFormatsProviderInterface',
            'ApiPlatform\Core\Api\IriConverterInterface',
            'api_platform.operation_path_resolver.legacy',

            // legacy/data_provider.xml
            'ApiPlatform\Core\DataProvider\ItemDataProviderInterface',
            'ApiPlatform\Core\DataProvider\CollectionDataProviderInterface',
            'ApiPlatform\Core\DataProvider\SubresourceDataProviderInterface',
            'ApiPlatform\Core\DataProvider\Pagination',

            // legacy/backward_compatibility.xml
            'api_platform.metadata.property.metadata_factory',
            'api_platform.metadata.resource.name_collection_factory',
            'api_platform.route_loader',
            'api_platform.iri_converter',
            'api_platform.identifiers_extractor',
            'api_platform.pagination',
            'api_platform.cache.metadata.property',
            'api_platform.openapi.factory',
        ];
        $tags = [
            // legacy/api.xml
            'api_platform.route_loader.legacy' => 'routing.loader',
            'api_platform.listener.request.add_format' => 'kernel.event_listener',
            'api_platform.listener.request.deserialize' => 'kernel.event_listener',
            'api_platform.listener.view.serialize' => 'kernel.event_listener',
            'api_platform.listener.view.respond' => 'kernel.event_listener',
            'api_platform.listener.exception.validation' => 'kernel.event_listener',
            'api_platform.listener.exception' => 'kernel.event_listener',
            'api_platform.listener.exception' => 'monolog.logger',
            'api_platform.identifier.integer' => 'api_platform.identifier.denormalizer',
            'api_platform.identifier.date_normalizer' => 'api_platform.identifier.denormalizer',
            'api_platform.listener.view.write.legacy' => 'kernel.event_listener',
            'api_platform.listener.request.read.legacy' => 'kernel.event_listener',
        ];

        $this->assertContainerHas($services, $aliases, $tags);
    }

    public function testCommonConfigurationWithoutMetadataBackwardCompatibilityLayer()
    {
        $config = self::DEFAULT_CONFIG;
        (new ApiPlatformExtension())->load($config, $this->container);

        $services = [
            // v3/api.xml
            'api_platform.route_loader',
            'api_platform.symfony.iri_converter',
            'api_platform.api.identifiers_extractor',
            'api_platform.uri_variables.converter',
            'api_platform.uri_variables.transformer.integer',
            'api_platform.uri_variables.transformer.date_time',

            // legacy/data_provider.xml
            'api_platform.item_data_provider',
            'api_platform.collection_data_provider',
            'api_platform.subresource_data_provider',
            'api_platform.pagination.legacy',

            // v3/state.xml
            'api_platform.pagination.next',
            'api_platform.state_provider',
            'api_platform.state_processor',

            // v3/backward_compatibility.xml
            'api_platform.metadata.resource.metadata_collection_factory.legacy_resource_metadata',
            'api_platform.metadata.resource.metadata_collection_factory.legacy_subresource_metadata',
            'api_platform.legacy_data_provider_state',
            'api_platform.listener.view.write.legacy',
            'api_platform.listener.request.read.legacy',
        ];

        $aliases = [
            // v3/api.xml
            'api_platform.iri_converter',
            'ApiPlatform\Api\IriConverterInterface',
            'api_platform.identifiers_extractor',
            'ApiPlatform\Api\IdentifiersExtractorInterface',

            // legacy/data_provider.xml
            'ApiPlatform\Core\DataProvider\ItemDataProviderInterface',
            'ApiPlatform\Core\DataProvider\CollectionDataProviderInterface',
            'ApiPlatform\Core\DataProvider\SubresourceDataProviderInterface',
            'ApiPlatform\Core\DataProvider\Pagination',

            // v3/state.xml
            'ApiPlatform\State\Pagination\Pagination',
            'api_platform.pagination',
            'ApiPlatform\State\ProviderInterface',
            'ApiPlatform\State\ProcessorInterface',
        ];

        $tags = [
            // v3/api.xml
            'api_platform.route_loader' => 'routing.loader',
            'api_platform.uri_variables.transformer.integer' => 'api_platform.uri_variables.transformer',
            'api_platform.uri_variables.transformer.date_time' => 'api_platform.uri_variables.transformer',

            // v3/backward_compatibility.xml
            'api_platform.legacy_data_provider_state' => 'api_platform.state_provider',
            'api_platform.listener.view.write.legacy' => 'kernel.event_listener',
            'api_platform.listener.request.read.legacy' => 'kernel.event_listener',
        ];

        $this->assertContainerHas($services, $aliases, $tags);
    }

    public function testMetadataConfiguration(): void
    {
        $config = self::DEFAULT_CONFIG;
        (new ApiPlatformExtension())->load($config, $this->container);

        $services = [
            // metadata/xml.xml
            'api_platform.metadata.resource_extractor.xml',
            'api_platform.metadata.property_extractor.xml',

            // metadata/property_name.xml
            'api_platform.metadata.property.name_collection_factory.property_info',
            'api_platform.metadata.property.name_collection_factory.cached',
            'api_platform.metadata.property.name_collection_factory.xml',

            // legacy/metadata.xml
            'api_platform.metadata.resource.metadata_factory.input_output',
            'api_platform.metadata.resource.metadata_factory.short_name',
            'api_platform.metadata.resource.metadata_factory.operation',
            'api_platform.metadata.resource.metadata_factory.formats',
            'api_platform.metadata.resource.metadata_factory.cached',
            'api_platform.cache.metadata.resource.legacy',
            'api_platform.metadata.property.metadata_factory.property_info.legacy',
            'api_platform.metadata.property.metadata_factory.serializer.legacy',
            'api_platform.metadata.subresource.metadata_factory.annotation.legacy',
            'api_platform.metadata.property.metadata_factory.annotation.legacy',
            'api_platform.metadata.property.metadata_factory.cached.legacy',
            'api_platform.metadata.property.metadata_factory.default_property.legacy',
            'api_platform.metadata.extractor.xml.legacy',
            'api_platform.metadata.property.metadata_factory.xml.legacy',
            'api_platform.cache.metadata.property.legacy',
            'api_platform.metadata.resource.metadata_factory.xml',
            'api_platform.metadata.resource.name_collection_factory.xml.legacy',
            'api_platform.subresource_operation_factory.cached',
            'api_platform.cache.subresource_operation_factory',
            'api_platform.subresource_operation_factory',

            // metadata/links.xml
            'api_platform.metadata.resource.link_factory',

            // metadata/property.xml
            'api_platform.metadata.property.metadata_factory.property_info',
            'api_platform.metadata.property.metadata_factory.attribute',
            'api_platform.metadata.property.metadata_factory.serializer',
            'api_platform.metadata.property.metadata_factory.cached',
            'api_platform.metadata.property.metadata_factory.default_property',
            'api_platform.metadata.property.metadata_factory.xml',
            'api_platform.metadata.property.identifier_metadata_factory.attribute',
            'api_platform.metadata.property.identifier_metadata_factory.xml',
            'api_platform.metadata.property.identifier_metadata_factory.yaml',
            'api_platform.cache.metadata.property',

            // metadata/property_name.xml
            'api_platform.metadata.property.name_collection_factory.property_info',
            'api_platform.metadata.property.name_collection_factory.cached',
            'api_platform.metadata.property.name_collection_factory.xml',

            // metadata/resource.xml
            'api_platform.metadata.resource.metadata_collection_factory.attributes',
            'api_platform.metadata.resource.metadata_collection_factory.xml',
            'api_platform.metadata.resource.metadata_collection_factory.uri_template',
            'api_platform.metadata.resource.metadata_collection_factory.link',
            'api_platform.metadata.resource.metadata_collection_factory.operation_name',
            'api_platform.metadata.resource.metadata_collection_factory.input_output',
            'api_platform.metadata.resource.metadata_collection_factory.formats',
            'api_platform.metadata.resource.metadata_collection_factory.filters',
            'api_platform.metadata.resource.metadata_collection_factory.alternate_uri',
            'api_platform.metadata.resource.metadata_collection_factory.cached',
            'api_platform.cache.metadata.resource_collection',

            // metadata/resource_name.xml
            'api_platform.cache.metadata.resource',
            'api_platform.metadata.resource.name_collection_factory.attributes',
            'api_platform.metadata.resource.name_collection_factory.cached',
            'api_platform.metadata.resource.name_collection_factory.xml',

            // metadata/yaml.xml
            'api_platform.metadata.resource_extractor.yaml',
            'api_platform.metadata.property_extractor.yaml',
            'api_platform.metadata.resource.name_collection_factory.yaml',
            'api_platform.metadata.resource.metadata_collection_factory.yaml',
            'api_platform.metadata.property.metadata_factory.yaml',
            'api_platform.metadata.property.name_collection_factory.yaml',
        ];

        $aliases = [
            // metadata/property_name.xml
            'api_platform.metadata.property.name_collection_factory',
            'ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface',
            'ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface',

            // legacy/metadata.xml
            'ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface',
            'api_platform.metadata.property.metadata_factory.legacy',
            'ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface',
            'api_platform.metadata.resource.metadata_factory',

            // metadata/property.xml
            'api_platform.metadata.property.identifier_metadata_factory',

            // metadata/property_name.xml
            'api_platform.metadata.property.name_collection_factory',
            'ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface',
            'ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface',

            // metadata/resource.xml
            'api_platform.metadata.resource.metadata_collection_factory',
            'api_platform.metadata.resource.metadata_collection_factory.retro_compatible',
            'ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface',

            // metadata/resource_name.xml
            'api_platform.metadata.resource.name_collection_factory',
            'ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface',

            // metadata/backward_compatibility.xml
            'api_platform.metadata.property.metadata_factory',
        ];

        $tags = [
            // legacy/metadata.xml
            'api_platform.cache.metadata.resource.legacy' => 'cache.pool',
            'api_platform.cache.metadata.property.legacy' => 'cache.pool',
            'api_platform.cache.subresource_operation_factory' => 'cache.pool',

            // metadata/property.xml
            'api_platform.cache.metadata.property' => 'cache.pool',

            // metadata/resource.xml
            'api_platform.cache.metadata.resource_collection' => 'cache.pool',

            // metadata/resource_name.xml
            'api_platform.cache.metadata.resource' => 'cache.pool',
        ];

        $this->assertContainerHas($services, $aliases, $tags);
    }

    public function testMetadataConfigurationAnnotation()
    {
        if (!class_exists(Annotation::class)) {
            $this->markTestSkipped('class Doctrine\Common\Annotations\Annotation does not exist');
        }

        $config = self::DEFAULT_CONFIG;
        (new ApiPlatformExtension())->load($config, $this->container);

        $services = [
            // legacy/metadata_annotation.xml
            'api_platform.metadata.resource.name_collection_factory.annotation',
            'api_platform.metadata.resource.metadata_factory.annotation',
            'api_platform.metadata.resource.filter_metadata_factory.annotation',
        ];

        $this->assertContainerHas($services, [], []);
    }

    public function testMetadataConfigurationDocBlockFactoryInterface()
    {
        if (!class_exists(DocBlockFactoryInterface::class)) {
            $this->markTestSkipped('class phpDocumentor\Reflection\DocBlockFactoryInterface does not exist');
        }

        $config = self::DEFAULT_CONFIG;
        (new ApiPlatformExtension())->load($config, $this->container);

        $services = [
            // legacy/metadata_php_doc.xml
            'api_platform.metadata.resource.metadata_factory.php_doc',

            // metadata/php_doc.xml
            'api_platform.metadata.resource.metadata_collection_factory.php_doc',
        ];

        $this->assertContainerHas($services, [], []);
    }

    public function testMetadataConfigurationYaml()
    {
        if (!class_exists(Yaml::class)) {
            $this->markTestSkipped('class Symfony\Component\Yaml\Yaml does not exist');
        }

        $config = self::DEFAULT_CONFIG;
        (new ApiPlatformExtension())->load($config, $this->container);

        $services = [
            // legacy/metadata_yaml.xml
            'api_platform.metadata.extractor.yaml.legacy',
            'api_platform.metadata.property.metadata_factory.yaml.legacy',
            'api_platform.metadata.resource.metadata_factory.yaml',
            'api_platform.metadata.resource.name_collection_factory.yaml.legacy',
        ];

        $this->assertContainerHas($services, [], []);
    }

    public function testSwaggerConfiguration()
    {
        $config = self::DEFAULT_CONFIG;
        $config['api_platform']['enable_swagger'] = true;
        $config['api_platform']['enable_swagger_ui'] = true;

        (new ApiPlatformExtension())->load($config, $this->container);

        $services = [
            // json_schema.xml
            'api_platform.json_schema.type_factory',
            'api_platform.json_schema.schema_factory',
            'api_platform.json_schema.json_schema_generate_command',

            // openapi.xml
            'api_platform.openapi.normalizer',
            'api_platform.openapi.options',
            'api_platform.openapi.command',
            'api_platform.openapi.normalizer.api_gateway',

            // swagger_ui.xml
            'api_platform.swagger.listener.ui',
            'api_platform.swagger_ui.context',
            'api_platform.swagger.action.ui',

            // v3/openapi.xml
            'api_platform.openapi.factory.next',

            // v3/swagger_ui.xml
            'api_platform.swagger_ui.action',
        ];

        $aliases = [
            // json_schema.xml
            'ApiPlatform\JsonSchema\TypeFactoryInterface',
            'ApiPlatform\JsonSchema\SchemaFactoryInterface',

            // openapi.xml
            'ApiPlatform\OpenApi\Serializer\OpenApiNormalizer',
            'ApiPlatform\OpenApi\Options',

            // swagger_ui.xml
            'api_platform.swagger_ui.listener',

            // v3/openapi.xml
            'ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface',
            'api_platform.openapi.factory',
        ];

        $tags = [
            // json_schema.xml
            'api_platform.json_schema.json_schema_generate_command' => 'console.command',

            // openapi.xml
            'api_platform.openapi.normalizer' => 'serializer.normalizer',
            'api_platform.openapi.command' => 'console.command',
            'api_platform.openapi.normalizer.api_gateway' => 'serializer.normalizer',

            // swagger_ui.xml
            'api_platform.swagger.listener.ui' => 'kernel.event_listener',
        ];

        $this->assertContainerHas($services, $aliases, $tags);
    }

    public function testSwaggerConfigurationMetadataBackwardCompatibilityLayer()
    {
        $config = self::DEFAULT_CONFIG;
        $config['api_platform']['enable_swagger'] = true;
        $config['api_platform']['enable_swagger_ui'] = true;
        $config['api_platform']['metadata_backward_compatibility_layer'] = true;

        (new ApiPlatformExtension())->load($config, $this->container);

        $services = [
            // legacy/swagger.xml
            'api_platform.swagger.normalizer.documentation',
            'api_platform.swagger.normalizer.api_gateway',
            'api_platform.swagger.command.swagger_command',

            // legacy/openapi.xml
            'api_platform.openapi.factory.legacy',

            // legacy/swagger_ui.xml
            'api_platform.swagger_ui.action',
        ];

        $tags = [
            // legacy/swagger.xml
            'api_platform.swagger.normalizer.documentation' => 'serializer.normalizer',
            'api_platform.swagger.normalizer.api_gateway' => 'serializer.normalizer',
            'api_platform.swagger.command.swagger_command' => 'console.command',
        ];

        $this->assertContainerHas($services, [], $tags);
    }

    public function testJsonApiConfiguration(): void
    {
        $config = self::DEFAULT_CONFIG;
        $config['api_platform']['formats']['jsonapi'] = [
            'mime_types' => ['application/vnd.api+json'],
        ];

        (new ApiPlatformExtension())->load($config, $this->container);

        $services = [
            // jsonapi.xml
            'api_platform.jsonapi.encoder',
            'api_platform.jsonapi.name_converter.reserved_attribute_name',
            'api_platform.jsonapi.normalizer.entrypoint',
            'api_platform.jsonapi.normalizer.collection',
            'api_platform.jsonapi.normalizer.item',
            'api_platform.jsonapi.normalizer.object',
            'api_platform.jsonapi.normalizer.constraint_violation_list',
            'api_platform.jsonapi.normalizer.error',
            'api_platform.jsonapi.listener.request.transform_pagination_parameters',
            'api_platform.jsonapi.listener.request.transform_sorting_parameters',
            'api_platform.jsonapi.listener.request.transform_fieldsets_parameters',
            'api_platform.jsonapi.listener.request.transform_filtering_parameters',
        ];

        $tags = [
            // jsonapi.xml
            'api_platform.jsonapi.encoder' => 'serializer.encoder',
            'api_platform.jsonapi.normalizer.entrypoint' => 'serializer.normalizer',
            'api_platform.jsonapi.normalizer.collection' => 'serializer.normalizer',
            'api_platform.jsonapi.normalizer.item' => 'serializer.normalizer',
            'api_platform.jsonapi.normalizer.object' => 'serializer.normalizer',
            'api_platform.jsonapi.normalizer.constraint_violation_list' => 'serializer.normalizer',
            'api_platform.jsonapi.normalizer.error' => 'serializer.normalizer',
            'api_platform.jsonapi.listener.request.transform_pagination_parameters' => 'kernel.event_listener',
            'api_platform.jsonapi.listener.request.transform_sorting_parameters' => 'kernel.event_listener',
            'api_platform.jsonapi.listener.request.transform_fieldsets_parameters' => 'kernel.event_listener',
            'api_platform.jsonapi.listener.request.transform_filtering_parameters' => 'kernel.event_listener',
        ];

        $this->assertContainerHas($services, [], $tags);
    }

    public function testJsonLdHydraConfiguration(): void
    {
        $config = self::DEFAULT_CONFIG;
        (new ApiPlatformExtension())->load($config, $this->container);

        $services = [
            // jsonld.xml
            'api_platform.jsonld.context_builder',
            'api_platform.jsonld.normalizer.item',
            'api_platform.jsonld.normalizer.object',
            'api_platform.jsonld.encoder',
            'api_platform.jsonld.action.context',

            // hydra.xml
            'api_platform.hydra.normalizer.documentation',
            'api_platform.hydra.listener.response.add_link_header',
            'api_platform.hydra.normalizer.constraint_violation_list',
            'api_platform.hydra.normalizer.entrypoint',
            'api_platform.hydra.normalizer.error',
            'api_platform.hydra.normalizer.collection',
            'api_platform.hydra.normalizer.partial_collection_view',
            'api_platform.hydra.normalizer.collection_filters',
            'api_platform.hydra.json_schema.schema_factory',
        ];

        $tags = [
            // jsonld.xml
            'api_platform.jsonld.normalizer.item' => 'serializer.normalizer',
            'api_platform.jsonld.normalizer.object' => 'serializer.normalizer',
            'api_platform.jsonld.encoder' => 'serializer.encoder',

            // hydra.xml
            'api_platform.hydra.normalizer.documentation' => 'serializer.normalizer',
            'api_platform.hydra.listener.response.add_link_header' => 'kernel.event_listener',
            'api_platform.hydra.normalizer.constraint_violation_list' => 'serializer.normalizer',
            'api_platform.hydra.normalizer.entrypoint' => 'serializer.normalizer',
            'api_platform.hydra.normalizer.error' => 'serializer.normalizer',
            'api_platform.hydra.normalizer.collection' => 'serializer.normalizer',
        ];

        $this->assertContainerHas($services, [], $tags);
    }

    public function testJsonHalConfiguration(): void
    {
        $config = self::DEFAULT_CONFIG;
        (new ApiPlatformExtension())->load($config, $this->container);

        $services = [
            // hal.xml
            'api_platform.hal.encoder',
            'api_platform.hal.normalizer.entrypoint',
            'api_platform.hal.normalizer.collection',
            'api_platform.hal.normalizer.item',
            'api_platform.hal.normalizer.object',
            'api_platform.hal.json_schema.schema_factory',
        ];

        $tags = [
            // hal.xml
            'api_platform.hal.encoder' => 'serializer.encoder',
            'api_platform.hal.normalizer.entrypoint' => 'serializer.normalizer',
            'api_platform.hal.normalizer.collection' => 'serializer.normalizer',
            'api_platform.hal.normalizer.item' => 'serializer.normalizer',
            'api_platform.hal.normalizer.object' => 'serializer.normalizer',
        ];

        $this->assertContainerHas($services, [], $tags);
    }

    public function testJsonProblemConfiguration(): void
    {
        $config = self::DEFAULT_CONFIG;
        (new ApiPlatformExtension())->load($config, $this->container);

        $services = [
            // problem.xml
            'api_platform.problem.encoder',
            'api_platform.problem.normalizer.constraint_violation_list',
            'api_platform.problem.normalizer.error',
        ];

        $tags = [
            // problem.xml
            'api_platform.problem.encoder' => 'serializer.encoder',
            'api_platform.problem.normalizer.constraint_violation_list' => 'serializer.normalizer',
            'api_platform.problem.normalizer.error' => 'serializer.normalizer',
        ];

        $this->assertContainerHas($services, [], $tags);
    }

    public function testGraphQlConfiguration(): void
    {
        $config = self::DEFAULT_CONFIG;
        $config['api_platform']['graphql']['enabled'] = true;
        (new ApiPlatformExtension())->load($config, $this->container);

        $services = [
            // graphql.xml
            'api_platform.graphql.executor',
            'api_platform.graphql.query_resolver_locator',
            'api_platform.graphql.mutation_resolver_locator',
            'api_platform.graphql.iterable_type',
            'api_platform.graphql.upload_type',
            'api_platform.graphql.type_locator',
            'api_platform.graphql.types_container',
            'api_platform.graphql.types_factory',
            'api_platform.graphql.fields_builder_locator',
            'api_platform.graphql.action.entrypoint',
            'api_platform.graphql.action.graphiql',
            'api_platform.graphql.action.graphql_playground',
            'api_platform.graphql.error_handler',
            'api_platform.graphql.subscription.subscription_identifier_generator',
            'api_platform.graphql.cache.subscription',
            'api_platform.graphql.command.export_command',

            // v3/graphql.xml
            'api_platform.graphql.resolver.stage.write',
            'api_platform.graphql.resolver.stage.read',
            'api_platform.graphql.type_converter',
            'api_platform.graphql.type_builder',
            'api_platform.graphql.fields_builder',
            'api_platform.graphql.schema_builder',
            'api_platform.graphql.serializer.context_builder',
            'api_platform.graphql.resolver.factory.item',
            'api_platform.graphql.resolver.factory.collection',
            'api_platform.graphql.resolver.factory.item_mutation',
            'api_platform.graphql.resolver.factory.item_subscription',
            'api_platform.graphql.resolver.stage.security',
            'api_platform.graphql.resolver.stage.security_post_denormalize',
            'api_platform.graphql.resolver.stage.security_post_validation',
            'api_platform.graphql.resolver.stage.serialize',
            'api_platform.graphql.resolver.stage.deserialize',
            'api_platform.graphql.resolver.stage.validate',
            'api_platform.graphql.resolver.resource_field',
            'api_platform.graphql.normalizer.item',
            'api_platform.graphql.normalizer.object',
            'api_platform.graphql.subscription.subscription_manager',
            'api_platform.graphql.normalizer.error',
            'api_platform.graphql.normalizer.validation_exception',
            'api_platform.graphql.normalizer.http_exception',
            'api_platform.graphql.normalizer.runtime_exception',
        ];

        $aliases = [
            // v3/graphql.xml
            'ApiPlatform\GraphQl\Serializer\SerializerContextBuilderInterface',
        ];

        $tags = [
            // graphql.xml
            'api_platform.graphql.query_resolver_locator' => 'container.service_locator',
            'api_platform.graphql.mutation_resolver_locator' => 'container.service_locator',
            'api_platform.graphql.iterable_type' => 'api_platform.graphql.type',
            'api_platform.graphql.upload_type' => 'api_platform.graphql.type',
            'api_platform.graphql.type_locator' => 'container.service_locator',
            'api_platform.graphql.fields_builder_locator' => 'container.service_locator',
            'api_platform.graphql.cache.subscription' => 'cache.pool',
            'api_platform.graphql.command.export_command' => 'console.command',

            // v3/graphql.xml
            'api_platform.graphql.normalizer.item' => 'serializer.normalizer',
            'api_platform.graphql.normalizer.object' => 'serializer.normalizer',
            'api_platform.graphql.normalizer.error' => 'serializer.normalizer',
            'api_platform.graphql.normalizer.validation_exception' => 'serializer.normalizer',
            'api_platform.graphql.normalizer.http_exception' => 'serializer.normalizer',
            'api_platform.graphql.normalizer.runtime_exception' => 'serializer.normalizer',
        ];

        $this->assertContainerHas($services, $aliases, $tags);
    }

    public function testGraphQlConfigurationMetadataBackwardCompatibilityLayer(): void
    {
        $config = self::DEFAULT_CONFIG;
        $config['api_platform']['graphql']['enabled'] = true;
        $config['api_platform']['metadata_backward_compatibility_layer'] = true;
        (new ApiPlatformExtension())->load($config, $this->container);

        $services = [
            // legacy/graphql.xml
            'api_platform.graphql.executor',
            'api_platform.graphql.resolver.factory.item',
            'api_platform.graphql.resolver.factory.collection',
            'api_platform.graphql.resolver.factory.item_mutation',
            'api_platform.graphql.resolver.factory.item_subscription',
            'api_platform.graphql.resolver.stage.read',
            'api_platform.graphql.resolver.stage.security',
            'api_platform.graphql.resolver.stage.security_post_denormalize',
            'api_platform.graphql.resolver.stage.serialize',
            'api_platform.graphql.resolver.stage.deserialize',
            'api_platform.graphql.resolver.stage.write',
            'api_platform.graphql.resolver.stage.validate',
            'api_platform.graphql.resolver.resource_field',
            'api_platform.graphql.query_resolver_locator',
            'api_platform.graphql.mutation_resolver_locator',
            'api_platform.graphql.iterable_type',
            'api_platform.graphql.upload_type',
            'api_platform.graphql.type_locator',
            'api_platform.graphql.types_container',
            'api_platform.graphql.type_converter',
            'api_platform.graphql.type_builder',
            'api_platform.graphql.fields_builder',
            'api_platform.graphql.fields_builder_locator',
            'api_platform.graphql.schema_builder',
            'api_platform.graphql.action.entrypoint',
            'api_platform.graphql.action.graphiql',
            'api_platform.graphql.action.graphql_playground',
            'api_platform.graphql.normalizer.item',
            'api_platform.graphql.normalizer.object',
            'api_platform.graphql.normalizer.error',
            'api_platform.graphql.normalizer.validation_exception',
            'api_platform.graphql.normalizer.http_exception',
            'api_platform.graphql.normalizer.runtime_exception',
            'api_platform.graphql.serializer.context_builder',
            'api_platform.graphql.subscription.subscription_manager',
            'api_platform.graphql.subscription.subscription_identifier_generator',
            'api_platform.graphql.cache.subscription',
            'api_platform.graphql.command.export_command',
        ];

        $tags = [
            // legacy/graphql.xml
            'api_platform.graphql.query_resolver_locator' => 'container.service_locator',
            'api_platform.graphql.mutation_resolver_locator' => 'container.service_locator',
            'api_platform.graphql.iterable_type' => 'api_platform.graphql.type',
            'api_platform.graphql.upload_type' => 'api_platform.graphql.type',
            'api_platform.graphql.type_locator' => 'container.service_locator',
            'api_platform.graphql.fields_builder_locator' => 'container.service_locator',
            'api_platform.graphql.normalizer.item' => 'serializer.normalizer',
            'api_platform.graphql.normalizer.object' => 'serializer.normalizer',
            'api_platform.graphql.normalizer.error' => 'serializer.normalizer',
            'api_platform.graphql.normalizer.validation_exception' => 'serializer.normalizer',
            'api_platform.graphql.normalizer.http_exception' => 'serializer.normalizer',
            'api_platform.graphql.normalizer.runtime_exception' => 'serializer.normalizer',
            'api_platform.graphql.cache.subscription' => 'cache.pool',
            'api_platform.graphql.command.export_command' => 'console.command',
        ];

        $this->assertContainerHas($services, [], $tags);
    }

    public function testLegacyBundlesConfigurationFosUserBundle(): void
    {
        $config = self::DEFAULT_CONFIG;
        $config['api_platform']['enable_fos_user'] = true;
        (new ApiPlatformExtension())->load($config, $this->container);

        if (!isset($bundles['FOSUserBundle'])) {
            $this->markTestSkipped('bundle FOSUserBundle does not exist');
        }
        $services = [
            // fos_user.xml
            'api_platform.fos_user.event_listener',
        ];

        $tags = [
            // fos_user.xml
            'api_platform.fos_user.event_listener' => 'kernel.event_listener',
        ];

        $this->assertContainerHas($services, [], $tags);
    }

    public function testLegacyBundlesConfigurationNelmioApiDocBundle(): void
    {
        $config = self::DEFAULT_CONFIG;
        $config['api_platform']['enable_nelmio_api_doc'] = true;
        (new ApiPlatformExtension())->load($config, $this->container);

        if (!isset($bundles['NelmioApiDocBundle'])) {
            $this->markTestSkipped('bundle NelmioApiDocBundle does not exist');
        }

        $services = [
            // nelmio_api_doc.xml
            'api_platform.nelmio_api_doc.annotations_provider',
            'api_platform.nelmio_api_doc.parser',
        ];

        $tags = [
            // nelmio_api_doc.xml
            'api_platform.nelmio_api_doc.annotations_provider' => 'nelmio_api_doc.extractor.annotations_provider',
            'api_platform.nelmio_api_doc.parser' => 'nelmio_api_doc.extractor.parser',
        ];

        $this->assertContainerHas($services, [], $tags);
    }

    public function testDoctrineOrmConfiguration(): void
    {
        $config = self::DEFAULT_CONFIG;
        $config['api_platform']['doctrine']['enabled'] = true;
        (new ApiPlatformExtension())->load($config, $this->container);

        $services = [
            // doctrine_orm.xml
            'api_platform.doctrine.metadata_factory',
            'api_platform.doctrine.orm.state.processor',
            'api_platform.doctrine.orm.state.collection_provider',
            'api_platform.doctrine.orm.state.item_provider',
            'api_platform.doctrine.orm.search_filter',
            'api_platform.doctrine.orm.order_filter',
            'api_platform.doctrine.orm.range_filter',
            'api_platform.doctrine.orm.query_extension.eager_loading',
            'api_platform.doctrine.orm.query_extension.filter',
            'api_platform.doctrine.orm.query_extension.filter_eager_loading',
            'api_platform.doctrine.orm.query_extension.pagination',
            'api_platform.doctrine.orm.query_extension.order',

            // legacy/doctrine_orm.xml
            'api_platform.doctrine.orm.data_persister',
            'api_platform.doctrine.orm.collection_data_provider',
            'api_platform.doctrine.orm.item_data_provider',
            'api_platform.doctrine.orm.subresource_data_provider',
            'api_platform.doctrine.orm.default.collection_data_provider',
            'api_platform.doctrine.orm.default.item_data_provider',
            'api_platform.doctrine.orm.default.subresource_data_provider',
            'api_platform.doctrine.orm.metadata.property.metadata_factory.legacy',
        ];

        $aliases = [
            // doctrine_orm.xml
            'ApiPlatform\Doctrine\Orm\Filter\OrderFilter',
            'ApiPlatform\Doctrine\Orm\Filter\RangeFilter',
            'ApiPlatform\Doctrine\Orm\Filter\DateFilter',
            'ApiPlatform\Doctrine\Orm\Filter\BooleanFilter',
            'ApiPlatform\Doctrine\Orm\Filter\NumericFilter',
            'ApiPlatform\Doctrine\Orm\Filter\ExistsFilter',
            'ApiPlatform\Doctrine\Orm\Extension\EagerLoadingExtension',
            'ApiPlatform\Doctrine\Orm\Extension\FilterExtension',
            'ApiPlatform\Doctrine\Orm\Extension\FilterEagerLoadingExtension',
            'ApiPlatform\Doctrine\Orm\Extension\PaginationExtension',
            'ApiPlatform\Doctrine\Orm\Extension\OrderExtension',
        ];

        $tags = [
            // doctrine_orm.xml
            'api_platform.doctrine.orm.state.processor' => 'api_platform.state_processor',
            'api_platform.doctrine.orm.state.collection_provider' => 'api_platform.state_provider',
            'api_platform.doctrine.orm.state.item_provider' => 'api_platform.state_provider',
            'api_platform.doctrine.orm.query_extension.eager_loading' => 'api_platform.doctrine.orm.query_extension.item',
            'api_platform.doctrine.orm.query_extension.eager_loading' => 'api_platform.doctrine.orm.query_extension.collection',
            'api_platform.doctrine.orm.query_extension.filter' => 'api_platform.doctrine.orm.query_extension.collection',
            'api_platform.doctrine.orm.query_extension.filter_eager_loading' => 'api_platform.doctrine.orm.query_extension.collection',
            'api_platform.doctrine.orm.query_extension.pagination' => 'api_platform.doctrine.orm.query_extension.collection',
            'api_platform.doctrine.orm.query_extension.order' => 'api_platform.doctrine.orm.query_extension.collection',

            // legacy/doctrine_orm.xml
            'api_platform.doctrine.orm.data_persister' => 'api_platform.data_persister',
            'api_platform.doctrine.orm.default.collection_data_provider' => 'api_platform.collection_data_provider',
            'api_platform.doctrine.orm.default.item_data_provider' => 'api_platform.item_data_provider',
            'api_platform.doctrine.orm.default.subresource_data_provider' => 'api_platform.subresource_data_provider',
        ];

        $this->assertContainerHas($services, $aliases, $tags);
    }

    public function testDoctrineMongoDbOdmConfiguration(): void
    {
        $config = self::DEFAULT_CONFIG;
        $config['api_platform']['doctrine_mongodb_odm']['enabled'] = true;
        (new ApiPlatformExtension())->load($config, $this->container);

        $services = [
            // doctrine_mongo_odm.xml
            'api_platform.doctrine_mongodb.odm.default_document_manager.property_info_extractor',
            'api_platform.doctrine.metadata_factory',
            'api_platform.doctrine_mongodb.odm.state.processor',
            'api_platform.doctrine.odm.state.collection_provider',
            'api_platform.doctrine.odm.state.item_provider',
            'api_platform.doctrine_mongodb.odm.search_filter',
            'api_platform.doctrine_mongodb.odm.boolean_filter',
            'api_platform.doctrine_mongodb.odm.date_filter',
            'api_platform.doctrine_mongodb.odm.exists_filter',
            'api_platform.doctrine_mongodb.odm.numeric_filter',
            'api_platform.doctrine_mongodb.odm.order_filter',
            'api_platform.doctrine_mongodb.odm.range_filter',
            'api_platform.doctrine_mongodb.odm.metadata.property.metadata_factory',
            'api_platform.doctrine_mongodb.odm.aggregation_extension.filter',
            'api_platform.doctrine_mongodb.odm.aggregation_extension.pagination',
            'api_platform.doctrine_mongodb.odm.aggregation_extension.order',
            'api_platform.doctrine_mongodb.odm.metadata.property.identifier_metadata_factory',

            // legacy/doctrine_odm.xml
            'api_platform.doctrine_mongodb.odm.data_persister',
            'api_platform.doctrine_mongodb.odm.collection_data_provider',
            'api_platform.doctrine_mongodb.odm.item_data_provider',
            'api_platform.doctrine_mongodb.odm.subresource_data_provider',
            'api_platform.doctrine_mongodb.odm.default.collection_data_provider',
            'api_platform.doctrine_mongodb.odm.default.item_data_provider',
            'api_platform.doctrine_mongodb.odm.default.subresource_data_provider',
        ];

        $aliases = [
            // doctrine_mongo_odm.xml
            'ApiPlatform\Doctrine\Odm\Filter\SearchFilter',
            'ApiPlatform\Doctrine\Odm\Filter\BooleanFilter',
            'ApiPlatform\Doctrine\Odm\Filter\DateFilter',
            'ApiPlatform\Doctrine\Odm\Filter\ExistsFilter',
            'ApiPlatform\Doctrine\Odm\Filter\NumericFilter',
            'ApiPlatform\Doctrine\Odm\Filter\OrderFilter',
            'ApiPlatform\Doctrine\Odm\Filter\RangeFilter',
            'ApiPlatform\Doctrine\Odm\Extension\FilterExtension',
            'ApiPlatform\Doctrine\Odm\Extension\PaginationExtension',
            'ApiPlatform\Doctrine\Odm\Extension\OrderExtension',
        ];

        $tags = [
            // doctrine_mongo_odm.xml
            'api_platform.doctrine_mongodb.odm.default_document_manager.property_info_extractor' => 'property_info.list_extractor',
            'api_platform.doctrine_mongodb.odm.default_document_manager.property_info_extractor' => 'property_info.type_extractor',
            'api_platform.doctrine_mongodb.odm.state.processor' => 'api_platform.state_processor',
            'api_platform.doctrine.odm.state.collection_provider' => 'api_platform.state_provider',
            'api_platform.doctrine.odm.state.item_provider' => 'api_platform.state_provider',
            'api_platform.doctrine_mongodb.odm.aggregation_extension.filter' => 'api_platform.doctrine_mongodb.odm.aggregation_extension.collection',
            'api_platform.doctrine_mongodb.odm.aggregation_extension.pagination' => 'api_platform.doctrine_mongodb.odm.aggregation_extension.collection',
            'api_platform.doctrine_mongodb.odm.aggregation_extension.order' => 'api_platform.doctrine_mongodb.odm.aggregation_extension.collection',

            // legacy/doctrine_odm.xml
            'api_platform.doctrine_mongodb.odm.data_persister' => 'api_platform.data_persister',
            'api_platform.doctrine_mongodb.odm.default.collection_data_provider' => 'api_platform.collection_data_provider',
            'api_platform.doctrine_mongodb.odm.default.item_data_provider' => 'api_platform.item_data_provider',
            'api_platform.doctrine_mongodb.odm.default.subresource_data_provider' => 'api_platform.subresource_data_provider',
        ];

        $this->assertContainerHas($services, $aliases, $tags);
    }

    public function testHttpCacheConfiguration(): void
    {
        $config = self::DEFAULT_CONFIG;
        $config['api_platform']['doctrine']['enabled'] = true;
        (new ApiPlatformExtension())->load($config, $this->container);

        $services = [
            // http_cache.xml
            'api_platform.http_cache.listener.response.configure',

            // doctrine_orm_http_cache_purger.xml
            'api_platform.doctrine.listener.http_cache.purge',

            // http_cache_tags.xml
            'api_platform.http_cache.purger.varnish_client',
            'api_platform.http_cache.purger.varnish.xkey',
            'api_platform.http_cache.purger.varnish.ban',
            'api_platform.http_cache.listener.response.add_tags',
        ];

        $tags = [
            // http_cache.xml
            'api_platform.http_cache.listener.response.configure' => 'kernel.event_listener',

            // doctrine_orm_http_cache_purger.xml
            'api_platform.doctrine.listener.http_cache.purge' => 'doctrine.event_listener',

            // http_cache_tags.xml
            'api_platform.http_cache.listener.response.add_tags' => 'kernel.event_listener',
        ];

        $this->assertTrue($this->container->hasAlias('api_platform.http_cache.purger.varnish'));

        $this->assertSame([
            ['event' => 'preUpdate'],
            ['event' => 'onFlush'],
            ['event' => 'postFlush'],
        ], $this->container->getDefinition('api_platform.doctrine.listener.http_cache.purge')->getTag('doctrine.event_listener'));
    }

    public function testValidatorConfiguration(): void
    {
        if (!interface_exists(ValidatorInterface::class)) {
            $this->markTestSkipped('interface Symfony\Component\Validator\Validator\ValidatorInterface does not exist');
        }

        $config = self::DEFAULT_CONFIG;
        (new ApiPlatformExtension())->load($config, $this->container);

        $services = [
            // metadata/validator.xml
            'api_platform.metadata.property.metadata_factory.validator',
            'api_platform.metadata.property_schema.choice_restriction',
            'api_platform.metadata.property_schema.collection_restriction',
            'api_platform.metadata.property_schema.count_restriction',
            'api_platform.metadata.property_schema.greater_than_or_equal_restriction',
            'api_platform.metadata.property_schema.greater_than_restriction',
            'api_platform.metadata.property_schema.length_restriction',
            'api_platform.metadata.property_schema.less_than_or_equal_restriction',
            'api_platform.metadata.property_schema.less_than_restriction',
            'api_platform.metadata.property_schema.one_of_restriction',
            'api_platform.metadata.property_schema.range_restriction',
            'api_platform.metadata.property_schema.regex_restriction',
            'api_platform.metadata.property_schema.format_restriction',
            'api_platform.metadata.property_schema.unique_restriction',

            // symfony/validator.xml
            'api_platform.validator',
            'api_platform.listener.view.validate',
            'api_platform.validator.query_parameter_validator',
            'api_platform.listener.view.validate_query_parameters',
        ];

        $aliases = [
            // symfony/validator.xml
            'ApiPlatform\Validator\ValidatorInterface',
        ];

        $tags = [
            // metadata/validator.xml
            'api_platform.metadata.property_schema.choice_restriction' => 'api_platform.metadata.property_schema_restriction',
            'api_platform.metadata.property_schema.collection_restriction' => 'api_platform.metadata.property_schema_restriction',
            'api_platform.metadata.property_schema.count_restriction' => 'api_platform.metadata.property_schema_restriction',
            'api_platform.metadata.property_schema.greater_than_or_equal_restriction' => 'api_platform.metadata.property_schema_restriction',
            'api_platform.metadata.property_schema.greater_than_restriction' => 'api_platform.metadata.property_schema_restriction',
            'api_platform.metadata.property_schema.length_restriction' => 'api_platform.metadata.property_schema_restriction',
            'api_platform.metadata.property_schema.less_than_or_equal_restriction' => 'api_platform.metadata.property_schema_restriction',
            'api_platform.metadata.property_schema.less_than_restriction' => 'api_platform.metadata.property_schema_restriction',
            'api_platform.metadata.property_schema.one_of_restriction' => 'api_platform.metadata.property_schema_restriction',
            'api_platform.metadata.property_schema.range_restriction' => 'api_platform.metadata.property_schema_restriction',
            'api_platform.metadata.property_schema.regex_restriction' => 'api_platform.metadata.property_schema_restriction',
            'api_platform.metadata.property_schema.format_restriction' => 'api_platform.metadata.property_schema_restriction',
            'api_platform.metadata.property_schema.unique_restriction' => 'api_platform.metadata.property_schema_restriction',

            // symfony/validator.xml
            'api_platform.listener.view.validate' => 'kernel.event_listener',
            'api_platform.listener.view.validate_query_parameters' => 'kernel.event_listener',
        ];

        $this->assertContainerHas($services, $aliases, $tags);
    }

    public function testValidatorConfigurationMetadataBackwardCompatibilityLayer(): void
    {
        if (!interface_exists(ValidatorInterface::class)) {
            $this->markTestSkipped('interface Symfony\Component\Validator\Validator\ValidatorInterface does not exist');
        }

        $config = self::DEFAULT_CONFIG;
        $config['api_platform']['metadata_backward_compatibility_layer'] = true;
        (new ApiPlatformExtension())->load($config, $this->container);

        $services = [
            // legacy/validator.xml
            'api_platform.metadata.property.metadata_factory.validator.legacy',
            'api_platform.metadata.property_schema.choice_restriction',
            'api_platform.metadata.property_schema.collection_restriction',
            'api_platform.metadata.property_schema.count_restriction',
            'api_platform.metadata.property_schema.greater_than_or_equal_restriction',
            'api_platform.metadata.property_schema.greater_than_restriction',
            'api_platform.metadata.property_schema.length_restriction',
            'api_platform.metadata.property_schema.less_than_or_equal_restriction',
            'api_platform.metadata.property_schema.less_than_restriction',
            'api_platform.metadata.property_schema.one_of_restriction',
            'api_platform.metadata.property_schema.range_restriction',
            'api_platform.metadata.property_schema.regex_restriction',
            'api_platform.metadata.property_schema.format_restriction',
            'api_platform.metadata.property_schema.unique_restriction',
            'api_platform.validator',
            'api_platform.listener.view.validate',
            'api_platform.validator.query_parameter_validator',
            'api_platform.listener.view.validate_query_parameters',
        ];

        $aliases = [
            // legacy/validator.xml
            'ApiPlatform\Validator\ValidatorInterface',
            'ApiPlatform\Core\Validator\ValidatorInterface',
        ];

        $tags = [
            // legacy/validator.xml
            'api_platform.metadata.property_schema.choice_restriction' => 'api_platform.metadata.property_schema_restriction',
            'api_platform.metadata.property_schema.collection_restriction' => 'api_platform.metadata.property_schema_restriction',
            'api_platform.metadata.property_schema.count_restriction' => 'api_platform.metadata.property_schema_restriction',
            'api_platform.metadata.property_schema.greater_than_or_equal_restriction' => 'api_platform.metadata.property_schema_restriction',
            'api_platform.metadata.property_schema.greater_than_restriction' => 'api_platform.metadata.property_schema_restriction',
            'api_platform.metadata.property_schema.length_restriction' => 'api_platform.metadata.property_schema_restriction',
            'api_platform.metadata.property_schema.less_than_or_equal_restriction' => 'api_platform.metadata.property_schema_restriction',
            'api_platform.metadata.property_schema.less_than_restriction' => 'api_platform.metadata.property_schema_restriction',
            'api_platform.metadata.property_schema.one_of_restriction' => 'api_platform.metadata.property_schema_restriction',
            'api_platform.metadata.property_schema.range_restriction' => 'api_platform.metadata.property_schema_restriction',
            'api_platform.metadata.property_schema.regex_restriction' => 'api_platform.metadata.property_schema_restriction',
            'api_platform.metadata.property_schema.format_restriction' => 'api_platform.metadata.property_schema_restriction',
            'api_platform.metadata.property_schema.unique_restriction' => 'api_platform.metadata.property_schema_restriction',
            'api_platform.listener.view.validate' => 'kernel.event_listener',
            'api_platform.listener.view.validate_query_parameters' => 'kernel.event_listener',
        ];

        $this->assertContainerHas($services, $aliases, $tags);
    }

    public function testDataCollectorConfiguration(): void
    {
        $config = self::DEFAULT_CONFIG;
        $config['api_platform']['enable_profiler'] = true;
        $this->container->setParameter('kernel.debug', true);
        (new ApiPlatformExtension())->load($config, $this->container);

        $services = [
            // v3/data_collector.xml
            'api_platform.data_collector.request',

            // debug.xml
            'debug.var_dumper.cloner',
            'debug.var_dumper.cli_dumper',

            // v3/debug.xml
            'debug.api_platform.debug_resource.command',
            'debug.api_platform.processor',
        ];

        $tags = [
            // v3/data_collector.xml
            'api_platform.data_collector.request' => 'data_collector',

            // v3/debug.xml
            'debug.api_platform.debug_resource.command' => 'console.command',
        ];

        $this->assertContainerHas($services, [], $tags);
    }

    public function testDataCollectorConfigurationMetadataBackwardCompatibilityLayer(): void
    {
        $config = self::DEFAULT_CONFIG;
        $config['api_platform']['enable_profiler'] = true;
        $config['api_platform']['metadata_backward_compatibility_layer'] = true;
        $this->container->setParameter('kernel.debug', true);
        (new ApiPlatformExtension())->load($config, $this->container);

        $services = [
            // legacy/data_collector.xml
            'api_platform.data_collector.request',

            // legacy/debug.xml
            'debug.api_platform.collection_data_provider',
            'debug.api_platform.item_data_provider',
            'debug.api_platform.subresource_data_provider',
            'debug.api_platform.data_persister',
        ];

        $tags = [
            // legacy/data_collector.xml
            'api_platform.data_collector.request' => 'data_collector',
        ];

        $this->assertContainerHas($services, [], $tags);
    }

    public function testMercureConfiguration(): void
    {
        $config = self::DEFAULT_CONFIG;
        $config['api_platform']['mercure']['enabled'] = true;
        $config['api_platform']['doctrine']['enabled'] = true;
        $config['api_platform']['doctrine_mongodb_odm']['enabled'] = true;
        $config['api_platform']['graphql']['enabled'] = true;
        (new ApiPlatformExtension())->load($config, $this->container);

        $services = [
            // mercure.xml
            'api_platform.mercure.listener.response.add_link_header',

            // v3/doctrine_orm_mercure_publisher
            'api_platform.doctrine.orm.listener.mercure.publish',

            // v3/doctrine_odm_mercure_publisher.xml
            'api_platform.doctrine_mongodb.odm.listener.mercure.publish',

            // v3/graphql_mercure.xml
            'api_platform.graphql.subscription.mercure_iri_generator',
        ];

        $tags = [
            // mercure.xml
            'api_platform.mercure.listener.response.add_link_header' => 'kernel.event_listener',

            // v3/doctrine_orm_mercure_publisher
            'api_platform.doctrine.orm.listener.mercure.publish' => 'doctrine.event_listener',

            // v3/doctrine_odm_mercure_publisher.xml
            'api_platform.doctrine_mongodb.odm.listener.mercure.publish' => 'doctrine_mongodb.odm.event_listener',
        ];

        $this->assertContainerHas($services, [], $tags);

        $this->assertSame([
            ['event' => 'onFlush'],
            ['event' => 'postFlush'],
        ], $this->container->getDefinition('api_platform.doctrine.orm.listener.mercure.publish')->getTag('doctrine.event_listener'));

        $this->assertSame([
            ['event' => 'onFlush'],
            ['event' => 'postFlush'],
        ], $this->container->getDefinition('api_platform.doctrine_mongodb.odm.listener.mercure.publish')->getTag('doctrine_mongodb.odm.event_listener'));
    }

    public function testMercureConfigurationMetadataBackwardCompatibilityLayer(): void
    {
        $config = self::DEFAULT_CONFIG;
        $config['api_platform']['mercure']['enabled'] = true;
        $config['api_platform']['doctrine']['enabled'] = true;
        $config['api_platform']['doctrine_mongodb_odm']['enabled'] = true;
        $config['api_platform']['graphql']['enabled'] = true;
        (new ApiPlatformExtension())->load($config, $this->container);

        $services = [
            // legacy/doctrine_orm_mercure_publisher.xml
            'api_platform.doctrine.orm.listener.mercure.publish',

            // legacy/doctrine_odm_mercure_publisher.xml
            'api_platform.doctrine_mongodb.odm.listener.mercure.publish',

            // legacy/graphql_mercure.xml
            'api_platform.graphql.subscription.mercure_iri_generator',
        ];

        $tags = [
            // legacy/doctrine_orm_mercure_publisher.xml
            'api_platform.doctrine.orm.listener.mercure.publish' => 'doctrine.event_listener',

            // legacy/doctrine_odm_mercure_publisher.xml
            'api_platform.doctrine_mongodb.odm.listener.mercure.publish' => 'doctrine_mongodb.odm.event_listener',
        ];
        $this->assertContainerHas($services, [], $tags);

        $this->assertSame([
            ['event' => 'onFlush'],
            ['event' => 'postFlush'],
        ], $this->container->getDefinition('api_platform.doctrine.orm.listener.mercure.publish')->getTag('doctrine.event_listener'));

        $this->assertSame([
            ['event' => 'onFlush'],
            ['event' => 'postFlush'],
        ], $this->container->getDefinition('api_platform.doctrine_mongodb.odm.listener.mercure.publish')->getTag('doctrine_mongodb.odm.event_listener'));
    }

    public function testMessengerConfiguration(): void
    {
        $config = self::DEFAULT_CONFIG;
        (new ApiPlatformExtension())->load($config, $this->container);

        $services = [
            // messenger.xml
            'api_platform.messenger.data_persister',
            'api_platform.messenger.processor',
            'api_platform.messenger.data_transformer',
        ];

        $aliases = [
            // messenger.xml
            'api_platform.message_bus',
        ];

        $tags = [
            // messenger.xml
            'api_platform.messenger.data_persister' => 'api_platform.data_persister',
            'api_platform.messenger.processor' => 'api_platform.state_processor',
            'api_platform.messenger.data_transformer' => 'api_platform.data_transformer',
        ];

        $this->assertContainerHas($services, $aliases, $tags);
    }

    public function testElasticsearchConfiguration(): void
    {
        $config = self::DEFAULT_CONFIG;
        $config['api_platform']['elasticsearch']['enabled'] = true;
        (new ApiPlatformExtension())->load($config, $this->container);

        $services = [
            // elasticsearch.xml
            'api_platform.elasticsearch.client',
            'api_platform.elasticsearch.metadata.resource.metadata_factory.operation',
            'api_platform.elasticsearch.cache.metadata.document',
            'api_platform.elasticsearch.metadata.document.metadata_factory.configured',
            'api_platform.elasticsearch.metadata.document.metadata_factory.attribute',
            'api_platform.elasticsearch.metadata.document.metadata_factory.cat',
            'api_platform.elasticsearch.metadata.document.metadata_factory.cached',
            'api_platform.elasticsearch.identifier_extractor',
            'api_platform.elasticsearch.name_converter.inner_fields',
            'api_platform.elasticsearch.normalizer.item',
            'api_platform.elasticsearch.normalizer.document',
            'api_platform.elasticsearch.state.item_provider',
            'api_platform.elasticsearch.item_data_provider',
            'api_platform.elasticsearch.state.collection_provider',
            'api_platform.elasticsearch.collection_data_provider',
            'api_platform.elasticsearch.request_body_search_extension.filter',
            'api_platform.elasticsearch.request_body_search_extension.constant_score_filter',
            'api_platform.elasticsearch.request_body_search_extension.sort_filter',
            'api_platform.elasticsearch.request_body_search_extension.sort',
            'api_platform.elasticsearch.search_filter',
            'api_platform.elasticsearch.term_filter',
            'api_platform.elasticsearch.match_filter',
            'api_platform.elasticsearch.order_filter',
        ];

        $aliases = [
            // elasticsearch.xml
            'api_platform.elasticsearch.metadata.document.metadata_factory',
            'ApiPlatform\Elasticsearch\Metadata\Document\Factory\DocumentMetadataFactoryInterface',
            'ApiPlatform\Core\Bridge\Elasticsearch\Api\IdentifierExtractorInterface',
            'ApiPlatform\Elasticsearch\Filter\TermFilter',
            'ApiPlatform\Elasticsearch\Filter\MatchFilter',
            'ApiPlatform\Elasticsearch\Filter\OrderFilter',
        ];

        $tags = [
            // elasticsearch.xml
            'api_platform.elasticsearch.cache.metadata.document' => 'cache.pool',
            'api_platform.elasticsearch.normalizer.document' => 'serializer.normalizer',
            'api_platform.elasticsearch.state.item_provider' => 'api_platform.state_provider',
            'api_platform.elasticsearch.item_data_provider' => 'api_platform.item_data_provider',
            'api_platform.elasticsearch.state.collection_provider' => 'api_platform.state_provider',
            'api_platform.elasticsearch.collection_data_provider' => 'api_platform.collection_data_provider',
            'api_platform.elasticsearch.request_body_search_extension.constant_score_filter' => 'api_platform.elasticsearch.request_body_search_extension.collection',
            'api_platform.elasticsearch.request_body_search_extension.sort_filter' => 'api_platform.elasticsearch.request_body_search_extension.collection',
            'api_platform.elasticsearch.request_body_search_extension.sort' => 'api_platform.elasticsearch.request_body_search_extension.collection',
        ];

        $this->assertContainerHas($services, $aliases, $tags);

        $autoconfiguredInstances = $this->container->getAutoconfiguredInstanceof();
        $this->assertArrayHasKey(RequestBodySearchCollectionExtensionInterface::class, $autoconfiguredInstances);
        $this->assertArrayHasKey('api_platform.elasticsearch.request_body_search_extension.collection', $autoconfiguredInstances[RequestBodySearchCollectionExtensionInterface::class]->getTags());
    }

    public function testSecurityConfiguration(): void
    {
        $config = self::DEFAULT_CONFIG;
        (new ApiPlatformExtension())->load($config, $this->container);

        $services = [
            // security.xml
            'api_platform.security.resource_access_checker',
            'api_platform.security.listener.request.deny_access',
            'api_platform.security.expression_language_provider',
        ];

        $aliases = [
            // security.xml
            'api_platform.security.expression_language',
            'ApiPlatform\Symfony\Security\ResourceAccessCheckerInterface',
        ];

        $tags = [
            // security.xml
            'api_platform.security.listener.request.deny_access' => 'kernel.event_listener',
            'api_platform.security.expression_language_provider' => 'security.expression_language_provider',
        ];

        $this->assertContainerHas($services, $aliases, $tags);

        $this->assertSame([
            ['event' => 'kernel.request', 'method' => 'onSecurity', 'priority' => 3],
            ['event' => 'kernel.request', 'method' => 'onSecurityPostDenormalize', 'priority' => 1],
            ['event' => 'kernel.view', 'method' => 'onSecurityPostValidation', 'priority' => 63],
        ], $this->container->getDefinition('api_platform.security.listener.request.deny_access')->getTag('kernel.event_listener'));
    }

    public function testMakerConfiguration(): void
    {
        $config = self::DEFAULT_CONFIG;
        $config['api_platform']['maker']['enabled'] = true;
        (new ApiPlatformExtension())->load($config, $this->container);

        $services = [
            // maker.xml
            'api_platform.maker.command.data_provider',
            'api_platform.maker.command.data_persister',
        ];

        $tags = [
            // maker.xml
            'api_platform.maker.command.data_provider' => 'maker.command',
            'api_platform.maker.command.data_persister' => 'maker.command',
        ];

        $this->assertContainerHas($services, [], $tags);
    }

    public function testArgumentResolverConfiguration(): void
    {
        $config = self::DEFAULT_CONFIG;
        (new ApiPlatformExtension())->load($config, $this->container);

        $services = [
            // argument_resolver.xml
            'api_platform.argument_resolver.payload',
        ];

        $tags = [
            // argument_resolver.xml
            'api_platform.argument_resolver.payload' => 'controller.argument_value_resolver',
        ];

        $this->assertContainerHas($services, [], $tags);
    }

    public function testLegacyServices(): void
    {
        $config = self::DEFAULT_CONFIG;
        (new ApiPlatformExtension())->load($config, $this->container);

        $services = [
            // legacy/identifiers.xml
            'api_platform.identifiers_extractor.legacy',
            'api_platform.identifiers_extractor.cached',
            'api_platform.cache.identifiers_extractor',
            'api_platform.identifier.converter',

            // symfony.xml
            'api_platform.listener.request.add_format',
            'api_platform.listener.request.read',
            'api_platform.listener.view.write',
            'api_platform.listener.request.deserialize',
            'api_platform.listener.view.serialize',
            'api_platform.listener.view.respond',
            'api_platform.listener.exception.validation',
            'api_platform.listener.exception',
            'api_platform.cache_warmer.cache_pool_clearer',
        ];

        $aliases = [
            // legacy/identifiers.xml
            'ApiPlatform\Core\Api\IdentifiersExtractorInterface',
        ];

        $tags = [
            // legacy/identifiers.xml
            'api_platform.cache.identifiers_extractor' => 'cache.pool',

            // symfony.xml
            'api_platform.listener.request.add_format' => 'kernel.event_listener',
            'api_platform.listener.request.read' => 'kernel.event_listener',
            'api_platform.listener.view.write' => 'kernel.event_listener',
            'api_platform.listener.request.deserialize' => 'kernel.event_listener',
            'api_platform.listener.view.serialize' => 'kernel.event_listener',
            'api_platform.listener.view.respond' => 'kernel.event_listener',
            'api_platform.listener.exception.validation' => 'kernel.event_listener',
            'api_platform.listener.exception' => 'kernel.event_listener',
            'api_platform.listener.exception' => 'monolog.logger',
            'api_platform.cache_warmer.cache_pool_clearer' => 'kernel.cache_warmer',
        ];

        $this->assertContainerHas($services, $aliases, $tags);
    }

    public function testLegacyServicesMetadataBackwardCompatibilityLayer(): void
    {
        $config = self::DEFAULT_CONFIG;
        $config['api_platform']['metadata_backward_compatibility_layer'] = true;
        (new ApiPlatformExtension())->load($config, $this->container);

        $services = [
            // legacy/api.xml
            'api_platform.operation_method_resolver',
            'api_platform.formats_provider',
            'api_platform.route_loader.legacy',
            'api_platform.operation_path_resolver.router',
            'api_platform.iri_converter.legacy',
            'api_platform.listener.request.add_format',
            'api_platform.listener.request.deserialize',
            'api_platform.listener.view.serialize',
            'api_platform.listener.view.respond',
            'api_platform.listener.exception.validation',
            'api_platform.listener.exception',
            'api_platform.identifier.integer',
            'api_platform.identifier.date_normalizer',
            'api_platform.operation_path_resolver.underscore',
            'api_platform.operation_path_resolver.dash',
            'api_platform.listener.view.write.legacy',
            'api_platform.listener.request.read.legacy',
            'api_platform.operation_path_resolver.router',
        ];

        $aliases = [
            // legacy/api.xml
            'api_platform.operation_path_resolver',
            'api_platform.metadata.resource.metadata_collection_factory.retro_compatible',
            'ApiPlatform\Core\Api\OperationAwareFormatsProviderInterface',
            'ApiPlatform\Core\Api\IriConverterInterface',
            'api_platform.operation_path_resolver.legacy',
        ];

        $tags = [
            // legacy/api.xml
            'api_platform.route_loader.legacy' => 'routing.loader',
            'api_platform.listener.request.add_format' => 'kernel.event_listener',
            'api_platform.listener.request.deserialize' => 'kernel.event_listener',
            'api_platform.listener.view.serialize' => 'kernel.event_listener',
            'api_platform.listener.view.respond' => 'kernel.event_listener',
            'api_platform.listener.exception.validation' => 'kernel.event_listener',
            'api_platform.listener.exception' => 'kernel.event_listener',
            'api_platform.listener.exception' => 'monolog.logger',
            'api_platform.identifier.integer' => 'api_platform.identifier.denormalizer',
            'api_platform.identifier.date_normalizer' => 'api_platform.identifier.denormalizer',
            'api_platform.listener.view.write.legacy' => 'kernel.event_listener',
            'api_platform.listener.request.read.legacy' => 'kernel.event_listener',
        ];

        $this->assertContainerHas($services, $aliases, $tags);
    }

    public function testRectorConfiguration(): void
    {
        $config = self::DEFAULT_CONFIG;
        $config['api_platform']['metadata_backward_compatibility_layer'] = true;
        (new ApiPlatformExtension())->load($config, $this->container);

        $services = [
            // legacy/upgrade.xml
            'api_platform.upgrade.subresource_transformer',
            'api_platform.upgrade_resource.command',
        ];

        $tags = [
            'api_platform.upgrade_resource.command' => 'console.command',
        ];

        $this->assertContainerHas($services, [], $tags);
    }

    public function testAutoConfigurableInterfaces(): void
    {
        $config = self::DEFAULT_CONFIG;
        (new ApiPlatformExtension())->load($config, $this->container);

        $interfaces = [
            FilterInterface::class => 'api_platform.filter',
            ProviderInterface::class => 'api_platform.state_provider',
            ProcessorInterface::class => 'api_platform.state_processor',
            DataPersisterInterface::class => 'api_platform.data_persister',
            ItemDataProviderInterface::class => 'api_platform.item_data_provider',
            CollectionDataProviderInterface::class => 'api_platform.collection_data_provider',
            SubresourceDataProviderInterface::class => 'api_platform.subresource_data_provider',
            DataTransformerInterface::class => 'api_platform.data_transformer',
            DataTransformerInitializerInterface::class => 'api_platform.data_transformer',
            ValidationGroupsGeneratorInterface::class => 'api_platform.validation_groups_generator',
            PropertySchemaRestrictionMetadataInterface::class => 'api_platform.metadata.property_schema_restriction',
            QueryItemResolverInterface::class => 'api_platform.graphql.query_resolver',
            QueryCollectionResolverInterface::class => 'api_platform.graphql.query_resolver',
            MutationResolverInterface::class => 'api_platform.graphql.mutation_resolver',
            GraphQlTypeInterface::class => 'api_platform.graphql.type',
            ErrorHandlerInterface::class => 'api_platform.graphql.error_handler',
            QueryItemExtensionInterface::class => 'api_platform.doctrine.orm.query_extension.item',
            DoctrineQueryCollectionExtensionInterface::class => 'api_platform.doctrine.orm.query_extension.collection',
            AggregationItemExtensionInterface::class => 'api_platform.doctrine_mongodb.odm.aggregation_extension.item',
            AggregationCollectionExtensionInterface::class => 'api_platform.doctrine_mongodb.odm.aggregation_extension.collection',
        ];

        $has = [];
        foreach ($this->container->getAutoconfiguredInstanceof() as $interface => $childDefinition) {
            if (isset($interfaces[$interface])) {
                $has[] = $interface;
                $this->assertArrayHasKey($interfaces[$interface], $childDefinition->getTags());
            }
        }

        $this->assertEmpty(array_diff(array_keys($interfaces), $has), 'Not all expected interfaces are autoconfigurable.');
    }
}
