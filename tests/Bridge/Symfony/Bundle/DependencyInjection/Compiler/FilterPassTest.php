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

use ApiPlatform\Core\Bridge\Symfony\Bundle\DependencyInjection\Compiler\FilterPass;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class FilterPassTest extends \PHPUnit_Framework_TestCase
{
    public function testProcess()
    {
        $dataProviderPass = new FilterPass();

        $this->assertInstanceOf(CompilerPassInterface::class, $dataProviderPass);

        $definitionProphecy = $this->prophesize(Definition::class);
        $definitionProphecy->addArgument(Argument::type('array'))->shouldBeCalled();
        $definition = $definitionProphecy->reveal();

        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);
        $containerBuilderProphecy->findTaggedServiceIds('api_platform.filter')->willReturn(['foo' => [], 'bar' => [0 => ['id' => 'my_id']]])->shouldBeCalled();
        $containerBuilderProphecy->getDefinition('api_platform.filters')->willReturn($definition)->shouldBeCalled();
        $containerBuilder = $containerBuilderProphecy->reveal();

        $dataProviderPass->process($containerBuilder);
    }

    /**
     * @expectedException        \ApiPlatform\Core\Exception\RuntimeException
     * @expectedExceptionMessage Filter tags must have an "id" property.
     */
    public function testIdNotExist()
    {
        $dataProviderPass = new FilterPass();

        $this->assertInstanceOf(CompilerPassInterface::class, $dataProviderPass);

        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);
        $containerBuilderProphecy->findTaggedServiceIds('api_platform.filter')->willReturn(['foo' => [], 'bar' => [0 => ['hi' => 'hello']]])->shouldBeCalled();
        $containerBuilder = $containerBuilderProphecy->reveal();

        $dataProviderPass->process($containerBuilder);
    }
}
