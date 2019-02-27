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

    public function testProcess()
    {
        $pass = new MetadataAwareNameConverterPass();

        $arguments = [new Reference('serializer.mapping.class_metadata_factory'), new Reference('app.name_converter')];

        $definition = $this->prophesize(Definition::class);
        $definition->getArguments()->willReturn($arguments)->shouldBeCalled();
        $definition->getArgument(1)->willReturn($arguments[1])->shouldBeCalled();

        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);
        $containerBuilderProphecy->hasAlias('api_platform.name_converter')->willReturn(false)->shouldBeCalled();
        $containerBuilderProphecy->hasDefinition('serializer.name_converter.metadata_aware')->willReturn(true)->shouldBeCalled();
        $containerBuilderProphecy->getDefinition('serializer.name_converter.metadata_aware')->willReturn($definition)->shouldBeCalled();
        $containerBuilderProphecy->setAlias('api_platform.name_converter', 'serializer.name_converter.metadata_aware')->shouldBeCalled();

        $pass->process($containerBuilderProphecy->reveal());
    }

    public function testProcessWithNameConverter()
    {
        $pass = new MetadataAwareNameConverterPass();

        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);
        $containerBuilderProphecy->hasAlias('api_platform.name_converter')->willReturn(true)->shouldBeCalled();
        $containerBuilderProphecy->hasDefinition('serializer.name_converter.metadata_aware')->shouldNotBeCalled();
        $containerBuilderProphecy->getDefinition('serializer.name_converter.metadata_aware')->shouldNotBeCalled();
        $containerBuilderProphecy->setAlias('api_platform.name_converter', 'serializer.name_converter.metadata_aware')->shouldNotBeCalled();

        $pass->process($containerBuilderProphecy->reveal());
    }

    public function testProcessWithoutMetadataAwareDefinition()
    {
        $pass = new MetadataAwareNameConverterPass();

        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);
        $containerBuilderProphecy->hasAlias('api_platform.name_converter')->willReturn(false)->shouldBeCalled();
        $containerBuilderProphecy->hasDefinition('serializer.name_converter.metadata_aware')->willReturn(false)->shouldBeCalled();
        $containerBuilderProphecy->setAlias('api_platform.name_converter', 'serializer.name_converter.metadata_aware')->shouldNotBeCalled();

        $pass->process($containerBuilderProphecy->reveal());
    }

    public function testProcessWithMetadataAwareDefinitionSecondArgumentNull()
    {
        $pass = new MetadataAwareNameConverterPass();

        $arguments = [new Reference('serializer.mapping.class_metadata_factory'), null];

        $definition = $this->prophesize(Definition::class);
        $definition->getArguments()->willReturn($arguments)->shouldBeCalled();
        $definition->getArgument(1)->willReturn($arguments[1])->shouldBeCalled();

        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);
        $containerBuilderProphecy->hasAlias('api_platform.name_converter')->willReturn(false)->shouldBeCalled();
        $containerBuilderProphecy->hasDefinition('serializer.name_converter.metadata_aware')->willReturn(true)->shouldBeCalled();
        $containerBuilderProphecy->getDefinition('serializer.name_converter.metadata_aware')->willReturn($definition)->shouldBeCalled();
        $containerBuilderProphecy->setAlias('api_platform.name_converter', 'serializer.name_converter.metadata_aware')->shouldNotBeCalled();

        $pass->process($containerBuilderProphecy->reveal());
    }
}
