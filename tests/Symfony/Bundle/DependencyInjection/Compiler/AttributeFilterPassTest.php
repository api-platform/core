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

use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Symfony\Bundle\DependencyInjection\Compiler\AttributeFilterPass;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException as DependencyInjectionInvalidArgumentException;

/**
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
class AttributeFilterPassTest extends TestCase
{
    use ProphecyTrait;

    public function testConstruct(): void
    {
        $attributeFilterPass = new AttributeFilterPass();

        $this->assertInstanceOf(CompilerPassInterface::class, $attributeFilterPass);
    }

    public function testProcess(): void
    {
        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);
        $containerBuilderProphecy->getParameter('api_platform.resource_class_directories')->willReturn([
            __DIR__.'/../../../../Fixtures/TestBundle/Entity/',
        ]);

        $containerBuilderProphecy->has(Argument::type('string'))->willReturn(false, true)->shouldBeCalled();
        $containerBuilderProphecy->getReflectionClass(BooleanFilter::class, false)->willReturn(new \ReflectionClass(BooleanFilter::class))->shouldBeCalled();
        $containerBuilderProphecy->has(BooleanFilter::class)->willReturn(true)->shouldBeCalled();
        $containerBuilderProphecy->findDefinition(BooleanFilter::class)->willReturn(new Definition(BooleanFilter::class))->shouldBeCalled();
        $containerBuilderProphecy->setDefinition(Argument::type('string'), Argument::allOf(
            Argument::type(Definition::class),
            Argument::that(fn (Definition $definition): bool => BooleanFilter::class === $definition->getClass())
        ))->willReturn(new Definition(BooleanFilter::class))->shouldBeCalled();

        $attributeFilterPass = new AttributeFilterPass();
        $attributeFilterPass->process($containerBuilderProphecy->reveal());
    }

    public function testProcessInvalidFilterClass(): void
    {
        $this->expectException(DependencyInjectionInvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf('Class "%s" used for service "annotated_api_platform_tests_fixtures_test_bundle_entity_converted_boolean_api_platform_doctrine_orm_filter_boolean_filter" cannot be found.', BooleanFilter::class));

        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);
        $containerBuilderProphecy->getParameter('api_platform.resource_class_directories')->willReturn([
            __DIR__.'/../../../../Fixtures/TestBundle/Entity/',
        ]);
        $containerBuilderProphecy->has(Argument::type('string'))->willReturn(false);
        $containerBuilderProphecy->getReflectionClass(BooleanFilter::class, false)->willReturn(null);

        $attributeFilterPass = new AttributeFilterPass();
        $attributeFilterPass->process($containerBuilderProphecy->reveal());
    }
}
