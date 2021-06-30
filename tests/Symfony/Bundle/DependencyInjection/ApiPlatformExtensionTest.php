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

use ApiPlatform\Action\NotFoundAction;
use ApiPlatform\Api\FilterInterface;
use ApiPlatform\Api\IdentifiersExtractorInterface;
use ApiPlatform\Api\IriConverterInterface;
use ApiPlatform\Api\ResourceClassResolverInterface;
use ApiPlatform\Api\UrlGeneratorInterface;
use ApiPlatform\Doctrine\Common\State\PersistProcessor;
use ApiPlatform\Doctrine\Common\State\RemoveProcessor;
use ApiPlatform\Doctrine\Odm\Extension\AggregationCollectionExtensionInterface;
use ApiPlatform\Doctrine\Odm\Extension\AggregationItemExtensionInterface;
use ApiPlatform\Doctrine\Odm\State\CollectionProvider as MongoDbCollectionProvider;
use ApiPlatform\Doctrine\Odm\State\ItemProvider as MongoDbItemProvider;
use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface as DoctrineQueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\State\CollectionProvider;
use ApiPlatform\Doctrine\Orm\State\ItemProvider;
use ApiPlatform\Elasticsearch\Extension\RequestBodySearchCollectionExtensionInterface;
use ApiPlatform\Elasticsearch\Filter\MatchFilter;
use ApiPlatform\Elasticsearch\Filter\TermFilter;
use ApiPlatform\Elasticsearch\Metadata\Document\Factory\DocumentMetadataFactoryInterface;
use ApiPlatform\Exception\ExceptionInterface;
use ApiPlatform\Exception\FilterValidationException;
use ApiPlatform\Exception\InvalidArgumentException;
use ApiPlatform\GraphQl\Error\ErrorHandlerInterface;
use ApiPlatform\GraphQl\Resolver\MutationResolverInterface;
use ApiPlatform\GraphQl\Resolver\QueryCollectionResolverInterface;
use ApiPlatform\GraphQl\Resolver\QueryItemResolverInterface;
use ApiPlatform\GraphQl\Serializer\SerializerContextBuilderInterface as GraphQlSerializerContextBuilderInterface;
use ApiPlatform\GraphQl\Type\Definition\TypeInterface as GraphQlTypeInterface;
use ApiPlatform\JsonSchema\SchemaFactoryInterface;
use ApiPlatform\JsonSchema\TypeFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Options;
use ApiPlatform\OpenApi\Serializer\OpenApiNormalizer;
use ApiPlatform\Serializer\Filter\GroupFilter;
use ApiPlatform\Serializer\Filter\PropertyFilter;
use ApiPlatform\Serializer\SerializerContextBuilderInterface;
use ApiPlatform\State\Pagination\Pagination;
use ApiPlatform\State\Pagination\PaginationOptions;
use ApiPlatform\Symfony\Bundle\DependencyInjection\ApiPlatformExtension;
use ApiPlatform\Symfony\Messenger\Processor as MessengerProcessor;
use ApiPlatform\Symfony\Security\ResourceAccessCheckerInterface;
use ApiPlatform\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaRestrictionMetadataInterface;
use ApiPlatform\Symfony\Validator\ValidationGroupsGeneratorInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\TestBundle;
use ApiPlatform\Translation\ResourceTranslatorInterface;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\ORM\OptimisticLockException;
use phpDocumentor\Reflection\DocBlockFactoryInterface;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\AbstractUid;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ApiPlatformExtensionTest extends TestCase
{
    use ExpectDeprecationTrait;
    use ProphecyTrait;

    final public const DEFAULT_CONFIG = ['api_platform' => [
        'title' => 'title',
        'description' => 'description',
        'version' => 'version',
        'formats' => [
            'jsonld' => ['mime_types' => ['application/ld+json']],
            'jsonhal' => ['mime_types' => ['application/hal+json']],
        ],
        'http_cache' => ['invalidation' => [
            'enabled' => true,
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
            'extra_properties' => [],
            'url_generation_strategy' => UrlGeneratorInterface::ABS_URL,
        ],
        'collection' => [
            'exists_parameter_name' => 'exists',
            'order' => 'ASC',
            'order_parameter_name' => 'order',
            'order_nulls_comparison' => null,
            'pagination' => [
                'page_parameter_name' => 'page',
                'enabled_parameter_name' => 'pagination',
                'items_per_page_parameter_name' => 'itemsPerPage',
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
        'eager_loading' => [
            'enabled' => true,
            'max_joins' => 30,
            'force_eager' => true,
            'fetch_partial' => false,
        ],
        'asset_package' => null,
        'enable_entrypoint' => true,
        'enable_docs' => true,
        'graphql' => [
            'graphql_playground' => ['enabled' => false],
        ],
    ]];

    private ContainerBuilder $container;

    protected function setUp(): void
    {
        $containerParameterBag = new ParameterBag([
            'kernel.bundles' => [
                'DoctrineBundle' => DoctrineBundle::class,
                'SecurityBundle' => SecurityBundle::class,
                'TwigBundle' => TwigBundle::class,
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
            'kernel.environment' => 'test',
        ]);

        $this->container = new ContainerBuilder($containerParameterBag);
    }

    private function assertContainerHas(array $services, array $aliases = []): void
    {
        foreach ($services as $service) {
            $this->assertTrue($this->container->hasDefinition($service), sprintf('Definition "%s" not found.', $service));
        }

        foreach ($aliases as $alias) {
            $this->assertContainerHasAlias($alias);
        }
    }

    private function assertNotContainerHasService(string $service): void
    {
        $this->assertFalse($this->container->hasDefinition($service), sprintf('Service "%s" found.', $service));
    }

    private function assertContainerHasAlias(string $alias): void
    {
        $this->assertTrue($this->container->hasAlias($alias), sprintf('Alias "%s" not found.', $alias));
    }

    private function assertServiceHasTags(string $service, array $tags = []): void
    {
        $serviceTags = $this->container->getDefinition($service)->getTags();

        foreach ($tags as $tag) {
            $this->assertArrayHasKey($tag, $serviceTags, sprintf('Tag "%s" not found on the service "%s".', $tag, $service));
        }
    }

    public function testCommonConfiguration(): void
    {
        $config = self::DEFAULT_CONFIG;
        (new ApiPlatformExtension())->load($config, $this->container);

        $services = [
            'api_platform.action.documentation',
            'api_platform.action.entrypoint',
            'api_platform.action.exception',
            'api_platform.action.not_found',
            'api_platform.action.placeholder',
            'api_platform.api.identifiers_extractor',
            'api_platform.filter_locator',
            'api_platform.negotiator',
            'api_platform.pagination',
            'api_platform.pagination_options',
            'api_platform.path_segment_name_generator.dash',
            'api_platform.path_segment_name_generator.underscore',
            'api_platform.ramsey_uuid.uri_variables.transformer.uuid',
            'api_platform.resource_class_resolver',
            'api_platform.route_loader',
            'api_platform.router',
            'api_platform.serializer.context_builder',
            'api_platform.serializer.context_builder.filter',
            'api_platform.serializer.group_filter',
            'api_platform.serializer.mapping.class_metadata_factory',
            'api_platform.serializer.normalizer.item',
            'api_platform.serializer.property_filter',
            'api_platform.serializer.uuid_denormalizer',
            'api_platform.serializer_locator',
            'api_platform.symfony.iri_converter',
            'api_platform.uri_variables.converter',
            'api_platform.uri_variables.transformer.date_time',
            'api_platform.uri_variables.transformer.integer',
        ];

        $aliases = [
            NotFoundAction::class,
            IdentifiersExtractorInterface::class,
            IriConverterInterface::class,
            ResourceClassResolverInterface::class,
            UrlGeneratorInterface::class,
            GroupFilter::class,
            PropertyFilter::class,
            SerializerContextBuilderInterface::class,
            Pagination::class,
            PaginationOptions::class,
            'api_platform.action.delete_item',
            'api_platform.action.get_collection',
            'api_platform.action.get_item',
            'api_platform.action.patch_item',
            'api_platform.action.post_collection',
            'api_platform.action.put_item',
            'api_platform.identifiers_extractor',
            'api_platform.iri_converter',
            'api_platform.path_segment_name_generator',
            'api_platform.property_accessor',
            'api_platform.property_info',
            'api_platform.serializer',
        ];

        $this->assertContainerHas($services, $aliases);

        $this->assertServiceHasTags('api_platform.cache.route_name_resolver', ['cache.pool']);
        $this->assertServiceHasTags('api_platform.serializer.normalizer.item', ['serializer.normalizer']);
        $this->assertServiceHasTags('api_platform.serializer_locator', ['container.service_locator']);
        $this->assertServiceHasTags('api_platform.filter_locator', ['container.service_locator']);

        // ramsey_uuid.xml
        $this->assertServiceHasTags('api_platform.serializer.uuid_denormalizer', ['serializer.normalizer']);
        $this->assertServiceHasTags('api_platform.ramsey_uuid.uri_variables.transformer.uuid', ['api_platform.uri_variables.transformer']);

        // api.xml
        $this->assertServiceHasTags('api_platform.route_loader', ['routing.loader']);
        $this->assertServiceHasTags('api_platform.uri_variables.transformer.integer', ['api_platform.uri_variables.transformer']);
        $this->assertServiceHasTags('api_platform.uri_variables.transformer.date_time', ['api_platform.uri_variables.transformer']);
    }

    public function testCommonConfigurationAbstractUid(): void
    {
        if (!class_exists(AbstractUid::class)) {
            $this->markTestSkipped('class Symfony\Component\Uid\AbstractUid does not exist');
        }

        $config = self::DEFAULT_CONFIG;
        (new ApiPlatformExtension())->load($config, $this->container);

        $services = [
            'api_platform.symfony.uri_variables.transformer.ulid',
            'api_platform.symfony.uri_variables.transformer.uuid',
        ];

        $this->assertContainerHas($services, []);

        $this->assertServiceHasTags('api_platform.symfony.uri_variables.transformer.ulid', ['api_platform.uri_variables.transformer']);
        $this->assertServiceHasTags('api_platform.symfony.uri_variables.transformer.uuid', ['api_platform.uri_variables.transformer']);
    }

    public function dataProviderCommonConfigurationAliasNameConverter(): \Iterator
    {
        yield ['dummyValue', true];
        yield [null, false];
    }

    /**
     * @dataProvider dataProviderCommonConfigurationAliasNameConverter
     */
    public function testCommonConfigurationAliasNameConverter(?string $nameConverterConfig, bool $aliasIsExected): void
    {
        $config = self::DEFAULT_CONFIG;
        $config['api_platform']['name_converter'] = $nameConverterConfig;

        (new ApiPlatformExtension())->load($config, $this->container);

        $this->assertSame($aliasIsExected, $this->container->hasAlias('api_platform.name_converter'));
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

            // metadata/operation.xml
            'api_platform.metadata.operation.metadata_factory',
        ];

        $aliases = [
            // metadata/property_name.xml
            'api_platform.metadata.property.name_collection_factory',
            PropertyNameCollectionFactoryInterface::class,

            // metadata/property.xml
            'api_platform.metadata.property.identifier_metadata_factory',
            'api_platform.metadata.property.metadata_factory',

            // metadata/resource.xml
            'api_platform.metadata.resource.metadata_collection_factory',
            ResourceMetadataCollectionFactoryInterface::class,

            // metadata/resource_name.xml
            'api_platform.metadata.resource.name_collection_factory',
            ResourceNameCollectionFactoryInterface::class,
        ];

        $this->assertContainerHas($services, $aliases);

        // metadata/property.xml
        $this->assertServiceHasTags('api_platform.cache.metadata.property', ['cache.pool']);

        // metadata/resource.xml
        $this->assertServiceHasTags('api_platform.cache.metadata.resource_collection', ['cache.pool']);

        // metadata/resource_name.xml
        $this->assertServiceHasTags('api_platform.cache.metadata.resource', ['cache.pool']);
    }

    public function testMetadataConfigurationDocBlockFactoryInterface(): void
    {
        if (!class_exists(PhpDocParser::class) || !interface_exists(DocBlockFactoryInterface::class)) {
            $this->markTestSkipped('class PHPStan\PhpDocParser\Parser\PhpDocParser or phpDocumentor\Reflection\DocBlockFactoryInterface does not exist');
        }

        $config = self::DEFAULT_CONFIG;
        (new ApiPlatformExtension())->load($config, $this->container);

        $services = [
            // metadata/php_doc.xml
            'api_platform.metadata.resource.metadata_collection_factory.php_doc',
        ];

        $this->assertContainerHas($services, []);
    }

    public function testSwaggerConfiguration(): void
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
            'api_platform.openapi.factory',

            // swagger_ui.xml
            'api_platform.swagger.listener.ui',
            'api_platform.swagger_ui.context',
            'api_platform.swagger_ui.action',
        ];

        $aliases = [
            // json_schema.xml
            TypeFactoryInterface::class,
            SchemaFactoryInterface::class,

            // openapi.xml
            OpenApiNormalizer::class,
            Options::class,
            OpenApiFactoryInterface::class,

            // swagger_ui.xml
            'api_platform.swagger_ui.listener',
        ];

        $this->assertContainerHas($services, $aliases);

        // json_schema.xml
        $this->assertServiceHasTags('api_platform.json_schema.json_schema_generate_command', ['console.command']);

        // openapi.xml
        $this->assertServiceHasTags('api_platform.openapi.normalizer', ['serializer.normalizer']);
        $this->assertServiceHasTags('api_platform.openapi.command', ['console.command']);
        $this->assertServiceHasTags('api_platform.openapi.normalizer.api_gateway', ['serializer.normalizer']);

        // swagger_ui.xml
        $this->assertServiceHasTags('api_platform.swagger.listener.ui', ['kernel.event_listener']);
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

        $this->assertContainerHas($services, []);

        // jsonapi.xml
        $this->assertServiceHasTags('api_platform.jsonapi.encoder', ['serializer.encoder']);
        $this->assertServiceHasTags('api_platform.jsonapi.normalizer.entrypoint', ['serializer.normalizer']);
        $this->assertServiceHasTags('api_platform.jsonapi.normalizer.collection', ['serializer.normalizer']);
        $this->assertServiceHasTags('api_platform.jsonapi.normalizer.item', ['serializer.normalizer']);
        $this->assertServiceHasTags('api_platform.jsonapi.normalizer.object', ['serializer.normalizer']);
        $this->assertServiceHasTags('api_platform.jsonapi.normalizer.constraint_violation_list', ['serializer.normalizer']);
        $this->assertServiceHasTags('api_platform.jsonapi.normalizer.error', ['serializer.normalizer']);
        $this->assertServiceHasTags('api_platform.jsonapi.listener.request.transform_pagination_parameters', ['kernel.event_listener']);
        $this->assertServiceHasTags('api_platform.jsonapi.listener.request.transform_sorting_parameters', ['kernel.event_listener']);
        $this->assertServiceHasTags('api_platform.jsonapi.listener.request.transform_fieldsets_parameters', ['kernel.event_listener']);
        $this->assertServiceHasTags('api_platform.jsonapi.listener.request.transform_filtering_parameters', ['kernel.event_listener']);
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
            'api_platform.hydra.json_schema.type_factory',
        ];

        $this->assertContainerHas($services, []);

        // jsonld.xml
        $this->assertServiceHasTags('api_platform.jsonld.normalizer.item', ['serializer.normalizer']);
        $this->assertServiceHasTags('api_platform.jsonld.normalizer.object', ['serializer.normalizer']);
        $this->assertServiceHasTags('api_platform.jsonld.encoder', ['serializer.encoder']);

        // hydra.xml
        $this->assertServiceHasTags('api_platform.hydra.normalizer.documentation', ['serializer.normalizer']);
        $this->assertServiceHasTags('api_platform.hydra.listener.response.add_link_header', ['kernel.event_listener']);
        $this->assertServiceHasTags('api_platform.hydra.normalizer.constraint_violation_list', ['serializer.normalizer']);
        $this->assertServiceHasTags('api_platform.hydra.normalizer.entrypoint', ['serializer.normalizer']);
        $this->assertServiceHasTags('api_platform.hydra.normalizer.error', ['serializer.normalizer']);
        $this->assertServiceHasTags('api_platform.hydra.normalizer.collection', ['serializer.normalizer']);
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

        $this->assertContainerHas($services, []);

        // hal.xml
        $this->assertServiceHasTags('api_platform.hal.encoder', ['serializer.encoder']);
        $this->assertServiceHasTags('api_platform.hal.normalizer.entrypoint', ['serializer.normalizer']);
        $this->assertServiceHasTags('api_platform.hal.normalizer.collection', ['serializer.normalizer']);
        $this->assertServiceHasTags('api_platform.hal.normalizer.item', ['serializer.normalizer']);
        $this->assertServiceHasTags('api_platform.hal.normalizer.object', ['serializer.normalizer']);
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

        $this->assertContainerHas($services, []);

        // problem.xml
        $this->assertServiceHasTags('api_platform.problem.encoder', ['serializer.encoder']);
        $this->assertServiceHasTags('api_platform.problem.normalizer.constraint_violation_list', ['serializer.normalizer']);
        $this->assertServiceHasTags('api_platform.problem.normalizer.error', ['serializer.normalizer']);
    }

    public function testGraphQlConfiguration(): void
    {
        $config = self::DEFAULT_CONFIG;
        $config['api_platform']['graphql']['enabled'] = true;
        $this->container->setParameter('kernel.debug', true);
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
            'api_platform.graphql.data_collector.resolver.factory.collection',
            'api_platform.graphql.data_collector.resolver.factory.item',
            'api_platform.graphql.data_collector.resolver.factory.item_mutation',
            'api_platform.graphql.data_collector.resolver.factory.item_subscription',
        ];

        $aliases = [
            // graphql.xml
            GraphQlSerializerContextBuilderInterface::class,
        ];

        $this->assertContainerHas($services, $aliases);

        // graphql.xml
        $this->assertServiceHasTags('api_platform.graphql.query_resolver_locator', ['container.service_locator']);
        $this->assertServiceHasTags('api_platform.graphql.mutation_resolver_locator', ['container.service_locator']);
        $this->assertServiceHasTags('api_platform.graphql.iterable_type', ['api_platform.graphql.type']);
        $this->assertServiceHasTags('api_platform.graphql.upload_type', ['api_platform.graphql.type']);
        $this->assertServiceHasTags('api_platform.graphql.type_locator', ['container.service_locator']);
        $this->assertServiceHasTags('api_platform.graphql.fields_builder_locator', ['container.service_locator']);
        $this->assertServiceHasTags('api_platform.graphql.cache.subscription', ['cache.pool']);
        $this->assertServiceHasTags('api_platform.graphql.command.export_command', ['console.command']);
        $this->assertServiceHasTags('api_platform.graphql.normalizer.item', ['serializer.normalizer']);
        $this->assertServiceHasTags('api_platform.graphql.normalizer.object', ['serializer.normalizer']);
        $this->assertServiceHasTags('api_platform.graphql.normalizer.error', ['serializer.normalizer']);
        $this->assertServiceHasTags('api_platform.graphql.normalizer.validation_exception', ['serializer.normalizer']);
        $this->assertServiceHasTags('api_platform.graphql.normalizer.http_exception', ['serializer.normalizer']);
        $this->assertServiceHasTags('api_platform.graphql.normalizer.runtime_exception', ['serializer.normalizer']);
    }

    public function testRuntimeExceptionIsThrownIfTwigIsNotEnabledButGraphqlClientsAre(): void
    {
        $config = self::DEFAULT_CONFIG;
        $config['api_platform']['graphql']['enabled'] = true;
        $this->container->getParameterBag()->set('kernel.bundles', [
            'DoctrineBundle' => DoctrineBundle::class,
            'SecurityBundle' => SecurityBundle::class,
        ]);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('GraphiQL and GraphQL Playground interfaces depend on Twig. Please activate TwigBundle for the test environnement or disable GraphiQL and GraphQL Playground.');

        (new ApiPlatformExtension())->load($config, $this->container);
    }

    public function testGraphqlClientsDefinitionsAreRemovedIfDisabled(): void
    {
        $config = self::DEFAULT_CONFIG;
        $config['api_platform']['graphql']['enabled'] = true;
        $config['api_platform']['graphql']['graphiql']['enabled'] = false;
        $config['api_platform']['graphql']['graphql_playground']['enabled'] = false;
        $this->container->getParameterBag()->set('kernel.bundles', [
            'DoctrineBundle' => DoctrineBundle::class,
            'SecurityBundle' => SecurityBundle::class,
        ]);

        (new ApiPlatformExtension())->load($config, $this->container);

        $this->assertNotContainerHasService('api_platform.graphql.action.graphiql');
        $this->assertNotContainerHasService('api_platform.graphql.action.graphql_playground');
    }

    public function testDoctrineOrmConfiguration(): void
    {
        $config = self::DEFAULT_CONFIG;
        $config['api_platform']['doctrine']['enabled'] = true;
        (new ApiPlatformExtension())->load($config, $this->container);

        $services = [
            // doctrine_orm.xml
            'api_platform.doctrine.metadata_factory',
            'api_platform.doctrine.orm.state.remove_processor',
            'api_platform.doctrine.orm.state.persist_processor',
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
        ];

        $aliases = [
            // doctrine_orm.xml
            RemoveProcessor::class,
            PersistProcessor::class,
            CollectionProvider::class,
            ItemProvider::class,
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

        $this->assertContainerHas($services, $aliases);

        // doctrine_orm.xml
        $this->assertServiceHasTags('api_platform.doctrine.orm.state.remove_processor', ['api_platform.state_processor']);
        $this->assertServiceHasTags('api_platform.doctrine.orm.state.persist_processor', ['api_platform.state_processor']);
        $this->assertServiceHasTags('api_platform.doctrine.orm.state.collection_provider', ['api_platform.state_provider']);
        $this->assertServiceHasTags('api_platform.doctrine.orm.state.item_provider', ['api_platform.state_provider']);
        $this->assertServiceHasTags('api_platform.doctrine.orm.query_extension.eager_loading', ['api_platform.doctrine.orm.query_extension.item', 'api_platform.doctrine.orm.query_extension.collection']);
        $this->assertServiceHasTags('api_platform.doctrine.orm.query_extension.filter', ['api_platform.doctrine.orm.query_extension.collection']);
        $this->assertServiceHasTags('api_platform.doctrine.orm.query_extension.filter_eager_loading', ['api_platform.doctrine.orm.query_extension.collection']);
        $this->assertServiceHasTags('api_platform.doctrine.orm.query_extension.pagination', ['api_platform.doctrine.orm.query_extension.collection']);
        $this->assertServiceHasTags('api_platform.doctrine.orm.query_extension.order', ['api_platform.doctrine.orm.query_extension.collection']);
    }

    public function testDoctrineMongoDbOdmConfiguration(): void
    {
        $config = self::DEFAULT_CONFIG;
        $config['api_platform']['doctrine_mongodb_odm']['enabled'] = true;
        (new ApiPlatformExtension())->load($config, $this->container);

        $services = [
            // doctrine_mongodb_odm.xml
            'api_platform.doctrine_mongodb.odm.default_document_manager.property_info_extractor',
            'api_platform.doctrine.metadata_factory',
            'api_platform.doctrine_mongodb.odm.state.remove_processor',
            'api_platform.doctrine_mongodb.odm.state.persist_processor',
            'api_platform.doctrine_mongodb.odm.state.collection_provider',
            'api_platform.doctrine_mongodb.odm.state.item_provider',
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
        ];

        $aliases = [
            // doctrine_mongodb_odm.xml
            RemoveProcessor::class,
            PersistProcessor::class,
            MongoDbCollectionProvider::class,
            MongoDbItemProvider::class,
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

        $this->assertContainerHas($services, $aliases);

        $this->assertServiceHasTags('api_platform.doctrine_mongodb.odm.state.remove_processor', ['api_platform.state_processor']);
        $this->assertServiceHasTags('api_platform.doctrine_mongodb.odm.state.persist_processor', ['api_platform.state_processor']);
        $this->assertServiceHasTags('api_platform.doctrine_mongodb.odm.state.collection_provider', ['api_platform.state_provider']);
        $this->assertServiceHasTags('api_platform.doctrine_mongodb.odm.state.item_provider', ['api_platform.state_provider']);
        $this->assertServiceHasTags('api_platform.doctrine_mongodb.odm.default_document_manager.property_info_extractor', ['property_info.list_extractor', 'property_info.type_extractor']);
        $this->assertServiceHasTags('api_platform.doctrine_mongodb.odm.aggregation_extension.filter', ['api_platform.doctrine_mongodb.odm.aggregation_extension.collection']);
        $this->assertServiceHasTags('api_platform.doctrine_mongodb.odm.aggregation_extension.pagination', ['api_platform.doctrine_mongodb.odm.aggregation_extension.collection']);
        $this->assertServiceHasTags('api_platform.doctrine_mongodb.odm.aggregation_extension.order', ['api_platform.doctrine_mongodb.odm.aggregation_extension.collection']);
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
            'api_platform.http_cache.purger.varnish.xkey',
            'api_platform.http_cache.purger.varnish.ban',
            'api_platform.http_cache.listener.response.add_tags',
        ];

        $this->assertContainerHas($services);

        // http_cache.xml
        $this->assertServiceHasTags('api_platform.http_cache.listener.response.configure', ['kernel.event_listener']);

        // doctrine_orm_http_cache_purger.xml
        $this->assertServiceHasTags('api_platform.doctrine.listener.http_cache.purge', ['doctrine.event_listener']);

        // http_cache_tags.xml
        $this->assertServiceHasTags('api_platform.http_cache.listener.response.add_tags', ['kernel.event_listener']);

        $this->assertContainerHasAlias('api_platform.http_cache.purger.varnish');

        $this->assertEquals([
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
            \ApiPlatform\Validator\ValidatorInterface::class,
        ];

        $this->assertContainerHas($services, $aliases);

        // metadata/validator.xml
        $this->assertServiceHasTags('api_platform.metadata.property_schema.choice_restriction', ['api_platform.metadata.property_schema_restriction']);
        $this->assertServiceHasTags('api_platform.metadata.property_schema.collection_restriction', ['api_platform.metadata.property_schema_restriction']);
        $this->assertServiceHasTags('api_platform.metadata.property_schema.count_restriction', ['api_platform.metadata.property_schema_restriction']);
        $this->assertServiceHasTags('api_platform.metadata.property_schema.greater_than_or_equal_restriction', ['api_platform.metadata.property_schema_restriction']);
        $this->assertServiceHasTags('api_platform.metadata.property_schema.greater_than_restriction', ['api_platform.metadata.property_schema_restriction']);
        $this->assertServiceHasTags('api_platform.metadata.property_schema.length_restriction', ['api_platform.metadata.property_schema_restriction']);
        $this->assertServiceHasTags('api_platform.metadata.property_schema.less_than_or_equal_restriction', ['api_platform.metadata.property_schema_restriction']);
        $this->assertServiceHasTags('api_platform.metadata.property_schema.less_than_restriction', ['api_platform.metadata.property_schema_restriction']);
        $this->assertServiceHasTags('api_platform.metadata.property_schema.one_of_restriction', ['api_platform.metadata.property_schema_restriction']);
        $this->assertServiceHasTags('api_platform.metadata.property_schema.range_restriction', ['api_platform.metadata.property_schema_restriction']);
        $this->assertServiceHasTags('api_platform.metadata.property_schema.regex_restriction', ['api_platform.metadata.property_schema_restriction']);
        $this->assertServiceHasTags('api_platform.metadata.property_schema.format_restriction', ['api_platform.metadata.property_schema_restriction']);
        $this->assertServiceHasTags('api_platform.metadata.property_schema.unique_restriction', ['api_platform.metadata.property_schema_restriction']);

        // symfony/validator.xml
        $this->assertServiceHasTags('api_platform.listener.view.validate', ['kernel.event_listener']);
        $this->assertServiceHasTags('api_platform.listener.view.validate_query_parameters', ['kernel.event_listener']);
    }

    public function testDataCollectorConfiguration(): void
    {
        $config = self::DEFAULT_CONFIG;
        $config['api_platform']['enable_profiler'] = true;
        $this->container->setParameter('kernel.debug', true);
        (new ApiPlatformExtension())->load($config, $this->container);

        $services = [
            // data_collector.xml
            'api_platform.data_collector.request',

            // debug.xml
            'debug.var_dumper.cloner',
            'debug.var_dumper.cli_dumper',
            'debug.api_platform.debug_resource.command',
        ];

        $this->assertContainerHas($services, []);

        // data_collector.xml
        $this->assertServiceHasTags('api_platform.data_collector.request', ['data_collector']);

        // debug.xml
        $this->assertServiceHasTags('debug.api_platform.debug_resource.command', ['console.command']);
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

            // doctrine_orm_mercure_publisher
            'api_platform.doctrine.orm.listener.mercure.publish',

            // doctrine_odm_mercure_publisher.xml
            'api_platform.doctrine_mongodb.odm.listener.mercure.publish',

            // graphql_mercure.xml
            'api_platform.graphql.subscription.mercure_iri_generator',
        ];

        $this->assertContainerHas($services, []);

        // mercure.xml
        $this->assertServiceHasTags('api_platform.mercure.listener.response.add_link_header', ['kernel.event_listener']);

        // doctrine_orm_mercure_publisher
        $this->assertServiceHasTags('api_platform.doctrine.orm.listener.mercure.publish', ['doctrine.event_listener']);

        // doctrine_odm_mercure_publisher.xml
        $this->assertServiceHasTags('api_platform.doctrine_mongodb.odm.listener.mercure.publish', ['doctrine_mongodb.odm.event_listener']);

        $this->assertEquals([
            ['event' => 'onFlush'],
            ['event' => 'postFlush'],
        ], $this->container->getDefinition('api_platform.doctrine.orm.listener.mercure.publish')->getTag('doctrine.event_listener'));

        $this->assertEquals([
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
            MessengerProcessor::class,
        ];

        $aliases = [
            // messenger.xml
            'api_platform.message_bus',
        ];

        $this->assertContainerHas($services, $aliases);

        $this->assertServiceHasTags(MessengerProcessor::class, ['api_platform.state_processor']);
    }

    public function testElasticsearchConfiguration(): void
    {
        $config = self::DEFAULT_CONFIG;
        $config['api_platform']['elasticsearch']['enabled'] = true;
        (new ApiPlatformExtension())->load($config, $this->container);

        $services = [
            // elasticsearch.xml
            'api_platform.elasticsearch.state.item_provider',
            'api_platform.elasticsearch.state.collection_provider',
            'api_platform.elasticsearch.client',
            'api_platform.elasticsearch.cache.metadata.document',
            'api_platform.elasticsearch.metadata.document.metadata_factory.configured',
            'api_platform.elasticsearch.metadata.document.metadata_factory.attribute',
            'api_platform.elasticsearch.metadata.document.metadata_factory.cat',
            'api_platform.elasticsearch.metadata.document.metadata_factory.cached',
            'api_platform.elasticsearch.name_converter.inner_fields',
            'api_platform.elasticsearch.normalizer.item',
            'api_platform.elasticsearch.normalizer.document',
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
            DocumentMetadataFactoryInterface::class,
            TermFilter::class,
            MatchFilter::class,
            \ApiPlatform\Elasticsearch\Filter\OrderFilter::class,
            \ApiPlatform\Elasticsearch\State\ItemProvider::class,
            \ApiPlatform\Elasticsearch\State\CollectionProvider::class,
        ];

        $this->assertContainerHas($services, $aliases);

        // elasticsearch.xml
        $this->assertServiceHasTags('api_platform.elasticsearch.cache.metadata.document', ['cache.pool']);
        $this->assertServiceHasTags('api_platform.elasticsearch.normalizer.document', ['serializer.normalizer']);
        $this->assertServiceHasTags('api_platform.elasticsearch.state.item_provider', ['api_platform.state_provider']);
        $this->assertServiceHasTags('api_platform.elasticsearch.state.collection_provider', ['api_platform.state_provider']);
        $this->assertServiceHasTags('api_platform.elasticsearch.request_body_search_extension.constant_score_filter', ['api_platform.elasticsearch.request_body_search_extension.collection']);
        $this->assertServiceHasTags('api_platform.elasticsearch.request_body_search_extension.sort_filter', ['api_platform.elasticsearch.request_body_search_extension.collection']);
        $this->assertServiceHasTags('api_platform.elasticsearch.request_body_search_extension.sort', ['api_platform.elasticsearch.request_body_search_extension.collection']);

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
            ResourceAccessCheckerInterface::class,
        ];

        $this->assertContainerHas($services, $aliases);

        // security.xml
        $this->assertServiceHasTags('api_platform.security.listener.request.deny_access', ['kernel.event_listener']);
        $this->assertServiceHasTags('api_platform.security.expression_language_provider', ['security.expression_language_provider']);

        $this->assertEquals([
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
    }

    public function testTranslationConfiguration(): void
    {
        $config = self::DEFAULT_CONFIG;
        (new ApiPlatformExtension())->load($config, $this->container);

        $services = [
            // translation.xml
            'api_platform.translation.resource_translator',
        ];

        $aliases = [
            // translation.xml
            ResourceTranslatorInterface::class,
        ];

        $this->assertContainerHas($services, $aliases);
    }

    public function testArgumentResolverConfiguration(): void
    {
        $config = self::DEFAULT_CONFIG;
        (new ApiPlatformExtension())->load($config, $this->container);

        $services = [
            // argument_resolver.xml
            'api_platform.argument_resolver.payload',
        ];

        $this->assertContainerHas($services, []);

        // argument_resolver.xml
        $this->assertServiceHasTags('api_platform.argument_resolver.payload', ['controller.argument_value_resolver']);
    }

    public function testAutoConfigurableInterfaces(): void
    {
        $config = self::DEFAULT_CONFIG;
        (new ApiPlatformExtension())->load($config, $this->container);

        $interfaces = [
            FilterInterface::class => 'api_platform.filter',
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

    public function testDefaults(): void
    {
        $config = self::DEFAULT_CONFIG;
        $config['api_platform']['defaults'] = [
            'something' => 'test',
            'extra_properties' => ['else' => 'foo'],
        ];

        (new ApiPlatformExtension())->load($config, $this->container);

        $this->assertEquals($this->container->getParameter('api_platform.defaults'), ['extra_properties' => ['else' => 'foo', 'something' => 'test']]);
    }

    public function testConfigurationDirectories(): void
    {
        $config = self::DEFAULT_CONFIG;
        $config['api_platform']['defaults'] = [
            'something' => 'test',
            'extra_properties' => ['else' => 'foo'],
        ];

        (new ApiPlatformExtension())->load($config, $this->container);

        $kernelProjectDir = realpath(__DIR__.'/../../../Fixtures/TestBundle');
        $resourceClassDirectories = $this->container->getParameter('api_platform.resource_class_directories');

        $this->assertContains($kernelProjectDir.'/Resources/config/api_resources', $resourceClassDirectories);
        $this->assertContains($kernelProjectDir.'/Entity', $resourceClassDirectories);
        $this->assertContains($kernelProjectDir.'/Document', $resourceClassDirectories);
        $this->assertContains(realpath(__DIR__.'/../../../Symfony/Bundle/DependencyInjection').'/../../../Fixtures/app/config/api_platform', $resourceClassDirectories);
    }

    /**
     * @group legacy
     */
    public function testDeprecatedHttpCacheConfiguration(): void
    {
        $config = self::DEFAULT_CONFIG;
        $config['api_platform']['http_cache']['invalidation']['varnish_urls'] = ['test'];
        $config['api_platform']['http_cache']['invalidation']['xkey'] = ['glue' => ' '];

        (new ApiPlatformExtension())->load($config, $this->container);
        $this->assertServiceHasTags('api_platform.invalidation_http_client.0', ['api_platform.http_cache.http_client']);
    }

    public function testHttpCacheUrlsConfiguration(): void
    {
        $config = self::DEFAULT_CONFIG;
        $config['api_platform']['http_cache']['invalidation']['urls'] = ['test'];
        $config['api_platform']['http_cache']['invalidation']['scoped_clients'] = ['my_scoped_client'];

        $this->container->setDefinition('my_scoped_client', new Definition('my_scoped_client'));

        (new ApiPlatformExtension())->load($config, $this->container);
        $this->assertServiceHasTags('api_platform.invalidation_http_client.0', ['api_platform.http_cache.http_client']);
        $this->assertServiceHasTags('my_scoped_client', ['api_platform.http_cache.http_client']);
    }

    public function testHttpCacheBanConfiguration(): void
    {
        $config = self::DEFAULT_CONFIG;

        (new ApiPlatformExtension())->load($config, $this->container);

        $service = $this->container->getDefinition('api_platform.http_cache.purger.varnish.ban');
        $this->assertCount(1, $service->getArguments());
        $this->assertEquals('api_platform.http_cache.http_client', $service->getArgument(0)->getTag());
    }
}
