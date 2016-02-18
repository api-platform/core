<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Tests\DependencyInjection;

use Dunglas\ApiBundle\DependencyInjection\DunglasApiExtension;
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
class DunglasApiExtensionTest extends \PHPUnit_Framework_TestCase
{
    const DEFAULT_CONFIG = [
        'dunglas_api' => [
            'title' => 'title',
            'description' => 'description',
        ],
    ];
    private $extension;

    public function setUp()
    {
        $this->extension = new DunglasApiExtension();
    }

    public function tearDown()
    {
        unset($this->extension);
    }

    public function testConstruct()
    {
        $this->extension = new DunglasApiExtension();

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
        $containerBuilderProphecy->setDefinition('api.fos_user.event_listener', Argument::type(Definition::class))->shouldBeCalled();
        $containerBuilder = $containerBuilderProphecy->reveal();

        $this->extension->load(array_merge_recursive(self::DEFAULT_CONFIG, ['dunglas_api' => ['enable_fos_user' => true]]), $containerBuilder);
    }

    private function getContainerBuilderProphecy()
    {
        $definitionArgument = Argument::that(function ($argument) {
            return $argument instanceof Definition || $argument instanceof DefinitionDecorator;
        });

        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);
        $containerBuilderProphecy->getParameter('kernel.bundles')->willReturn([])->shouldBeCalled();

        $parameters = [
            'api.title' => 'title',
            'api.description' => 'description',
            'api.supported_formats' => ['jsonld'],
            'api.collection.order' => null,
            'api.collection.order_parameter_name' => 'order',
            'api.collection.pagination.enabled' => true,
            'api.collection.pagination.client_enabled' => false,
            'api.collection.pagination.client_items_per_page' => false,
            'api.collection.pagination.items_per_page' => 30,
            'api.collection.pagination.page_parameter_name' => 'page',
            'api.collection.pagination.enabled_parameter_name' => 'pagination',
            'api.collection.pagination.items_per_page_parameter_name' => 'itemsPerPage',
        ];
        foreach ($parameters as $key => $value) {
            $containerBuilderProphecy->setParameter($key, $value)->shouldBeCalled();
        }

        $containerBuilderProphecy->addResource(Argument::type(ResourceInterface::class))->shouldBeCalled();
        $containerBuilderProphecy->hasExtension('http://symfony.com/schema/dic/services')->shouldBeCalled();

        $aliases = [
            'api.serializer',
            'api.property_accessor',
            'api.property_info',
            'api.metadata.resource.factory.collection',
            'api.metadata.resource.factory.item',
            'api.metadata.property.factory.collection',
            'api.metadata.property.factory.item',
            'api.item_data_provider',
            'api.collection_data_provider',
        ];
        foreach ($aliases as $alias) {
            $containerBuilderProphecy->setAlias($alias, Argument::type(Alias::class))->shouldBeCalled();
        }

        $definitionProphecy = $this->prophesize(Definition::class);
        $definitionProphecy->addArgument([])->shouldBeCalled();
        $definition = $definitionProphecy->reveal();
        $containerBuilderProphecy->getDefinition('api.metadata.resource.factory.collection.annotation')->willReturn($definition);

        $definitions = [
            'api.filters',
            'api.resource_class_resolver',
            'api.operation_method_resolver',
            'api.metadata.resource.factory.collection.annotation',
            'api.metadata.resource.factory.item.annotation',
            'api.metadata.resource.factory.item.php_doc',
            'api.metadata.resource.factory.item.short_name',
            'api.metadata.resource.factory.item.operation',
            'api.metadata.property.factory.collection.property_info',
            'api.metadata.property.factory.item.annotation',
            'api.metadata.property.factory.item.property_info',
            'api.metadata.property.factory.item.serializer',
            'api.metadata.property.factory.item.validator',
            'api.metadata.resource.factory.collection.annotation',
            'api.format_negotiator',
            'api.route_loader',
            'api.router',
            'api.iri_converter',
            'api.listener.request.format',
            'api.listener.view.validation',
            'api.listener.request.format',
            'api.action.get_collection',
            'api.action.post_collection',
            'api.action.get_item',
            'api.action.put_item',
            'api.action.delete_item',
            'api.doctrine.metadata_factory',
            'api.doctrine.orm.collection_data_provider',
            'api.doctrine.orm.item_data_provider',
            'api.doctrine.orm.default.item_data_provider',
            'api.doctrine.orm.search_filter',
            'api.doctrine.orm.order_filter',
            'api.doctrine.orm.date_filter',
            'api.doctrine.orm.range_filter',
            'api.doctrine.orm.default.collection_data_provider',
            'api.doctrine.orm.default.item_data_provider',
            'api.doctrine.orm.metadata.property.factory.item',
            'api.doctrine.orm.query_extension.eager_loading',
            'api.doctrine.orm.query_extension.filter',
            'api.doctrine.orm.query_extension.pagination',
            'api.doctrine.orm.query_extension.order',
            'api.doctrine.listener.view.manager',
            'api.jsonld.entrypoint_builder',
            'api.jsonld.context_builder',
            'api.jsonld.listener.view.responder',
            'api.jsonld.normalizer.item',
            'api.jsonld.normalizer.datetime',
            'api.jsonld.encoder',
            'api.jsonld.listener.view.responder',
            'api.jsonld.action.context',
            'api.jsonld.action.entrypoint',
            'api.hydra.documentation_builder',
            'api.hydra.listener.validation_exception',
            'api.hydra.listener.link_header_response',
            'api.hydra.listener.request_exception',
            'api.hydra.normalizer.collection',
            'api.hydra.normalizer.paged_collection',
            'api.hydra.normalizer.collection_filters',
            'api.hydra.normalizer.constraint_violation_list',
            'api.hydra.normalizer.error',
            'api.hydra.action.documentation',
            'api.hydra.action.exception',
        ];
        foreach ($definitions as $definition) {
            $containerBuilderProphecy->setDefinition($definition, $definitionArgument)->shouldBeCalled();
        }

        $aliases = [
            'api.metadata.resource.factory.collection' => 'api.metadata.resource.factory.collection.annotation',
        ];

        foreach ($aliases as $alias => $service) {
            $containerBuilderProphecy->setAlias($alias, $service)->shouldBeCalled();
        }

        return $containerBuilderProphecy;
    }
}
