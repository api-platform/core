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

use ApiPlatform\Core\Bridge\Symfony\Bundle\DependencyInjection\Compiler\GraphQlTypePass;
use ApiPlatform\Core\Tests\ProphecyTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
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

    public function testProcess()
    {
        $filterPass = new GraphQlTypePass();

        $this->assertInstanceOf(CompilerPassInterface::class, $filterPass);

        $typeLocatorDefinitionProphecy = $this->prophesize(Definition::class);
        $typeLocatorDefinitionProphecy->addArgument(Argument::that(function (array $arg) {
            return !isset($arg['foo']) && isset($arg['my_id']) && $arg['my_id'] instanceof Reference;
        }))->shouldBeCalled();

        $typesFactoryDefinitionProphecy = $this->prophesize(Definition::class);
        $typesFactoryDefinitionProphecy->addArgument(['my_id'])->shouldBeCalled();

        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);
        $containerBuilderProphecy->getParameter('api_platform.graphql.enabled')->willReturn(true)->shouldBeCalled();
        $containerBuilderProphecy->findTaggedServiceIds('api_platform.graphql.type', true)->willReturn(['foo' => [], 'bar' => [['id' => 'my_id']]])->shouldBeCalled();
        $containerBuilderProphecy->getDefinition('api_platform.graphql.type_locator')->willReturn($typeLocatorDefinitionProphecy->reveal())->shouldBeCalled();
        $containerBuilderProphecy->getDefinition('api_platform.graphql.types_factory')->willReturn($typesFactoryDefinitionProphecy->reveal())->shouldBeCalled();

        $filterPass->process($containerBuilderProphecy->reveal());
    }

    public function testIdNotExist()
    {
        $filterPass = new GraphQlTypePass();

        $this->assertInstanceOf(CompilerPassInterface::class, $filterPass);

        $typeLocatorDefinitionProphecy = $this->prophesize(Definition::class);
        $typeLocatorDefinitionProphecy->addArgument(Argument::that(function (array $arg) {
            return !isset($arg['foo']) && isset($arg['bar']) && $arg['bar'] instanceof Reference;
        }))->shouldBeCalled();

        $typesFactoryDefinitionProphecy = $this->prophesize(Definition::class);
        $typesFactoryDefinitionProphecy->addArgument(['bar'])->shouldBeCalled();

        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);
        $containerBuilderProphecy->getParameter('api_platform.graphql.enabled')->willReturn(true)->shouldBeCalled();
        $containerBuilderProphecy->findTaggedServiceIds('api_platform.graphql.type', true)->willReturn(['foo' => [], 'bar' => [['hi' => 'hello']]])->shouldBeCalled();
        $containerBuilderProphecy->getDefinition('api_platform.graphql.type_locator')->willReturn($typeLocatorDefinitionProphecy->reveal())->shouldBeCalled();
        $containerBuilderProphecy->getDefinition('api_platform.graphql.types_factory')->willReturn($typesFactoryDefinitionProphecy->reveal())->shouldBeCalled();

        $filterPass->process($containerBuilderProphecy->reveal());
    }

    public function testDisabled()
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
