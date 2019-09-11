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

use ApiPlatform\Core\Bridge\Symfony\Bundle\DependencyInjection\Compiler\GraphQlExceptionFormatterPass;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @author Mahmood Bazdar <mahmood@bazdar.me>
 */
class GraphQlExceptionFormatterPassTest extends TestCase
{
    public function testProcess()
    {
        $filterPass = new GraphQlExceptionFormatterPass();

        $this->assertInstanceOf(CompilerPassInterface::class, $filterPass);

        $exceptionFormatterLocatorDefinitionProphecy = $this->prophesize(Definition::class);
        $exceptionFormatterLocatorDefinitionProphecy->addArgument(Argument::that(function (array $arg) {
            return !isset($arg['foo']) && isset($arg['my_id']) && $arg['my_id'] instanceof Reference;
        }))->shouldBeCalled();

        $exceptionFormatterFactoryDefinitionProphecy = $this->prophesize(Definition::class);
        $exceptionFormatterFactoryDefinitionProphecy->addArgument(['my_id'])->shouldBeCalled();

        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);
        $containerBuilderProphecy->getParameter('api_platform.graphql.enabled')->willReturn(true)->shouldBeCalled();
        $containerBuilderProphecy->findTaggedServiceIds('api_platform.graphql.exception_formatter', true)->willReturn(['foo' => [], 'bar' => [['id' => 'my_id']]])->shouldBeCalled();
        $containerBuilderProphecy->getDefinition('api_platform.graphql.exception_formatter_locator')->willReturn($exceptionFormatterLocatorDefinitionProphecy->reveal())->shouldBeCalled();
        $containerBuilderProphecy->getDefinition('api_platform.graphql.exception_formatter_factory')->willReturn($exceptionFormatterFactoryDefinitionProphecy->reveal())->shouldBeCalled();

        $filterPass->process($containerBuilderProphecy->reveal());
    }

    public function testIdNotExist()
    {
        $filterPass = new GraphQlExceptionFormatterPass();

        $this->assertInstanceOf(CompilerPassInterface::class, $filterPass);

        $exceptionFormatterLocatorDefinitionProphecy = $this->prophesize(Definition::class);
        $exceptionFormatterLocatorDefinitionProphecy->addArgument(Argument::that(function (array $arg) {
            return !isset($arg['foo']) && isset($arg['bar']) && $arg['bar'] instanceof Reference;
        }))->shouldBeCalled();

        $exceptionFormatterFactoryDefinitionProphecy = $this->prophesize(Definition::class);
        $exceptionFormatterFactoryDefinitionProphecy->addArgument(['bar'])->shouldBeCalled();

        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);
        $containerBuilderProphecy->getParameter('api_platform.graphql.enabled')->willReturn(true)->shouldBeCalled();
        $containerBuilderProphecy->findTaggedServiceIds('api_platform.graphql.exception_formatter', true)->willReturn(['foo' => [], 'bar' => [['hi' => 'hello']]])->shouldBeCalled();
        $containerBuilderProphecy->getDefinition('api_platform.graphql.exception_formatter_locator')->willReturn($exceptionFormatterLocatorDefinitionProphecy->reveal())->shouldBeCalled();
        $containerBuilderProphecy->getDefinition('api_platform.graphql.exception_formatter_factory')->willReturn($exceptionFormatterFactoryDefinitionProphecy->reveal())->shouldBeCalled();

        $filterPass->process($containerBuilderProphecy->reveal());
    }

    public function testDisabled()
    {
        $filterPass = new GraphQlExceptionFormatterPass();

        $this->assertInstanceOf(CompilerPassInterface::class, $filterPass);

        $exceptionFormatterLocatorDefinitionProphecy = $this->prophesize(Definition::class);
        $exceptionFormatterLocatorDefinitionProphecy->addArgument(Argument::any())->shouldNotBeCalled();

        $exceptionFormatterFactoryDefinitionProphecy = $this->prophesize(Definition::class);
        $exceptionFormatterFactoryDefinitionProphecy->addArgument(['my_id'])->shouldNotBeCalled();

        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);
        $containerBuilderProphecy->getParameter('api_platform.graphql.enabled')->willReturn(false)->shouldBeCalled();
        $containerBuilderProphecy->findTaggedServiceIds('api_platform.graphql.exception_formatter', true)->willReturn(['foo' => [], 'bar' => [['id' => 'my_id']]])->shouldNotBeCalled();
        $containerBuilderProphecy->getDefinition('api_platform.graphql.exception_formatter_locator')->willReturn($exceptionFormatterLocatorDefinitionProphecy->reveal())->shouldNotBeCalled();
        $containerBuilderProphecy->getDefinition('api_platform.graphql.exception_formatter_factory')->willReturn($exceptionFormatterFactoryDefinitionProphecy->reveal())->shouldNotBeCalled();

        $filterPass->process($containerBuilderProphecy->reveal());
    }
}
