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

namespace ApiPlatform\Tests\Symfony\Bundle\DependencyInjection\Compiler;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Tests\ProphecyTrait;
use ApiPlatform\Symfony\Bundle\DependencyInjection\Compiler\DeprecateLegacyIriConverterPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\BaseNode;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

final class DeprecateLegacyIriConverterPassTest extends TestCase
{
    use ProphecyTrait;

    public function testProcess(): void
    {
        $deprecateIriConverterPass = new DeprecateLegacyIriConverterPass();

        $this->assertInstanceOf(CompilerPassInterface::class, $deprecateIriConverterPass);

        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);
        $aliasProphecy = $this->prophesize(Alias::class);
        $definitionProphecy = $this->prophesize(Definition::class);

        $containerBuilderProphecy
            ->hasDefinition(IriConverterInterface::class)
            ->willReturn(true);

        $containerBuilderProphecy
            ->hasAlias(IriConverterInterface::class)
            ->willReturn(true);

        $containerBuilderProphecy
            ->hasDefinition('api_platform.iri_converter.legacy')
            ->willReturn(true);

        $containerBuilderProphecy
            ->getDefinition(IriConverterInterface::class)
            ->willReturn($definitionProphecy->reveal())
            ->shouldBeCalled();

        $containerBuilderProphecy
            ->getAlias(IriConverterInterface::class)
            ->willReturn($aliasProphecy->reveal())
            ->shouldBeCalled();

        $containerBuilderProphecy
            ->getDefinition('api_platform.iri_converter.legacy')
            ->willReturn($definitionProphecy->reveal())
            ->shouldBeCalled();

        $setDeprecatedAliasArgs = method_exists(BaseNode::class, 'getDeprecation')
            ? ['api-platform/core', '2.7', 'Using "%alias_id%" is deprecated since API Platform 2.7. Use "ApiPlatform\Api\IriConverterInterface" instead.']
            : ['Using "%alias_id%" is deprecated since API Platform 2.7. Use "ApiPlatform\Api\IriConverterInterface" instead.'];

        $setDeprecatedDefinitionArgs = method_exists(BaseNode::class, 'getDeprecation')
            ? ['api-platform/core', '2.7', 'Using "%service_id%" is deprecated since API Platform 2.7. Use "ApiPlatform\Api\IriConverterInterface" instead.']
            : ['Using "%service_id%" is deprecated since API Platform 2.7. Use "ApiPlatform\Api\IriConverterInterface" instead.'];

        $aliasProphecy
            ->setDeprecated(...$setDeprecatedAliasArgs)
            ->willReturn($aliasProphecy->reveal())
            ->shouldBeCalled();

        $definitionProphecy
            ->setDeprecated(...$setDeprecatedDefinitionArgs)
            ->willReturn($definitionProphecy->reveal())
            ->shouldBeCalled();

        $deprecateIriConverterPass->process($containerBuilderProphecy->reveal());
    }

    public function testProcessWithoutDefinition(): void
    {
        $deprecateIriConverterPass = new DeprecateLegacyIriConverterPass();
        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);

        $containerBuilderProphecy
            ->hasDefinition(IriConverterInterface::class)
            ->willReturn(false);

        $containerBuilderProphecy
            ->hasAlias(IriConverterInterface::class)
            ->willReturn(false);

        $containerBuilderProphecy
            ->hasDefinition('api_platform.iri_converter.legacy')
            ->willReturn(false);

        $containerBuilderProphecy
            ->getDefinition(IriConverterInterface::class)
            ->shouldNotBeCalled();

        $containerBuilderProphecy
            ->getAlias(IriConverterInterface::class)
            ->shouldNotBeCalled();

        $containerBuilderProphecy
            ->getDefinition('api_platform.iri_converter.legacy')
            ->shouldNotBeCalled();

        $deprecateIriConverterPass->process($containerBuilderProphecy->reveal());
    }
}
