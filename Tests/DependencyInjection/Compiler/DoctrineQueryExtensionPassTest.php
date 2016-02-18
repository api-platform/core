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

use Dunglas\ApiBundle\DependencyInjection\Compiler\DoctrineQueryExtensionPass;
use Prophecy\Argument;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class DoctrineQueryExtensionPassTest extends \PHPUnit_Framework_TestCase
{
    public function testProcess()
    {
        $dataProviderPass = new DoctrineQueryExtensionPass();

        $this->assertInstanceOf('Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface', $dataProviderPass);

        $collectionDataProviderDefinitionProphecy = $this->prophesize('Symfony\Component\DependencyInjection\Definition');
        $collectionDataProviderDefinitionProphecy->replaceArgument(1, Argument::type('array'))->shouldBeCalled();
        $collectionDataProviderDefinition = $collectionDataProviderDefinitionProphecy->reveal();

        $itemDataProviderDefinitionProphecy = $this->prophesize('Symfony\Component\DependencyInjection\Definition');
        $itemDataProviderDefinitionProphecy->replaceArgument(3, Argument::type('array'))->shouldBeCalled();
        $itemDataProviderDefinition = $itemDataProviderDefinitionProphecy->reveal();

        $containerBuilderProphecy = $this->prophesize('Symfony\Component\DependencyInjection\ContainerBuilder');
        $containerBuilderProphecy->findTaggedServiceIds('api.doctrine.orm.query_extension.collection')->willReturn(['foo' => [], 'bar' => ['priority' => 1]])->shouldBeCalled();
        $containerBuilderProphecy->findTaggedServiceIds('api.doctrine.orm.query_extension.item')->willReturn(['foo' => [], 'bar' => ['priority' => 1]])->shouldBeCalled();
        $containerBuilderProphecy->getDefinition('api.doctrine.orm.collection_data_provider')->willReturn($collectionDataProviderDefinition)->shouldBeCalled();
        $containerBuilderProphecy->getDefinition('api.doctrine.orm.item_data_provider')->willReturn($itemDataProviderDefinition)->shouldBeCalled();
        $containerBuilder = $containerBuilderProphecy->reveal();

        $dataProviderPass->process($containerBuilder);
    }
}
