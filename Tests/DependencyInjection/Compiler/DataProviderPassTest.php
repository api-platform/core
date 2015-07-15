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

use Dunglas\ApiBundle\DependencyInjection\Compiler\DataProviderPass;
use Prophecy\Argument;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class DataProviderPassTest extends \PHPUnit_Framework_TestCase
{
    public function testProcess()
    {
        $dataProviderPass = new DataProviderPass();

        $this->assertInstanceOf('Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface', $dataProviderPass);

        $definitionProphecy = $this->prophesize('Symfony\Component\DependencyInjection\Definition');
        $definitionProphecy->addArgument(Argument::type('array'))->shouldBeCalled();
        $definition = $definitionProphecy->reveal();

        $containerBuilderProphecy = $this->prophesize('Symfony\Component\DependencyInjection\ContainerBuilder');
        $containerBuilderProphecy->findTaggedServiceIds('api.data_provider')->willReturn(['foo' => [], 'bar' => ['priority' => 1]])->shouldBeCalled();
        $containerBuilderProphecy->getDefinition('api.data_provider')->willReturn($definition)->shouldBeCalled();
        $containerBuilder = $containerBuilderProphecy->reveal();

        $dataProviderPass->process($containerBuilder);
    }
}
