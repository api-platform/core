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

use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Symfony\Bundle\DependencyInjection\Configuration;
use Doctrine\ORM\OptimisticLockException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Exception\InvalidTypeException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
class ConfigurationTest extends TestCase
{
    private Configuration $configuration;

    private Processor $processor;

    protected function setUp(): void
    {
        $this->configuration = new Configuration();
        $this->processor = new Processor();
    }

    public function testDefaultConfig(): void
    {
        $this->runDefaultConfigTests();
    }

    public function testDefaultConfigWithMongoDbOdm(): void
    {
        $this->runDefaultConfigTests(['orm', 'odm']);
    }

    private function runDefaultConfigTests(array $doctrineIntegrationsToLoad = ['orm']): void
    {
        $treeBuilder = $this->configuration->getConfigTreeBuilder();
        $config = $this->processor->processConfiguration($this->configuration, [
            'api_platform' => [
                'title' => 'title',
                'description' => 'description',
                'version' => '1.0.0',
                'doctrine' => [
                    'enabled' => \in_array('orm', $doctrineIntegrationsToLoad, true),
                ],
                'doctrine_mongodb_odm' => [
                    'enabled' => \in_array('odm', $doctrineIntegrationsToLoad, true),
                ],
            ],
        ]);

        $this->assertInstanceOf(ConfigurationInterface::class, $this->configuration);
        $this->assertInstanceOf(TreeBuilder::class, $treeBuilder);
        $this->assertEquals([
            'title' => 'title',
            'description' => 'description',
            'version' => '1.0.0',
            'show_webby' => true,
            'formats' => [
                'jsonld' => ['mime_types' => ['application/ld+json']],
            ],
            'docs_formats' => [
                'jsonopenapi' => ['mime_types' => ['application/vnd.openapi+json']],
                'yamlopenapi' => ['mime_types' => ['application/vnd.openapi+yaml']],
                'jsonld' => ['mime_types' => ['application/ld+json']],
                'html' => ['mime_types' => ['text/html']],
            ],
            'patch_formats' => [
                'json' => ['mime_types' => ['application/merge-patch+json']],
            ],
            'error_formats' => [
                'jsonproblem' => ['mime_types' => ['application/problem+json']],
                'jsonld' => ['mime_types' => ['application/ld+json']],
                'json' => ['mime_types' => ['application/problem+json', 'application/json']],
            ],
            'jsonschema_formats' => [],
            'exception_to_status' => [
                ExceptionInterface::class => Response::HTTP_BAD_REQUEST,
                InvalidArgumentException::class => Response::HTTP_BAD_REQUEST,
                OptimisticLockException::class => Response::HTTP_CONFLICT,
            ],
            'path_segment_name_generator' => 'api_platform.metadata.path_segment_name_generator.underscore',
            'inflector' => 'api_platform.metadata.inflector',
            'validator' => [
                'serialize_payload_fields' => [],
                'query_parameter_validation' => true,
            ],
            'name_converter' => null,
            'enable_swagger' => true,
            'enable_swagger_ui' => true,
            'enable_entrypoint' => true,
            'enable_re_doc' => true,
            'enable_docs' => true,
            'enable_profiler' => true,
            'graphql' => [
                'enabled' => true,
                'default_ide' => 'graphiql',
                'graphql_playground' => [
                    'enabled' => true,
                ],
                'graphiql' => [
                    'enabled' => true,
                ],
                'introspection' => [
                    'enabled' => true,
                ],
                'max_query_depth' => 20,
                'max_query_complexity' => 500,
                'nesting_separator' => '_',
                'collection' => [
                    'pagination' => [
                        'enabled' => true,
                    ],
                ],
            ],
            'elasticsearch' => [
                'enabled' => false,
                'hosts' => [],
            ],
            'oauth' => [
                'enabled' => false,
                'clientId' => '',
                'clientSecret' => '',
                'type' => 'oauth2',
                'flow' => 'application',
                'tokenUrl' => '',
                'authorizationUrl' => '',
                'refreshUrl' => '',
                'scopes' => [],
                'pkce' => false,
            ],
            'swagger' => [
                'versions' => [3],
                'api_keys' => [],
                'http_auth' => [],
                'swagger_ui_extra_configuration' => [],
                'persist_authorization' => false,
            ],
            'eager_loading' => [
                'enabled' => true,
                'max_joins' => 30,
                'force_eager' => true,
                'fetch_partial' => false,
            ],
            'collection' => [
                'exists_parameter_name' => 'exists',
                'order' => 'ASC',
                'order_parameter_name' => 'order',
                'order_nulls_comparison' => null,
                'pagination' => [
                    'enabled' => true,
                    'page_parameter_name' => 'page',
                    'enabled_parameter_name' => 'pagination',
                    'items_per_page_parameter_name' => 'itemsPerPage',
                    'partial_parameter_name' => 'partial',
                ],
            ],
            'mapping' => [
                'paths' => [],
            ],
            'http_cache' => [
                'invalidation' => [
                    'enabled' => false,
                    'varnish_urls' => [],
                    'request_options' => [],
                    'max_header_length' => 7500,
                    'purger' => 'api_platform.http_cache.purger.varnish',
                    'xkey' => ['glue' => ' '],
                    'urls' => [],
                    'scoped_clients' => [],
                ],
                'public' => null,
            ],
            'doctrine' => [
                'enabled' => \in_array('orm', $doctrineIntegrationsToLoad, true),
            ],
            'doctrine_mongodb_odm' => [
                'enabled' => \in_array('odm', $doctrineIntegrationsToLoad, true),
            ],
            'messenger' => [
                'enabled' => true,
            ],
            'mercure' => [
                'enabled' => true,
                'hub_url' => null,
                'include_type' => false,
            ],
            'resource_class_directories' => [],
            'asset_package' => null,
            'openapi' => [
                'contact' => [
                    'name' => null,
                    'url' => null,
                    'email' => null,
                ],
                'termsOfService' => null,
                'license' => [
                    'name' => null,
                    'url' => null,
                    'identifier' => null,
                ],
                'swagger_ui_extra_configuration' => [],
                'overrideResponses' => true,
                'tags' => [],
            ],
            'maker' => [
                'enabled' => true,
            ],
            'use_symfony_listeners' => false,
            'handle_symfony_errors' => false,
            'enable_link_security' => false,
            'serializer' => [
                'hydra_prefix' => null,
            ],
        ], $config);
    }

    public static function invalidHttpStatusCodeProvider(): array
    {
        return [
            [0],
            [99],
            [700],
            [1000],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('invalidHttpStatusCodeProvider')]
    public function testExceptionToStatusConfigWithInvalidHttpStatusCode($invalidHttpStatusCode): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessageMatches('/The HTTP status code ".+" is not valid\\./');

        $this->processor->processConfiguration($this->configuration, [
            'api_platform' => [
                'exception_to_status' => [
                    \Exception::class => $invalidHttpStatusCode,
                ],
            ],
        ]);
    }

    public static function invalidHttpStatusCodeValueProvider(): array
    {
        return [
            [true],
            [null],
            [-\INF],
            [40.4],
            ['foo'],
            ['HTTP_FOO_BAR'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('invalidHttpStatusCodeValueProvider')]
    public function testExceptionToStatusConfigWithInvalidHttpStatusCodeValue($invalidHttpStatusCodeValue): void
    {
        $this->expectException(InvalidTypeException::class);
        $this->expectExceptionMessageMatches('/Invalid type for path "api_platform\\.exception_to_status\\.Exception". Expected "?int"?, but got .+\\./');

        $this->processor->processConfiguration($this->configuration, [
            'api_platform' => [
                'exception_to_status' => [
                    \Exception::class => $invalidHttpStatusCodeValue,
                ],
            ],
        ]);
    }

    /**
     * Test config for api keys.
     */
    public function testInvalidApiKeysConfig(): void
    {
        $this->expectExceptionMessage('The api keys "key" is not valid according to the pattern enforced by OpenAPI 3.1 ^[a-zA-Z0-9._-]+$.');
        $exampleConfig = [
            'name' => 'Authorization',
            'type' => 'query',
        ];

        $config = $this->processor->processConfiguration($this->configuration, [
            'api_platform' => [
                'swagger' => [
                    'api_keys' => ['Some Authorization name, like JWT' => $exampleConfig, 'Another-Auth' => $exampleConfig],
                ],
            ],
        ]);
    }

    /**
     * Test config for api keys.
     */
    public function testApiKeysConfig(): void
    {
        $exampleConfig = [
            'name' => 'Authorization',
            'type' => 'query',
        ];

        $config = $this->processor->processConfiguration($this->configuration, [
            'api_platform' => [
                'swagger' => [
                    'api_keys' => ['authorization_name_like_JWT' => $exampleConfig],
                ],
            ],
        ]);

        $this->assertArrayHasKey('api_keys', $config['swagger']);
        $this->assertSame($exampleConfig, $config['swagger']['api_keys']['authorization_name_like_JWT']);
    }

    /**
     * Test config for disabled swagger versions.
     */
    public function testDisabledSwaggerVersionConfig(): void
    {
        $config = $this->processor->processConfiguration($this->configuration, [
            'api_platform' => [
                'enable_swagger' => false,
                'swagger' => [
                    'versions' => [3],
                ],
            ],
        ]);

        $this->assertArrayHasKey('versions', $config['swagger']);
        $this->assertEmpty($config['swagger']['versions']);
    }

    /**
     * Test config for swagger versions.
     */
    public function testSwaggerVersionConfig(): void
    {
        $config = $this->processor->processConfiguration($this->configuration, [
            'api_platform' => [
                'swagger' => [
                    'versions' => [3],
                ],
            ],
        ]);

        $this->assertArrayHasKey('versions', $config['swagger']);
        $this->assertEquals([3], $config['swagger']['versions']);

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessageMatches('/Only the versions .+ are supported. Got .+./');

        $this->processor->processConfiguration($this->configuration, [
            'api_platform' => [
                'swagger' => [
                    'versions' => [1],
                ],
            ],
        ]);
    }

    /**
     * Test config for empty title and description.
     */
    public function testEmptyTitleDescriptionConfig(): void
    {
        $config = $this->processor->processConfiguration($this->configuration, [
            'api_platform' => [],
        ]);

        $this->assertSame('', $config['title']);
        $this->assertSame('', $config['description']);
    }

    public function testEnableElasticsearch(): void
    {
        $config = $this->processor->processConfiguration($this->configuration, [
            'api_platform' => [
                'elasticsearch' => true,
            ],
        ]);

        $this->assertTrue($config['elasticsearch']['enabled']);
    }

    /**
     * Test config for http auth.
     */
    public function testHttpAuth(): void
    {
        $config = $this->processor->processConfiguration($this->configuration, [
            'api_platform' => [
                'swagger' => [
                    'http_auth' => ['PAT' => [
                        'scheme' => 'bearer',
                        'bearerFormat' => 'JWT',
                    ]],
                ],
            ],
        ]);

        $this->assertArrayHasKey('http_auth', $config['swagger']);
        $this->assertSame(['scheme' => 'bearer', 'bearerFormat' => 'JWT'], $config['swagger']['http_auth']['PAT']);
    }

    /**
     * Test openapi tags.
     */
    public function testOpenApiTags(): void
    {
        $config = $this->processor->processConfiguration($this->configuration, [
            'api_platform' => [
                'openapi' => [
                    'tags' => [
                        ['name' => 'test', 'description' => 'test2'],
                        ['name' => 'test3'],
                    ],
                ],
            ],
        ]);

        $this->assertEquals(['name' => 'test3', 'description' => null], $config['openapi']['tags'][1]);
    }
}
