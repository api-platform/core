<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Tests\DependencyInjection\Compiler;

use Dunglas\ApiBundle\DependencyInjection\Compiler\ResourcePass;
use Prophecy\Argument;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ResourcePassTest extends \PHPUnit_Framework_TestCase
{
    public function testProcess()
    {
        $this->markTestSkipped('To be refactored');

        $resourcePass = new ResourcePass();

        $this->assertInstanceOf('Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface', $resourcePass);

        $resourceCollectionDefinitionProphecy = $this->prophesize('Symfony\Component\DependencyInjection\Definition');
        $resourceCollectionDefinitionProphecy->addMethodCall('init', Argument::type('array'))->shouldBeCalled();
        $resourceCollectionDefinition = $resourceCollectionDefinitionProphecy->reveal();

        $customResourceDefinitionProphecy = $this->prophesize('Symfony\Component\DependencyInjection\Definition');
        $customResourceDefinitionProphecy->getClass()->willReturn('Foo\Bar')->shouldBeCalled();
        $customResourceDefinitionProphecy->hasMethodCall(Argument::any())->shouldNotBeCalled();
        $customResourceDefinitionProphecy->addMethodCall(Argument::any())->shouldNotBeCalled();
        $customResourceDefinition = $customResourceDefinitionProphecy->reveal();

        $builtinResourceDefinitionProphecy = $this->prophesize('Symfony\Component\DependencyInjection\Definition');
        $builtinResourceDefinitionProphecy->getClass()->willReturn('Dunglas\ApiBundle\Api\Resource')->shouldBeCalled();
        $builtinResourceDefinitionProphecy->hasMethodCall('initItemOperations')->willReturn(true)->shouldBeCalled();
        $builtinResourceDefinitionProphecy->addMethodCall('initItemOperations')->shouldNotBeCalled();
        $builtinResourceDefinitionProphecy->hasMethodCall('initCollectionOperations', Argument::any())->willReturn(true)->shouldBeCalled();
        $builtinResourceDefinitionProphecy->addMethodCall('initCollectionOperations', Argument::any())->shouldNotBeCalled();
        $builtinResourceDefinition = $builtinResourceDefinitionProphecy->reveal();

        $innerResourceDefinitionProphecy = $this->prophesize('Symfony\Component\DependencyInjection\Definition');
        $innerResourceDefinitionProphecy->getClass()->willReturn('Dunglas\ApiBundle\Api\Resource')->shouldBeCalled();
        $innerResourceDefinition = $innerResourceDefinitionProphecy->reveal();

        $decoratedResourceDefinitionProphecy = $this->prophesize('Symfony\Component\DependencyInjection\DefinitionDecorator');
        $decoratedResourceDefinitionProphecy->getClass()->willReturn(false)->shouldBeCalled();
        $decoratedResourceDefinitionProphecy->getParent()->willReturn('inner_resource')->shouldBeCalled();
        $decoratedResourceDefinitionProphecy->hasMethodCall('initItemOperations')->willReturn(false)->shouldBeCalled();
        $decoratedResourceDefinitionProphecy->addMethodCall('initItemOperations', Argument::type('array'))->shouldBeCalled();
        $decoratedResourceDefinitionProphecy->hasMethodCall('initCollectionOperations')->willReturn(false)->shouldBeCalled();
        $decoratedResourceDefinitionProphecy->addMethodCall('initCollectionOperations', Argument::type('array'))->shouldBeCalled();
        $decoratedResourceDefinition = $decoratedResourceDefinitionProphecy->reveal();

        $containerBuilderProphecy = $this->prophesize('Symfony\Component\DependencyInjection\ContainerBuilder');
        $containerBuilderProphecy->getDefinition('api.resource_collection')->willReturn($resourceCollectionDefinition)->shouldBeCalled();
        $containerBuilderProphecy->getDefinition('custom_resource')->willReturn($customResourceDefinition)->shouldBeCalled();
        $containerBuilderProphecy->getDefinition('builtin_resource')->willReturn($builtinResourceDefinition)->shouldBeCalled();
        $containerBuilderProphecy->getDefinition('decorated_resource')->willReturn($decoratedResourceDefinition)->shouldBeCalled();
        $containerBuilderProphecy->getDefinition('inner_resource')->willReturn($innerResourceDefinition)->shouldBeCalled();
        $containerBuilderProphecy->findTaggedServiceIds('api.resource')->willReturn([
            'custom_resource' => [],
            'builtin_resource' => [],
            'decorated_resource' => [],
        ])->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('decorated_resource.item_operation.GET', Argument::type('Symfony\Component\DependencyInjection\Definition'))->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('decorated_resource.item_operation.PUT', Argument::type('Symfony\Component\DependencyInjection\Definition'))->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('decorated_resource.item_operation.DELETE', Argument::type('Symfony\Component\DependencyInjection\Definition'))->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('decorated_resource.collection_operation.GET', Argument::type('Symfony\Component\DependencyInjection\Definition'))->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('decorated_resource.collection_operation.POST', Argument::type('Symfony\Component\DependencyInjection\Definition'))->shouldBeCalled();
        $containerBuilder = $containerBuilderProphecy->reveal();

        $resourcePass->process($containerBuilder);
    }
}
