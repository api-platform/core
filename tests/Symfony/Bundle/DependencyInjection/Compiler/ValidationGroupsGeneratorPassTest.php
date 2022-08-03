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

use ApiPlatform\Symfony\Bundle\DependencyInjection\Compiler\ValidationGroupsGeneratorPass;
use ApiPlatform\Tests\ProphecyTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @author Danny van Wijk <dannyvanwijk@gmail.com>
 */
class ValidationGroupsGeneratorPassTest extends TestCase
{
    use ProphecyTrait;

    public function testProcess(): void
    {
        $validationGroupsGeneratorPass = new ValidationGroupsGeneratorPass();

        $this->assertInstanceOf(CompilerPassInterface::class, $validationGroupsGeneratorPass);

        $filterLocatorDefinitionProphecy = $this->prophesize(Definition::class);
        $filterLocatorDefinitionProphecy->addArgument(Argument::that(fn (array $arg) => !isset($arg['foo']) && isset($arg['my_id']) && $arg['my_id'] instanceof Reference))->willReturn($filterLocatorDefinitionProphecy->reveal())->shouldBeCalled();

        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);
        $containerBuilderProphecy->findTaggedServiceIds('api_platform.validation_groups_generator', true)->willReturn(['foo' => [], 'bar' => [['id' => 'my_id']]])->shouldBeCalled();
        $containerBuilderProphecy->getDefinition('api_platform.validator_locator')->willReturn($filterLocatorDefinitionProphecy->reveal())->shouldBeCalled();

        $validationGroupsGeneratorPass->process($containerBuilderProphecy->reveal());
    }

    public function testIdNotExist(): void
    {
        $validationGroupsGeneratorPass = new ValidationGroupsGeneratorPass();

        $this->assertInstanceOf(CompilerPassInterface::class, $validationGroupsGeneratorPass);

        $filterLocatorDefinitionProphecy = $this->prophesize(Definition::class);
        $filterLocatorDefinitionProphecy->addArgument(Argument::that(fn (array $arg) => !isset($arg['foo']) && isset($arg['bar']) && $arg['bar'] instanceof Reference))->willReturn($filterLocatorDefinitionProphecy->reveal())->shouldBeCalled();

        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);
        $containerBuilderProphecy->findTaggedServiceIds('api_platform.validation_groups_generator', true)->willReturn(['foo' => [], 'bar' => [['hi' => 'hello']]])->shouldBeCalled();
        $containerBuilderProphecy->getDefinition('api_platform.validator_locator')->willReturn($filterLocatorDefinitionProphecy->reveal())->shouldBeCalled();

        $validationGroupsGeneratorPass->process($containerBuilderProphecy->reveal());
    }
}
