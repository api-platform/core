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

use Dunglas\ApiBundle\DependencyInjection\Compiler\FilterPass;
use Dunglas\ApiBundle\Exception\RuntimeException;
use Prophecy\Argument;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class FilterPassTest extends \PHPUnit_Framework_TestCase
{
    public function testProcess()
    {
        $dataProviderPass = new FilterPass();

        $this->assertInstanceOf('Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface', $dataProviderPass);

        $definitionProphecy = $this->prophesize('Symfony\Component\DependencyInjection\Definition');
        $definitionProphecy->addArgument(Argument::type('array'))->shouldBeCalled();
        $definition = $definitionProphecy->reveal();

        $containerBuilderProphecy = $this->prophesize('Symfony\Component\DependencyInjection\ContainerBuilder');
        $containerBuilderProphecy->findTaggedServiceIds('api.filter')->willReturn(['foo' => [], 'bar' => [0 => ['id' => 'my_id']]])->shouldBeCalled();
        $containerBuilderProphecy->getDefinition('api.filters')->willReturn($definition)->shouldBeCalled();
        $containerBuilder = $containerBuilderProphecy->reveal();

        $dataProviderPass->process($containerBuilder);
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage Filter tags must have an "id" property.
     */
    public function testIdNotExist()
    {
        $dataProviderPass = new FilterPass();

        $this->assertInstanceOf('Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface', $dataProviderPass);

        $containerBuilderProphecy = $this->prophesize('Symfony\Component\DependencyInjection\ContainerBuilder');
        $containerBuilderProphecy->findTaggedServiceIds('api.filter')->willReturn(['foo' => [], 'bar' => [0 => ['hi' => 'hello']]])->shouldBeCalled();
        $containerBuilder = $containerBuilderProphecy->reveal();

        $dataProviderPass->process($containerBuilder);
    }
}
