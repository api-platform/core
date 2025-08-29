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

namespace ApiPlatform\Symfony\Tests\Bundle\DependencyInjection;

use ApiPlatform\Metadata\Exception\ExceptionInterface;
use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\IdentifiersExtractorInterface;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use ApiPlatform\Serializer\Filter\GroupFilter;
use ApiPlatform\Serializer\Filter\PropertyFilter;
use ApiPlatform\State\Pagination\Pagination;
use ApiPlatform\State\Pagination\PaginationOptions;
use ApiPlatform\State\SerializerContextBuilderInterface;
use ApiPlatform\Symfony\Action\NotFoundAction;
use ApiPlatform\Symfony\Bundle\DependencyInjection\ApiPlatformExtension;
use ApiPlatform\Tests\Fixtures\TestBundle\TestBundle;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\ORM\OptimisticLockException;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\HttpFoundation\Response;

class ApiPlatformExtensionTest extends TestCase
{
    final public const DEFAULT_CONFIG = ['api_platform' => [
        'title' => 'title',
        'description' => 'description',
        'version' => 'version',
        'enable_json_streamer' => false,
        'serializer' => ['hydra_prefix' => true],
        'formats' => [
            'json' => ['mime_types' => ['json']],
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
        'use_symfony_listeners' => false,
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
            $this->assertTrue($this->container->hasDefinition($service), \sprintf('Definition "%s" not found.', $service));
        }

        foreach ($aliases as $alias) {
            $this->assertContainerHasAlias($alias);
        }
    }

    private function assertContainerHasService(string $service): void
    {
        $this->assertTrue($this->container->hasDefinition($service), \sprintf('Service "%s" not found.', $service));
    }

    private function assertNotContainerHasService(string $service): void
    {
        $this->assertFalse($this->container->hasDefinition($service), \sprintf('Service "%s" found.', $service));
    }

    private function assertContainerHasAlias(string $alias): void
    {
        $this->assertTrue($this->container->hasAlias($alias), \sprintf('Alias "%s" not found.', $alias));
    }

    private function assertServiceHasTags(string $service, array $tags = []): void
    {
        $serviceTags = $this->container->getDefinition($service)->getTags();

        foreach ($tags as $tag) {
            $this->assertArrayHasKey($tag, $serviceTags, \sprintf('Tag "%s" not found on the service "%s".', $tag, $service));
        }
    }

    public function testCommonConfiguration(): void
    {
        $config = self::DEFAULT_CONFIG;
        (new ApiPlatformExtension())->load($config, $this->container);

        $services = [
            'api_platform.action.documentation',
            'api_platform.action.entrypoint',
            'api_platform.action.not_found',
            'api_platform.api.identifiers_extractor',
            'api_platform.filter_locator',
            'api_platform.negotiator',
            'api_platform.pagination',
            'api_platform.pagination_options',
            'api_platform.path_segment_name_generator.dash',
            'api_platform.path_segment_name_generator.underscore',
            'api_platform.metadata.inflector',
            'api_platform.resource_class_resolver',
            'api_platform.route_loader',
            'api_platform.router',
            'api_platform.serializer.context_builder',
            'api_platform.serializer.context_builder.filter',
            'api_platform.serializer.group_filter',
            'api_platform.serializer.mapping.class_metadata_factory',
            'api_platform.serializer.normalizer.item',
            'api_platform.serializer.property_filter',
            'api_platform.serializer_locator',
            'api_platform.symfony.iri_converter',
            'api_platform.uri_variables.converter',
            'api_platform.uri_variables.transformer.date_time',
            'api_platform.uri_variables.transformer.integer',

            'api_platform.state_provider.content_negotiation',
            'api_platform.state_provider.deserialize',
            'api_platform.state_processor.respond',
            'api_platform.state_processor.add_link_header',
            'api_platform.state_processor.serialize',
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
            'api_platform.identifiers_extractor',
            'api_platform.iri_converter',
            'api_platform.path_segment_name_generator',
            'api_platform.property_accessor',
            'api_platform.property_info',
            'api_platform.serializer',
            'api_platform.inflector',
        ];

        $this->assertContainerHas($services, $aliases);

        $this->assertServiceHasTags('api_platform.cache.route_name_resolver', ['cache.pool']);
        $this->assertServiceHasTags('api_platform.serializer.normalizer.item', ['serializer.normalizer']);
        $this->assertServiceHasTags('api_platform.serializer_locator', ['container.service_locator']);
        $this->assertServiceHasTags('api_platform.filter_locator', ['container.service_locator']);

        // api.xml
        $this->assertServiceHasTags('api_platform.route_loader', ['routing.loader']);
        $this->assertServiceHasTags('api_platform.uri_variables.transformer.integer', ['api_platform.uri_variables.transformer']);
        $this->assertServiceHasTags('api_platform.uri_variables.transformer.date_time', ['api_platform.uri_variables.transformer']);

        $services = [
            'api_platform.listener.request.read',
            'api_platform.listener.request.deserialize',
            'api_platform.listener.request.add_format',
            'api_platform.listener.view.write',
            'api_platform.listener.view.serialize',
            'api_platform.listener.view.respond',
        ];

        foreach ($services as $service) {
            $this->assertNotContainerHasService($service);
        }
    }

    public function testEventListenersConfiguration(): void
    {
        $config = self::DEFAULT_CONFIG;
        $config['api_platform']['use_symfony_listeners'] = true;
        (new ApiPlatformExtension())->load($config, $this->container);

        $services = [
            'api_platform.listener.request.read',
            'api_platform.listener.request.deserialize',
            'api_platform.listener.request.add_format',
            'api_platform.listener.view.write',
            'api_platform.listener.view.serialize',
            'api_platform.listener.view.respond',

            'api_platform.state_provider.content_negotiation',
            'api_platform.state_provider.deserialize',
            'api_platform.state_processor.respond',
            'api_platform.state_processor.add_link_header',
            'api_platform.state_processor.serialize',
        ];

        $aliases = [
            'api_platform.action.delete_item',
            'api_platform.action.get_collection',
            'api_platform.action.get_item',
            'api_platform.action.patch_item',
            'api_platform.action.post_collection',
            'api_platform.action.put_item',
        ];

        $this->assertContainerHas($services, $aliases);
        $this->container->hasParameter('api_platform.swagger.http_auth');
    }

    public function testItRegistersMetadataConfiguration(): void
    {
        $config = self::DEFAULT_CONFIG;
        $config['api_platform']['mapping']['imports'] = [__DIR__.'/php'];
        (new ApiPlatformExtension())->load($config, $this->container);

        $emptyPhpFile = realpath(__DIR__.'/php/empty_file.php');
        $this->assertContainerHasService('api_platform.metadata.resource_extractor.php_file');
        $this->assertSame([$emptyPhpFile], $this->container->getDefinition('api_platform.metadata.resource_extractor.php_file')->getArgument(0));
    }
}
