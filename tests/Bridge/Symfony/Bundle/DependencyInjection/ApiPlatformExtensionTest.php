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

namespace ApiPlatform\Core\Tests\Bridge\Symfony\Bundle\DependencyInjection;

use ApiPlatform\Core\Bridge\Symfony\Bundle\DependencyInjection\ApiPlatformExtension;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use FOS\UserBundle\FOSUserBundle;
use Nelmio\ApiDocBundle\NelmioApiDocBundle;
use Prophecy\Argument;
use Symfony\Component\Config\Resource\ResourceInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Extension\ConfigurationExtensionInterface;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ApiPlatformExtensionTest extends \PHPUnit_Framework_TestCase
{
    const DEFAULT_CONFIG = ['api_platform' => [
        'title' => 'title',
        'description' => 'description',
        'version' => 'version',
        'formats' => [
            'jsonld' => ['mime_types' => ['application/ld+json']],
            'jsonhal' => ['mime_types' => ['application/hal+json']],
        ],
    ]];

    private $extension;

    protected function setUp()
    {
        $this->extension = new ApiPlatformExtension();
    }

    public function tearDown()
    {
        unset($this->extension);
    }

    public function testConstruct()
    {
        $this->extension = new ApiPlatformExtension();

        $this->assertInstanceOf(PrependExtensionInterface::class, $this->extension);
        $this->assertInstanceOf(ExtensionInterface::class, $this->extension);
        $this->assertInstanceOf(ConfigurationExtensionInterface::class, $this->extension);
    }

    public function testNotPrependWhenNull()
    {
        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);
        $containerBuilderProphecy->getExtensionConfig('framework')->willReturn(null)->shouldBeCalled();
        $containerBuilderProphecy->prependExtensionConfig('framework', Argument::type('array'))->shouldNotBeCalled();
        $containerBuilder = $containerBuilderProphecy->reveal();

        $this->extension->prepend($containerBuilder);
    }

    public function testNotPrependSerializerWhenConfigExist()
    {
        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);
        $containerBuilderProphecy->getExtensionConfig('framework')->willReturn([0 => ['serializer' => ['enabled' => false]]])->shouldBeCalled();
        $containerBuilderProphecy->prependExtensionConfig('framework', Argument::any())->willReturn(null)->shouldBeCalled();
        $containerBuilderProphecy->prependExtensionConfig('framework', Argument::that(function (array $config) {
            return array_key_exists('serializer', $config);
        }))->shouldNotBeCalled();
        $containerBuilder = $containerBuilderProphecy->reveal();

        $this->extension->prepend($containerBuilder);
    }

    public function testNotPrependPropertyInfoWhenConfigExist()
    {
        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);
        $containerBuilderProphecy->getExtensionConfig('framework')->willReturn([0 => ['property_info' => ['enabled' => false]]])->shouldBeCalled();
        $containerBuilderProphecy->prependExtensionConfig('framework', Argument::any())->willReturn(null)->shouldBeCalled();
        $containerBuilderProphecy->prependExtensionConfig('framework', Argument::that(function (array $config) {
            return array_key_exists('property_info', $config);
        }))->shouldNotBeCalled();
        $containerBuilder = $containerBuilderProphecy->reveal();

        $this->extension->prepend($containerBuilder);
    }

    public function testPrependWhenNotConfigured()
    {
        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);
        $containerBuilderProphecy->getExtensionConfig('framework')->willReturn([])->shouldBeCalled();
        $containerBuilderProphecy->prependExtensionConfig('framework', Argument::type('array'))->shouldNotBeCalled();
        $containerBuilder = $containerBuilderProphecy->reveal();

        $this->extension->prepend($containerBuilder);
    }

    public function testPrependWhenNotEnabled()
    {
        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);
        $containerBuilderProphecy->getExtensionConfig('framework')->willReturn([0 => ['serializer' => []]])->shouldBeCalled();
        $containerBuilderProphecy->prependExtensionConfig('framework', Argument::type('array'))->shouldBeCalled();
        $containerBuilder = $containerBuilderProphecy->reveal();

        $this->extension->prepend($containerBuilder);
    }

    public function testLoadDefaultConfig()
    {
        $containerBuilderProphecy = $this->getContainerBuilderProphecy();
        $containerBuilder = $containerBuilderProphecy->reveal();

        $this->extension->load(self::DEFAULT_CONFIG, $containerBuilder);
    }

    public function testSetNameConverter()
    {
        $nameConverterId = 'test.name_converter';

        $containerBuilderProphecy = $this->getContainerBuilderProphecy();
        $containerBuilderProphecy->setAlias('api_platform.name_converter', $nameConverterId)->shouldBeCalled();
        $containerBuilder = $containerBuilderProphecy->reveal();

        $this->extension->load(array_merge_recursive(self::DEFAULT_CONFIG, ['api_platform' => ['name_converter' => $nameConverterId]]), $containerBuilder);
    }

    public function testEnableFosUser()
    {
        $containerBuilderProphecy = $this->getContainerBuilderProphecy();
        $containerBuilderProphecy->getParameter('kernel.bundles')->willReturn([
            'DoctrineBundle' => DoctrineBundle::class,
            'FOSUserBundle' => FOSUserBundle::class,
        ])->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.fos_user.event_listener', Argument::type(Definition::class))->shouldBeCalled();
        $containerBuilder = $containerBuilderProphecy->reveal();

        $this->extension->load(array_merge_recursive(self::DEFAULT_CONFIG, ['api_platform' => ['enable_fos_user' => true]]), $containerBuilder);
    }

    public function testEnableNelmioApiDoc()
    {
        $containerBuilderProphecy = $this->getContainerBuilderProphecy();
        $containerBuilderProphecy->getParameter('kernel.bundles')->willReturn([
            'DoctrineBundle' => DoctrineBundle::class,
            'NelmioApiDocBundle' => NelmioApiDocBundle::class,
        ])->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.nelmio_api_doc.annotations_provider', Argument::type(Definition::class))->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.nelmio_api_doc.parser', Argument::type(Definition::class))->shouldBeCalled();
        $containerBuilder = $containerBuilderProphecy->reveal();

        $this->extension->load(array_merge_recursive(self::DEFAULT_CONFIG, ['api_platform' => ['enable_nelmio_api_doc' => true]]), $containerBuilder);
    }

    public function testDisableEagerLoadingExtension()
    {
        $containerBuilderProphecy = $this->getContainerBuilderProphecy();
        $containerBuilderProphecy->setParameter('api_platform.eager_loading.enabled', false)->shouldBeCalled();
        $containerBuilderProphecy->removeDefinition('api_platform.doctrine.orm.query_extension.eager_loading')->shouldBeCalled();
        $containerBuilderProphecy->removeDefinition('api_platform.doctrine.orm.query_extension.filter_eager_loading')->shouldBeCalled();
        $containerBuilder = $containerBuilderProphecy->reveal();
        $this->extension->load(array_merge_recursive(self::DEFAULT_CONFIG, ['api_platform' => ['eager_loading' => ['enabled' => false]]]), $containerBuilder);
    }

    private function getContainerBuilderProphecy()
    {
        $definitionArgument = Argument::that(function ($argument) {
            return $argument instanceof Definition || $argument instanceof DefinitionDecorator;
        });

        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);
        $containerBuilderProphecy->getParameter('kernel.bundles')->willReturn([
            'DoctrineBundle' => DoctrineBundle::class,
        ])->shouldBeCalled();

        $parameters = [
            'api_platform.collection.order' => null,
            'api_platform.collection.order_parameter_name' => 'order',
            'api_platform.collection.pagination.client_enabled' => false,
            'api_platform.collection.pagination.client_items_per_page' => false,
            'api_platform.collection.pagination.enabled' => true,
            'api_platform.collection.pagination.enabled_parameter_name' => 'pagination',
            'api_platform.collection.pagination.items_per_page' => 30,
            'api_platform.collection.pagination.items_per_page_parameter_name' => 'itemsPerPage',
            'api_platform.collection.pagination.maximum_items_per_page' => null,
            'api_platform.collection.pagination.page_parameter_name' => 'page',
            'api_platform.description' => 'description',
            'api_platform.error_formats' => ['jsonproblem' => ['application/problem+json'], 'jsonld' => ['application/ld+json']],
            'api_platform.enable_swagger' => true,
            'api_platform.enable_swagger_ui' => true,
            'api_platform.formats' => ['jsonld' => ['application/ld+json'], 'jsonhal' => ['application/hal+json']],
            'api_platform.exception_to_status' => [ExceptionInterface::class => Response::HTTP_BAD_REQUEST, InvalidArgumentException::class => Response::HTTP_BAD_REQUEST],
            'api_platform.title' => 'title',
            'api_platform.version' => 'version',
            'api_platform.eager_loading.enabled' => Argument::type('bool'),
            'api_platform.eager_loading.max_joins' => 30,
            'api_platform.eager_loading.force_eager' => true,
            'api_platform.resource_class_directories' => [],
        ];
        foreach ($parameters as $key => $value) {
            $containerBuilderProphecy->setParameter($key, $value)->shouldBeCalled();
        }

        $containerBuilderProphecy->addResource(Argument::type(ResourceInterface::class))->shouldBeCalled();
        $containerBuilderProphecy->hasExtension('http://symfony.com/schema/dic/services')->shouldBeCalled();

        $definitionProphecy = $this->prophesize(Definition::class);
        $definitionProphecy->addArgument('%api_platform.resource_class_directories%')->shouldBeCalled();
        $containerBuilderProphecy->getDefinition('api_platform.metadata.resource.name_collection_factory.annotation')->willReturn($definitionProphecy->reveal())->shouldBeCalled();

        foreach (['yaml', 'xml'] as $format) {
            $definitionProphecy = $this->prophesize(Definition::class);
            $definitionProphecy->addArgument([])->shouldBeCalled();
            $containerBuilderProphecy->getDefinition('api_platform.metadata.extractor.'.$format)->willReturn($definitionProphecy->reveal())->shouldBeCalled();
        }

        $definitions = [
            'api_platform.action.documentation',
            'api_platform.action.placeholder',
            'api_platform.action.entrypoint',
            'api_platform.action.exception',
            'api_platform.action.placeholder',
            'api_platform.cache.metadata.property',
            'api_platform.cache.metadata.resource',
            'api_platform.cache.route_name_resolver',
            'api_platform.collection_data_provider',
            'api_platform.doctrine.listener.view.write',
            'api_platform.doctrine.metadata_factory',
            'api_platform.doctrine.orm.boolean_filter',
            'api_platform.doctrine.orm.collection_data_provider',
            'api_platform.doctrine.orm.date_filter',
            'api_platform.doctrine.orm.default.collection_data_provider',
            'api_platform.doctrine.orm.default.item_data_provider',
            'api_platform.doctrine.orm.default.item_data_provider',
            'api_platform.doctrine.orm.item_data_provider',
            'api_platform.doctrine.orm.metadata.property.metadata_factory',
            'api_platform.doctrine.orm.numeric_filter',
            'api_platform.doctrine.orm.order_filter',
            'api_platform.doctrine.orm.query_extension.eager_loading',
            'api_platform.doctrine.orm.query_extension.filter',
            'api_platform.doctrine.orm.query_extension.filter_eager_loading',
            'api_platform.doctrine.orm.query_extension.order',
            'api_platform.doctrine.orm.query_extension.pagination',
            'api_platform.doctrine.orm.range_filter',
            'api_platform.doctrine.orm.search_filter',
            'api_platform.filters',
            'api_platform.doctrine.listener.view.write',
            'api_platform.jsonld.normalizer.item',
            'api_platform.jsonld.encoder',
            'api_platform.jsonld.action.context',
            'api_platform.jsonld.context_builder',
            'api_platform.jsonld.normalizer.item',
            'api_platform.swagger.normalizer.documentation',
            'api_platform.swagger.command.swagger_command',
            'api_platform.swagger.action.ui',
            'api_platform.swagger.listener.ui',
            'api_platform.hal.encoder',
            'api_platform.hal.normalizer.collection',
            'api_platform.hal.normalizer.entrypoint',
            'api_platform.hal.normalizer.item',
            'api_platform.hydra.listener.response.add_link_header',
            'api_platform.hydra.normalizer.collection',
            'api_platform.hydra.normalizer.collection_filters',
            'api_platform.hydra.normalizer.constraint_violation_list',
            'api_platform.hydra.normalizer.documentation',
            'api_platform.hydra.normalizer.entrypoint',
            'api_platform.hydra.normalizer.error',
            'api_platform.hydra.normalizer.partial_collection_view',
            'api_platform.iri_converter',
            'api_platform.item_data_provider',
            'api_platform.jsonld.action.context',
            'api_platform.jsonld.context_builder',
            'api_platform.jsonld.encoder',
            'api_platform.jsonld.normalizer.item',
            'api_platform.jsonld.normalizer.item',
            'api_platform.listener.exception',
            'api_platform.listener.exception.validation',
            'api_platform.listener.request.add_format',
            'api_platform.listener.request.deserialize',
            'api_platform.listener.request.read',
            'api_platform.listener.view.respond',
            'api_platform.listener.view.serialize',
            'api_platform.listener.view.validate',
            'api_platform.metadata.extractor.yaml',
            'api_platform.metadata.extractor.xml',
            'api_platform.metadata.property.metadata_factory.annotation',
            'api_platform.metadata.property.metadata_factory.cached',
            'api_platform.metadata.property.metadata_factory.inherited',
            'api_platform.metadata.property.metadata_factory.property_info',
            'api_platform.metadata.property.metadata_factory.serializer',
            'api_platform.metadata.property.metadata_factory.validator',
            'api_platform.metadata.property.metadata_factory.xml',
            'api_platform.metadata.property.metadata_factory.yaml',
            'api_platform.metadata.property.name_collection_factory.cached',
            'api_platform.metadata.property.name_collection_factory.inherited',
            'api_platform.metadata.property.name_collection_factory.property_info',
            'api_platform.metadata.property.name_collection_factory.xml',
            'api_platform.metadata.property.name_collection_factory.yaml',
            'api_platform.metadata.resource.metadata_factory.annotation',
            'api_platform.metadata.resource.metadata_factory.cached',
            'api_platform.metadata.resource.metadata_factory.operation',
            'api_platform.metadata.resource.metadata_factory.php_doc',
            'api_platform.metadata.resource.metadata_factory.short_name',
            'api_platform.metadata.resource.metadata_factory.xml',
            'api_platform.metadata.resource.metadata_factory.yaml',
            'api_platform.metadata.resource.name_collection_factory.annotation',
            'api_platform.metadata.resource.name_collection_factory.cached',
            'api_platform.metadata.resource.name_collection_factory.xml',
            'api_platform.metadata.resource.name_collection_factory.yaml',
            'api_platform.negotiator',
            'api_platform.operation_method_resolver',
            'api_platform.operation_path_resolver.custom',
            'api_platform.operation_path_resolver.dash',
            'api_platform.operation_path_resolver.router',
            'api_platform.operation_path_resolver.underscore',
            'api_platform.problem.encoder',
            'api_platform.problem.normalizer.constraint_violation_list',
            'api_platform.problem.normalizer.error',
            'api_platform.resource_class_resolver',
            'api_platform.route_loader',
            'api_platform.route_name_resolver',
            'api_platform.route_name_resolver.cached',
            'api_platform.router',
            'api_platform.serializer.context_builder',
            'api_platform.serializer.normalizer.item',
            'api_platform.swagger.action.ui',
            'api_platform.swagger.command.swagger_command',
            'api_platform.swagger.normalizer.documentation',
        ];

        foreach ($definitions as $definition) {
            $containerBuilderProphecy->setDefinition($definition, $definitionArgument)->shouldBeCalled();
        }

        $aliases = [
            'api_platform.action.delete_item' => 'api_platform.action.placeholder',
            'api_platform.action.get_collection' => 'api_platform.action.placeholder',
            'api_platform.action.get_item' => 'api_platform.action.placeholder',
            'api_platform.action.post_collection' => 'api_platform.action.placeholder',
            'api_platform.action.put_item' => 'api_platform.action.placeholder',
            'api_platform.metadata.property.metadata_factory' => 'api_platform.metadata.property.metadata_factory.annotation',
            'api_platform.metadata.property.name_collection_factory' => 'api_platform.metadata.property.name_collection_factory.property_info',
            'api_platform.metadata.resource.metadata_factory' => 'api_platform.metadata.resource.metadata_factory.annotation',
            'api_platform.metadata.resource.name_collection_factory' => 'api_platform.metadata.resource.name_collection_factory.annotation',
            'api_platform.operation_path_resolver' => 'api_platform.operation_path_resolver.router',
            'api_platform.operation_path_resolver.default' => 'api_platform.operation_path_resolver.underscore',
            'api_platform.property_accessor' => 'property_accessor',
            'api_platform.property_info' => 'property_info',
            'api_platform.serializer' => 'serializer',
        ];

        foreach ($aliases as $alias => $service) {
            $containerBuilderProphecy->setAlias($alias, $service)->shouldBeCalled();
        }

        $containerBuilderProphecy->getParameter('kernel.debug')->willReturn(false);

        return $containerBuilderProphecy;
    }
}
