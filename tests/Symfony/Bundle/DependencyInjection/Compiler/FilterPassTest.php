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

use ApiPlatform\Symfony\Bundle\DependencyInjection\Compiler\FilterPass;
use ApiPlatform\Tests\ProphecyTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class FilterPassTest extends TestCase
{
    use ProphecyTrait;

    public function testProcess()
    {
        $filterPass = new FilterPass();

        $this->assertInstanceOf(CompilerPassInterface::class, $filterPass);

        $filterLocatorDefinitionProphecy = $this->prophesize(Definition::class);
        $filterLocatorDefinitionProphecy->addArgument(Argument::that(function (array $arg) {
            return !isset($arg['foo']) && isset($arg['my_id']) && $arg['my_id'] instanceof Reference;
        }))->willReturn($filterLocatorDefinitionProphecy->reveal())->shouldBeCalled();

        $filterCollectionFactoryDefinitionProphecy = $this->prophesize(Definition::class);
        $filterCollectionFactoryDefinitionProphecy->addArgument(['my_id'])->willReturn($filterCollectionFactoryDefinitionProphecy->reveal())->shouldBeCalled();

        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);
        $containerBuilderProphecy->findTaggedServiceIds('api_platform.filter', true)->willReturn(['foo' => [], 'bar' => [['id' => 'my_id']]])->shouldBeCalled();
        $containerBuilderProphecy->getDefinition('api_platform.filter_locator')->willReturn($filterLocatorDefinitionProphecy->reveal())->shouldBeCalled();
        $containerBuilderProphecy->getDefinition('api_platform.filter_collection_factory')->willReturn($filterCollectionFactoryDefinitionProphecy->reveal())->shouldBeCalled();

        $filterPass->process($containerBuilderProphecy->reveal());
    }

    public function testIdNotExist()
    {
        $filterPass = new FilterPass();

        $this->assertInstanceOf(CompilerPassInterface::class, $filterPass);

        $filterLocatorDefinitionProphecy = $this->prophesize(Definition::class);
        $filterLocatorDefinitionProphecy->addArgument(Argument::that(function (array $arg) {
            return !isset($arg['foo']) && isset($arg['bar']) && $arg['bar'] instanceof Reference;
        }))->willReturn($filterLocatorDefinitionProphecy->reveal())->shouldBeCalled();

        $filterCollectionFactoryDefinitionProphecy = $this->prophesize(Definition::class);
        $filterCollectionFactoryDefinitionProphecy->addArgument(['bar'])->willReturn($filterCollectionFactoryDefinitionProphecy->reveal())->shouldBeCalled();

        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);
        $containerBuilderProphecy->findTaggedServiceIds('api_platform.filter', true)->willReturn(['foo' => [], 'bar' => [['hi' => 'hello']]])->shouldBeCalled();
        $containerBuilderProphecy->getDefinition('api_platform.filter_locator')->willReturn($filterLocatorDefinitionProphecy->reveal())->shouldBeCalled();
        $containerBuilderProphecy->getDefinition('api_platform.filter_collection_factory')->willReturn($filterCollectionFactoryDefinitionProphecy->reveal())->shouldBeCalled();

        $filterPass->process($containerBuilderProphecy->reveal());
    }
}
