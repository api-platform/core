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

namespace ApiPlatform\Core\Tests\Bridge\Symfony\Bundle\DependencyInjection\Compiler;

use ApiPlatform\Core\Bridge\Symfony\Bundle\DependencyInjection\Compiler\MetadataAwareNameConverterPass;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
class MetadataAwareNameConverterPassTest extends TestCase
{
    public function testConstruct()
    {
        $this->assertInstanceOf(CompilerPassInterface::class, new MetadataAwareNameConverterPass());
    }

    public function testProcessFirstArgumentConfigured()
    {
        $pass = new MetadataAwareNameConverterPass();

        $definition = $this->prophesize(Definition::class);
        $definition->getArguments()->willReturn([0, 1])->shouldBeCalled();
        $definition->getArgument(1)->willReturn(new Reference('app.name_converter'))->shouldBeCalled();

        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);
        $containerBuilderProphecy->hasAlias('api_platform.name_converter')->shouldBeCalled()->willReturn(true);
        $containerBuilderProphecy->getAlias('api_platform.name_converter')->shouldBeCalled()->willReturn(Argument::any());
        $containerBuilderProphecy->hasDefinition('serializer.name_converter.metadata_aware')->willReturn(true)->shouldBeCalled();
        $containerBuilderProphecy->getDefinition('serializer.name_converter.metadata_aware')->willReturn($definition)->shouldBeCalled();
        $containerBuilderProphecy->setAlias('api_platform.name_converter', 'serializer.name_converter.metadata_aware')->shouldBeCalled();

        $pass->process($containerBuilderProphecy->reveal());
    }

    public function testProcessWithNameConverter()
    {
        $pass = new MetadataAwareNameConverterPass();

        $reference = new Reference('app.name_converter');

        $definition = $this->prophesize(Definition::class);
        $definition->getArguments()->willReturn([0, 1])->shouldBeCalled();
        $definition->getArgument(1)->willReturn(null)->shouldBeCalled();
        $definition->setArgument(1, $reference)->shouldBeCalled();

        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);
        $containerBuilderProphecy->hasAlias('api_platform.name_converter')->willReturn(true)->shouldBeCalled();
        $containerBuilderProphecy->getAlias('api_platform.name_converter')->shouldBeCalled()->willReturn(new Alias('app.name_converter'));
        $containerBuilderProphecy->hasDefinition('serializer.name_converter.metadata_aware')->shouldBeCalled()->willReturn(true);
        $containerBuilderProphecy->getDefinition('serializer.name_converter.metadata_aware')->shouldBeCalled()->willReturn($definition);
        $containerBuilderProphecy->setAlias('api_platform.name_converter', 'serializer.name_converter.metadata_aware')->shouldBeCalled();

        $pass->process($containerBuilderProphecy->reveal());
    }

    public function testProcessWithoutMetadataAwareDefinition()
    {
        $pass = new MetadataAwareNameConverterPass();

        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);
        $containerBuilderProphecy->hasDefinition('serializer.name_converter.metadata_aware')->willReturn(false)->shouldBeCalled();
        $containerBuilderProphecy->setAlias('api_platform.name_converter', 'serializer.name_converter.metadata_aware')->shouldNotBeCalled();

        $pass->process($containerBuilderProphecy->reveal());
    }

    public function testProcessOnlyOneArg()
    {
        $pass = new MetadataAwareNameConverterPass();

        $definition = $this->prophesize(Definition::class);
        $definition->getArguments()->willReturn([0])->shouldBeCalled();
        $definition->addArgument(new Reference('app.name_converter'))->shouldBeCalled();

        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);
        $containerBuilderProphecy->hasDefinition('serializer.name_converter.metadata_aware')->willReturn(true)->shouldBeCalled();
        $containerBuilderProphecy->hasAlias('api_platform.name_converter')->shouldBeCalled()->willReturn(true);
        $containerBuilderProphecy->getAlias('api_platform.name_converter')->shouldBeCalled()->willReturn(new Alias('app.name_converter'));
        $containerBuilderProphecy->setAlias('api_platform.name_converter', 'serializer.name_converter.metadata_aware')->shouldBeCalled();
        $containerBuilderProphecy->getDefinition('serializer.name_converter.metadata_aware')->shouldBeCalled()->willReturn($definition);

        $pass->process($containerBuilderProphecy->reveal());
    }
}
