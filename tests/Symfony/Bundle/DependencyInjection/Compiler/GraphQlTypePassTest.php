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

use ApiPlatform\Symfony\Bundle\DependencyInjection\Compiler\GraphQlTypePass;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class GraphQlTypePassTest extends TestCase
{
    use ProphecyTrait;

    public function testProcess(): void
    {
        $filterPass = new GraphQlTypePass();

        $this->assertInstanceOf(CompilerPassInterface::class, $filterPass);

        $typeLocatorDefinitionProphecy = $this->prophesize(Definition::class);
        $typeLocatorDefinitionProphecy->addArgument(Argument::that(fn (array $arg) => !isset($arg['foo']) && isset($arg['my_id']) && $arg['my_id'] instanceof Reference))->willReturn($typeLocatorDefinitionProphecy->reveal())->shouldBeCalled();

        $typesFactoryDefinitionProphecy = $this->prophesize(Definition::class);
        $typesFactoryDefinitionProphecy->addArgument(['my_id'])->willReturn($typesFactoryDefinitionProphecy->reveal())->shouldBeCalled();

        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);
        $containerBuilderProphecy->getParameter('api_platform.graphql.enabled')->willReturn(true)->shouldBeCalled();
        $containerBuilderProphecy->findTaggedServiceIds('api_platform.graphql.type', true)->willReturn(['foo' => [], 'bar' => [['id' => 'my_id']]])->shouldBeCalled();
        $containerBuilderProphecy->getDefinition('api_platform.graphql.type_locator')->willReturn($typeLocatorDefinitionProphecy->reveal())->shouldBeCalled();
        $containerBuilderProphecy->getDefinition('api_platform.graphql.types_factory')->willReturn($typesFactoryDefinitionProphecy->reveal())->shouldBeCalled();

        $filterPass->process($containerBuilderProphecy->reveal());
    }

    public function testIdNotExist(): void
    {
        $filterPass = new GraphQlTypePass();

        $this->assertInstanceOf(CompilerPassInterface::class, $filterPass);

        $typeLocatorDefinitionProphecy = $this->prophesize(Definition::class);
        $typeLocatorDefinitionProphecy->addArgument(Argument::that(fn (array $arg) => !isset($arg['foo']) && isset($arg['bar']) && $arg['bar'] instanceof Reference))->willReturn($typeLocatorDefinitionProphecy->reveal())->shouldBeCalled();

        $typesFactoryDefinitionProphecy = $this->prophesize(Definition::class);
        $typesFactoryDefinitionProphecy->addArgument(['bar'])->willReturn($typesFactoryDefinitionProphecy->reveal())->shouldBeCalled();

        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);
        $containerBuilderProphecy->getParameter('api_platform.graphql.enabled')->willReturn(true)->shouldBeCalled();
        $containerBuilderProphecy->findTaggedServiceIds('api_platform.graphql.type', true)->willReturn(['foo' => [], 'bar' => [['hi' => 'hello']]])->shouldBeCalled();
        $containerBuilderProphecy->getDefinition('api_platform.graphql.type_locator')->willReturn($typeLocatorDefinitionProphecy->reveal())->shouldBeCalled();
        $containerBuilderProphecy->getDefinition('api_platform.graphql.types_factory')->willReturn($typesFactoryDefinitionProphecy->reveal())->shouldBeCalled();

        $filterPass->process($containerBuilderProphecy->reveal());
    }

    public function testDisabled(): void
    {
        $filterPass = new GraphQlTypePass();

        $this->assertInstanceOf(CompilerPassInterface::class, $filterPass);

        $typeLocatorDefinitionProphecy = $this->prophesize(Definition::class);
        $typeLocatorDefinitionProphecy->addArgument(Argument::any())->shouldNotBeCalled();

        $typesFactoryDefinitionProphecy = $this->prophesize(Definition::class);
        $typesFactoryDefinitionProphecy->addArgument(['my_id'])->shouldNotBeCalled();

        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);
        $containerBuilderProphecy->getParameter('api_platform.graphql.enabled')->willReturn(false)->shouldBeCalled();
        $containerBuilderProphecy->findTaggedServiceIds('api_platform.graphql.type', true)->willReturn(['foo' => [], 'bar' => [['id' => 'my_id']]])->shouldNotBeCalled();
        $containerBuilderProphecy->getDefinition('api_platform.graphql.type_locator')->willReturn($typeLocatorDefinitionProphecy->reveal())->shouldNotBeCalled();
        $containerBuilderProphecy->getDefinition('api_platform.graphql.types_factory')->willReturn($typesFactoryDefinitionProphecy->reveal())->shouldNotBeCalled();

        $filterPass->process($containerBuilderProphecy->reveal());
    }
}
