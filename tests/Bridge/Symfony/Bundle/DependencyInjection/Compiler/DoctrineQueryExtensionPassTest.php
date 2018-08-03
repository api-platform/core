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

use ApiPlatform\Core\Bridge\Symfony\Bundle\DependencyInjection\Compiler\DoctrineQueryExtensionPass;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class DoctrineQueryExtensionPassTest extends TestCase
{
    public function testProcess()
    {
        $dataProviderPass = new DoctrineQueryExtensionPass();

        $this->assertInstanceOf(CompilerPassInterface::class, $dataProviderPass);

        $collectionDataProviderDefinitionProphecy = $this->prophesize(Definition::class);
        $collectionDataProviderDefinitionProphecy->replaceArgument(1, Argument::type('array'))->shouldBeCalled();
        $collectionDataProviderDefinition = $collectionDataProviderDefinitionProphecy->reveal();

        $itemDataProviderDefinitionProphecy = $this->prophesize(Definition::class);
        $itemDataProviderDefinitionProphecy->replaceArgument(3, Argument::type('array'))->shouldBeCalled();
        $itemDataProviderDefinition = $itemDataProviderDefinitionProphecy->reveal();

        $subresourceDataProviderDefinitionProphecy = $this->prophesize(Definition::class);
        $subresourceDataProviderDefinitionProphecy->replaceArgument(3, Argument::type('array'))->shouldBeCalled();
        $subresourceDataProviderDefinitionProphecy->replaceArgument(4, Argument::type('array'))->shouldBeCalled();
        $subresourceDataProviderDefinition = $subresourceDataProviderDefinitionProphecy->reveal();

        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);
        $containerBuilderProphecy->hasDefinition('api_platform.doctrine.metadata_factory')->willReturn(true)->shouldBeCalled();
        $containerBuilderProphecy->findTaggedServiceIds('api_platform.doctrine.orm.query_extension.collection', true)->willReturn(['foo' => [], 'bar' => ['priority' => 1]])->shouldBeCalled();
        $containerBuilderProphecy->findTaggedServiceIds('api_platform.doctrine.orm.query_extension.item', true)->willReturn(['foo' => [], 'bar' => ['priority' => 1]])->shouldBeCalled();

        $containerBuilderProphecy->getDefinition('api_platform.doctrine.orm.collection_data_provider')->willReturn($collectionDataProviderDefinition)->shouldBeCalled();
        $containerBuilderProphecy->getDefinition('api_platform.doctrine.orm.item_data_provider')->willReturn($itemDataProviderDefinition)->shouldBeCalled();
        $containerBuilderProphecy->getDefinition('api_platform.doctrine.orm.subresource_data_provider')->willReturn($subresourceDataProviderDefinition)->shouldBeCalled();
        $containerBuilder = $containerBuilderProphecy->reveal();

        $dataProviderPass->process($containerBuilder);
    }
}
