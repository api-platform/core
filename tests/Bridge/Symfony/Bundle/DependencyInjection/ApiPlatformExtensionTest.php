<?php

/*
 * This file is part of the API Platform Builder package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Tests\Symfony\Bridge\Bundle\DependencyInjection;

use ApiPlatform\Core\Bridge\Symfony\Bundle\DependencyInjection\ApiPlatformExtension;
use Prophecy\Argument;
use Symfony\Component\Config\Resource\ResourceInterface;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Extension\ConfigurationExtensionInterface;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ApiPlatformExtensionTest extends \PHPUnit_Framework_TestCase
{
    const DEFAULT_CONFIG = [
        'api_platform' => [
            'title' => 'title',
            'description' => 'description',
        ],
    ];
    private $extension;

    public function setUp()
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
        $containerBuilderProphecy->getExtensionConfig('framework')->willReturn(['serializer' => ['enabled' => false]])->shouldBeCalled();
        $containerBuilderProphecy->prependExtensionConfig('framework', Argument::any())->willReturn(null);
        $containerBuilderProphecy->prependExtensionConfig('framework', Argument::that(function (array $config) {
            return array_key_exists('serializer', $config);
        }))->shouldNotBeCalled();
        $containerBuilder = $containerBuilderProphecy->reveal();

        $this->extension->prepend($containerBuilder);
    }

    public function testNotPrependPropertyInfoWhenConfigExist()
    {
        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);
        $containerBuilderProphecy->getExtensionConfig('framework')->willReturn(['property_info' => ['enabled' => false]])->shouldBeCalled();
        $containerBuilderProphecy->prependExtensionConfig('framework', Argument::any())->willReturn(null);
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
        $containerBuilderProphecy->getExtensionConfig('framework')->willReturn(['serializer' => []])->shouldBeCalled();
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

    public function testEnableFosUser()
    {
        $containerBuilderProphecy = $this->getContainerBuilderProphecy();
        $containerBuilderProphecy->setDefinition('api_platform.fos_user.event_listener', Argument::type(Definition::class))->shouldBeCalled();
        $containerBuilder = $containerBuilderProphecy->reveal();

        $this->extension->load(array_merge_recursive(self::DEFAULT_CONFIG, ['api_builder' => ['enable_fos_user' => true]]), $containerBuilder);
    }

    private function getContainerBuilderProphecy()
    {
        $definitionArgument = Argument::that(function ($argument) {
            return $argument instanceof Definition || $argument instanceof DefinitionDecorator;
        });

        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);
        $containerBuilderProphecy->getParameter('kernel.bundles')->willReturn([])->shouldBeCalled();

        $parameters = [
            'api_platform.title' => 'title',
            'api_platform.description' => 'description',
            'api_platform.supported_formats' => ['jsonld'],
            'api_platform.collection.order' => null,
            'api_platform.collection.order_parameter_name' => 'order',
            'api_platform.collection.pagination.enabled' => true,
            'api_platform.collection.pagination.client_enabled' => false,
            'api_platform.collection.pagination.client_items_per_page' => false,
            'api_platform.collection.pagination.items_per_page' => 30,
            'api_platform.collection.pagination.page_parameter_name' => 'page',
            'api_platform.collection.pagination.enabled_parameter_name' => 'pagination',
            'api_platform.collection.pagination.items_per_page_parameter_name' => 'itemsPerPage',
        ];
        foreach ($parameters as $key => $value) {
            $containerBuilderProphecy->setParameter($key, $value)->shouldBeCalled();
        }

        $containerBuilderProphecy->addResource(Argument::type(ResourceInterface::class))->shouldBeCalled();
        $containerBuilderProphecy->hasExtension('http://symfony.com/schema/dic/services')->shouldBeCalled();

        $aliases = [
            'api_platform.serializer',
            'api_platform.property_accessor',
            'api_platform.property_info',
            'api_platform.metadata.resource.factory.collection',
            'api_platform.metadata.resource.factory.item',
            'api_platform.metadata.property.factory.collection',
            'api_platform.metadata.property.factory.item',
            'api_platform.item_data_provider',
            'api_platform.collection_data_provider',
            'api_platform.action.delete_item',
        ];
        foreach ($aliases as $alias) {
            $containerBuilderProphecy->setAlias($alias, Argument::type(Alias::class))->shouldBeCalled();
        }

        $definitionProphecy = $this->prophesize(Definition::class);
        $definitionProphecy->addArgument([])->shouldBeCalled();
        $definition = $definitionProphecy->reveal();
        $containerBuilderProphecy->getDefinition('api_platform.metadata.resource.factory.collection.annotation')->willReturn($definition);

        $definitions = [
            'api_platform.filters',
            'api_platform.resource_class_resolver',
            'api_platform.operation_method_resolver',
            'api_platform.metadata.resource.factory.collection.annotation',
            'api_platform.metadata.resource.factory.item.annotation',
            'api_platform.metadata.resource.factory.item.php_doc',
            'api_platform.metadata.resource.factory.item.short_name',
            'api_platform.metadata.resource.factory.item.operation',
            'api_platform.metadata.property.factory.collection.property_info',
            'api_platform.metadata.property.factory.item.annotation',
            'api_platform.metadata.property.factory.item.property_info',
            'api_platform.metadata.property.factory.item.serializer',
            'api_platform.metadata.property.factory.item.validator',
            'api_platform.metadata.resource.factory.collection.annotation',
            'api_platform.format_negotiator',
            'api_platform.route_loader',
            'api_platform.router',
            'api_platform.iri_converter',
            'api_platform.listener.request.format',
            'api_platform.listener.view.validation',
            'api_platform.listener.request.format',
            'api_platform.action.get_collection',
            'api_platform.action.post_collection',
            'api_platform.action.get_item',
            'api_platform.action.put_item',
            'api_platform.doctrine.metadata_factory',
            'api_platform.doctrine.orm.collection_data_provider',
            'api_platform.doctrine.orm.item_data_provider',
            'api_platform.doctrine.orm.default.item_data_provider',
            'api_platform.doctrine.orm.search_filter',
            'api_platform.doctrine.orm.order_filter',
            'api_platform.doctrine.orm.date_filter',
            'api_platform.doctrine.orm.range_filter',
            'api_platform.doctrine.orm.default.collection_data_provider',
            'api_platform.doctrine.orm.default.item_data_provider',
            'api_platform.doctrine.orm.metadata.property.factory.item',
            'api_platform.doctrine.orm.query_extension.eager_loading',
            'api_platform.doctrine.orm.query_extension.filter',
            'api_platform.doctrine.orm.query_extension.pagination',
            'api_platform.doctrine.orm.query_extension.order',
            'api_platform.doctrine.listener.view.manager',
            'api_platform.jsonld.entrypoint_builder',
            'api_platform.jsonld.context_builder',
            'api_platform.jsonld.listener.view.responder',
            'api_platform.jsonld.normalizer.item',
            'api_platform.jsonld.encoder',
            'api_platform.jsonld.listener.view.responder',
            'api_platform.jsonld.action.context',
            'api_platform.jsonld.action.entrypoint',
            'api_platform.hydra.documentation_builder',
            'api_platform.hydra.listener.validation_exception',
            'api_platform.hydra.listener.link_header_response',
            'api_platform.hydra.listener.request_exception',
            'api_platform.hydra.normalizer.collection',
            'api_platform.hydra.normalizer.paged_collection',
            'api_platform.hydra.normalizer.collection_filters',
            'api_platform.hydra.normalizer.constraint_violation_list',
            'api_platform.hydra.normalizer.error',
            'api_platform.hydra.action.documentation',
            'api_platform.hydra.action.exception',
        ];
        foreach ($definitions as $definition) {
            $containerBuilderProphecy->setDefinition($definition, $definitionArgument)->shouldBeCalled();
        }

        $aliases = [
            'api_platform.metadata.resource.factory.collection' => 'api_platform.metadata.resource.factory.collection.annotation',
        ];

        foreach ($aliases as $alias => $service) {
            $containerBuilderProphecy->setAlias($alias, $service)->shouldBeCalled();
        }

        return $containerBuilderProphecy;
    }
}
