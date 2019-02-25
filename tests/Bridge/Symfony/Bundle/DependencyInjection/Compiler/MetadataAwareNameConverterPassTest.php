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

/**
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
class MetadataAwareNameConverterPassTest extends TestCase
{
    public function testProcess()
    {
        $pass = new MetadataAwareNameConverterPass();
        $this->assertInstanceOf(CompilerPassInterface::class, $pass);

        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);
        $containerBuilderProphecy->hasAlias('api_platform.name_converter')->shouldBeCalled()->willReturn(false);
        $containerBuilderProphecy->hasDefinition('serializer.name_converter.metadata_aware')->shouldBeCalled()->willReturn(true);
        $containerBuilderProphecy->setAlias('api_platform.name_converter', 'serializer.name_converter.metadata_aware')->shouldBeCalled();

        $pass->process($containerBuilderProphecy->reveal());
    }

    public function testProcessWithNameConverter()
    {
        $pass = new MetadataAwareNameConverterPass();
        $this->assertInstanceOf(CompilerPassInterface::class, $pass);

        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);
        $containerBuilderProphecy->hasAlias('api_platform.name_converter')->shouldBeCalled()->willReturn(true);
        $containerBuilderProphecy->hasDefinition('serializer.name_converter.metadata_aware')->shouldNotBeCalled()->willReturn(true);
        $containerBuilderProphecy->setAlias('api_platform.name_converter', 'serializer.name_converter.metadata_aware')->shouldNotBeCalled();

        $pass->process($containerBuilderProphecy->reveal());
    }

    public function testProcessWithoutMetadataAwareDefinition()
    {
        $pass = new MetadataAwareNameConverterPass();
        $this->assertInstanceOf(CompilerPassInterface::class, $pass);

        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);
        $containerBuilderProphecy->hasAlias('api_platform.name_converter')->shouldBeCalled()->willReturn(false);
        $containerBuilderProphecy->hasDefinition('serializer.name_converter.metadata_aware')->shouldBeCalled()->willReturn(false);
        $containerBuilderProphecy->setAlias('api_platform.name_converter', 'serializer.name_converter.metadata_aware')->shouldNotBeCalled();

        $pass->process($containerBuilderProphecy->reveal());
    }
}
