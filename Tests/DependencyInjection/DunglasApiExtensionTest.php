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

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class DunglasApiExtensionTest extends \PHPUnit_Framework_TestCase
{
    private static $defaultConfig = [
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

        $this->assertInstanceOf('Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface', $this->extension);
        $this->assertInstanceOf('Symfony\Component\DependencyInjection\Extension\ExtensionInterface', $this->extension);
        $this->assertInstanceOf('Symfony\Component\DependencyInjection\Extension\ConfigurationExtensionInterface', $this->extension);
    }

    public function testNotPrependWhenNull()
    {
        $containerBuilderProphecy = $this->prophesize('Symfony\Component\DependencyInjection\ContainerBuilder');
        $containerBuilderProphecy->getExtensionConfig('framework')->willReturn(null)->shouldBeCalled();
        $containerBuilderProphecy->prependExtensionConfig('framework', Argument::type('array'))->shouldNotBeCalled();
        $containerBuilder = $containerBuilderProphecy->reveal();

        $this->extension->prepend($containerBuilder);
    }

    public function testNotPrependWhenConfigExist()
    {
        $containerBuilderProphecy = $this->prophesize('Symfony\Component\DependencyInjection\ContainerBuilder');
        $containerBuilderProphecy->getExtensionConfig('framework')->willReturn(['serializer' => ['enabled' => false]])->shouldBeCalled();
        $containerBuilderProphecy->prependExtensionConfig('framework', Argument::type('array'))->shouldNotBeCalled();
        $containerBuilder = $containerBuilderProphecy->reveal();

        $this->extension->prepend($containerBuilder);
    }

    public function testPrependWhenNotConfigured()
    {
        $containerBuilderProphecy = $this->prophesize('Symfony\Component\DependencyInjection\ContainerBuilder');
        $containerBuilderProphecy->getExtensionConfig('framework')->willReturn([])->shouldBeCalled();
        $containerBuilderProphecy->prependExtensionConfig('framework', Argument::type('array'))->shouldBeCalled();
        $containerBuilder = $containerBuilderProphecy->reveal();

        $this->extension->prepend($containerBuilder);
    }

    public function testPrependWhenNotEnabled()
    {
        $containerBuilderProphecy = $this->prophesize('Symfony\Component\DependencyInjection\ContainerBuilder');
        $containerBuilderProphecy->getExtensionConfig('framework')->willReturn(['serializer' => []])->shouldBeCalled();
        $containerBuilderProphecy->prependExtensionConfig('framework', Argument::type('array'))->shouldBeCalled();
        $containerBuilder = $containerBuilderProphecy->reveal();

        $this->extension->prepend($containerBuilder);
    }

    public function testLoadDefaultConfig()
    {
        $containerBuilderProphecy = $this->getContainerBuilderProphecy();
        $containerBuilderProphecy->removeDefinition('api.cache_warmer.metadata')->shouldBeCalled();
        $containerBuilder = $containerBuilderProphecy->reveal();

        $this->extension->load(self::$defaultConfig, $containerBuilder);
    }

    public function testEnableFosUser()
    {
        $containerBuilderProphecy = $this->getContainerBuilderProphecy();
        $containerBuilderProphecy->setDefinition('api.fos_user.event_subscriber', Argument::type('Symfony\Component\DependencyInjection\Definition'))->shouldBeCalled();
        $containerBuilderProphecy->removeDefinition('api.cache_warmer.metadata')->shouldBeCalled();
        $containerBuilder = $containerBuilderProphecy->reveal();

        $this->extension->load(array_merge_recursive(self::$defaultConfig, ['dunglas_api' => ['enable_fos_user' => true]]), $containerBuilder);
    }

    public function testEnableCache()
    {
        $metadataFactoryDefinitionProphecy = $this->prophesize('Symfony\Component\DependencyInjection\Definition');
        $metadataFactoryDefinitionProphecy->addArgument(Argument::type('Symfony\Component\DependencyInjection\Reference'))->shouldBeCalled();
        $metadataFactoryDefinition = $metadataFactoryDefinitionProphecy->reveal();

        $containerBuilderProphecy = $this->getContainerBuilderProphecy();
        $containerBuilderProphecy->getParameter('kernel.root_dir')->willReturn('test')->shouldBeCalled();
        $containerBuilderProphecy->setParameter('api.mapping.cache.prefix', Argument::type('string'))->shouldBeCalled();
        $containerBuilderProphecy->getDefinition('api.mapping.class_metadata_factory')->willReturn($metadataFactoryDefinition)->shouldBeCalled();
        $containerBuilder = $containerBuilderProphecy->reveal();

        $this->extension->load(array_merge_recursive(self::$defaultConfig, ['dunglas_api' => ['cache' => true]]), $containerBuilder);
    }

    private function getContainerBuilderProphecy()
    {
        $this->markTestSkipped('Must be refactored');

        $parameterBagProphecy = $this->prophesize('Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface');
        $parameterBagProphecy->add(Argument::any())->shouldBeCalled();
        $parameterBag = $parameterBagProphecy->reveal();

        $definitionArgument = Argument::type('Symfony\Component\DependencyInjection\Definition');

        $containerBuilderProphecy = $this->prophesize('Symfony\Component\DependencyInjection\ContainerBuilder');
        $containerBuilderProphecy->getParameterBag()->willReturn($parameterBag)->shouldBeCalled();
        $containerBuilderProphecy->setParameter('api.title', 'title')->shouldBeCalled();
        $containerBuilderProphecy->setParameter('api.description', 'description')->shouldBeCalled();
        $containerBuilderProphecy->setParameter('api.supported_formats', ['jsonld'])->shouldBeCalled();
        $containerBuilderProphecy->setParameter('api.collection.filter_name.order', 'order')->shouldBeCalled();
        $containerBuilderProphecy->setParameter('api.collection.order', null)->shouldBeCalled();
        $containerBuilderProphecy->setParameter('api.collection.pagination.page_parameter_name', 'page')->shouldBeCalled();
        $containerBuilderProphecy->setParameter('api.collection.pagination.items_per_page.number', 30)->shouldBeCalled();
        $containerBuilderProphecy->setParameter('api.collection.pagination.items_per_page.enable_client_request', false)->shouldBeCalled();
        $containerBuilderProphecy->setParameter('api.collection.pagination.items_per_page.parameter_name', 'itemsPerPage')->shouldBeCalled();
        $containerBuilderProphecy->hasExtension('http://symfony.com/schema/dic/services')->shouldBeCalled();
        $containerBuilderProphecy->addResource(Argument::type('Symfony\Component\Config\Resource\ResourceInterface'))->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api.resource', $definitionArgument)->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api.resource_collection', $definitionArgument)->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api.format_negotiator', $definitionArgument)->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api.data_provider', $definitionArgument)->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api.operation_factory', $definitionArgument)->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api.route_loader', $definitionArgument)->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api.router', $definitionArgument)->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api.iri_converter', $definitionArgument)->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api.listener.request.resource_type', $definitionArgument)->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api.listener.view.validation', $definitionArgument)->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api.listener.request.format', $definitionArgument)->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api.action.get_collection', $definitionArgument)->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api.action.get_collection', $definitionArgument)->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api.action.post_collection', $definitionArgument)->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api.action.get_item', $definitionArgument)->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api.action.put_item', $definitionArgument)->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api.action.delete_item', $definitionArgument)->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api.property_info', $definitionArgument)->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api.property_info.doctrine_extractor', $definitionArgument)->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api.property_info.php_doc_extractor', $definitionArgument)->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api.property_info.setter_extractor', $definitionArgument)->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api.cache_warmer.metadata', $definitionArgument)->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api.mapping.class_metadata_factory', $definitionArgument)->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api.mapping.attribute_metadata_factory', $definitionArgument)->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api.mapping.cache.apc', $definitionArgument)->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api.mapping.loaders.chain', $definitionArgument)->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api.mapping.loaders.serializer_metadata', $definitionArgument)->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api.mapping.loaders.validator_metadata', $definitionArgument)->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api.mapping.loaders.reflection', $definitionArgument)->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api.mapping.loaders.phpdoc', $definitionArgument)->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api.mapping.loaders.annotation', $definitionArgument)->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api.doctrine.mapping.loaders.identifier', $definitionArgument)->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api.doctrine.metadata_factory', $definitionArgument)->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api.doctrine.orm.data_provider', $definitionArgument)->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api.doctrine.orm.default_data_provider', $definitionArgument)->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api.doctrine.orm.search_filter', $definitionArgument)->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api.doctrine.orm.order_filter', $definitionArgument)->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api.doctrine.orm.date_filter', $definitionArgument)->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api.doctrine.listener.view.manager', $definitionArgument)->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api.jsonld.entrypoint_builder', $definitionArgument)->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api.jsonld.context_builder', $definitionArgument)->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api.jsonld.resource_context_builder_listener', $definitionArgument)->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api.jsonld.normalizer.item', $definitionArgument)->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api.jsonld.normalizer.datetime', $definitionArgument)->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api.jsonld.encoder', $definitionArgument)->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api.jsonld.listener.view.responder', $definitionArgument)->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api.jsonld.action.context', $definitionArgument)->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api.jsonld.action.entrypoint', $definitionArgument)->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api.hydra.documentation_builder', $definitionArgument)->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api.hydra.listener.link_header_response', $definitionArgument)->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api.hydra.listener.request_exception', $definitionArgument)->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api.hydra.normalizer.collection', $definitionArgument)->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api.hydra.normalizer.constraint_violation_list', $definitionArgument)->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api.hydra.normalizer.error', $definitionArgument)->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api.hydra.action.documentation', $definitionArgument)->shouldBeCalled();

        return $containerBuilderProphecy;
    }
}
