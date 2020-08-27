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

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Api\FilterInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Bridge\Symfony\Bundle\DependencyInjection\Compiler\AnnotationFilterPass;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Serializer\Filter\GroupFilter;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Filter\DoesNotImplementInterfaceFilter;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Filter\NoConstructorFilter;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Filter\NoPropertiesArgumentFilter;
use ApiPlatform\Core\Tests\ProphecyTrait;
use Doctrine\Common\Annotations\Reader;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException as DependencyInjectionInvalidArgumentException;

/**
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
class AnnotationFilterPassTest extends TestCase
{
    use ProphecyTrait;

    public function testConstruct(): void
    {
        $annotationFilterPass = new AnnotationFilterPass();

        $this->assertInstanceOf(CompilerPassInterface::class, $annotationFilterPass);
    }

    public function testProcess(): void
    {
        $readerProphecy = $this->prophesize(Reader::class);
        $readerProphecy->getClassAnnotations(Argument::type(\ReflectionClass::class))->willReturn([]);
        $readerProphecy->getClassAnnotations(Argument::allOf(
            Argument::type(\ReflectionClass::class),
            Argument::which('getName', Dummy::class)
        ))->willReturn([
            new ApiFilter(['value' => SearchFilter::class, 'strategy' => 'exact', 'properties' => ['description', 'relatedDummy.name', 'name']]),
            new ApiFilter(['value' => GroupFilter::class, 'arguments' => ['parameterName' => 'foobar']]),
        ]);
        $readerProphecy->getPropertyAnnotations(Argument::type(\ReflectionProperty::class))->willReturn([]);
        $readerProphecy->getPropertyAnnotations(Argument::allOf(
            Argument::type(\ReflectionProperty::class),
            Argument::that(function (\ReflectionProperty $reflectionProperty): bool {
                return Dummy::class === $reflectionProperty->getDeclaringClass()->getName();
            }),
            Argument::which('getName', 'dummyDate')
        ))->willReturn([
            new ApiFilter(['value' => DateFilter::class]),
        ]);

        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);
        $containerBuilderProphecy->getParameter('api_platform.resource_class_directories')->willReturn([
            __DIR__.'/../../../../../Fixtures/TestBundle/Entity/',
        ]);
        $containerBuilderProphecy->get('annotation_reader')->willReturn($readerProphecy);
        $containerBuilderProphecy->has('annotated_api_platform_core_tests_fixtures_test_bundle_entity_dummy_api_platform_core_bridge_doctrine_orm_filter_search_filter')->willReturn(false);
        $containerBuilderProphecy->has('annotated_api_platform_core_tests_fixtures_test_bundle_entity_dummy_api_platform_core_serializer_filter_group_filter')->willReturn(false);
        $containerBuilderProphecy->has('annotated_api_platform_core_tests_fixtures_test_bundle_entity_dummy_api_platform_core_bridge_doctrine_orm_filter_date_filter')->willReturn(false);
        $containerBuilderProphecy->has(SearchFilter::class)->willReturn(false);
        $containerBuilderProphecy->has(GroupFilter::class)->willReturn(false);
        $containerBuilderProphecy->has(DateFilter::class)->willReturn(true);
        $containerBuilderProphecy->findDefinition(DateFilter::class)->willReturn((new Definition(DateFilter::class))->setAbstract(true));
        $containerBuilderProphecy->getReflectionClass(SearchFilter::class, false)->willReturn(new \ReflectionClass(SearchFilter::class));
        $containerBuilderProphecy->getReflectionClass(GroupFilter::class, false)->willReturn(new \ReflectionClass(GroupFilter::class));
        $containerBuilderProphecy->getReflectionClass(DateFilter::class, false)->willReturn(new \ReflectionClass(DateFilter::class));
        $containerBuilderProphecy->setDefinition('annotated_api_platform_core_tests_fixtures_test_bundle_entity_dummy_api_platform_core_bridge_doctrine_orm_filter_search_filter', Argument::allOf(
            Argument::type(Definition::class),
            Argument::that(function (Definition $definition): bool {
                return SearchFilter::class === $definition->getClass() && ['$properties' => ['description' => null, 'relatedDummy.name' => null, 'name' => null]] === $definition->getArguments();
            })
        ))->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('annotated_api_platform_core_tests_fixtures_test_bundle_entity_dummy_api_platform_core_serializer_filter_group_filter', Argument::allOf(
            Argument::type(Definition::class),
            Argument::that(function (Definition $definition): bool {
                return GroupFilter::class === $definition->getClass() && ['$parameterName' => 'foobar'] === $definition->getArguments();
            })
        ))->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('annotated_api_platform_core_tests_fixtures_test_bundle_entity_dummy_api_platform_core_bridge_doctrine_orm_filter_date_filter', Argument::allOf(
            Argument::type(ChildDefinition::class),
            Argument::that(function (ChildDefinition $definition): bool {
                return DateFilter::class === $definition->getParent() && ['$properties' => ['dummyDate' => null]] === $definition->getArguments();
            })
        ))->shouldBeCalled();

        $annotationFilterPass = new AnnotationFilterPass();
        $annotationFilterPass->process($containerBuilderProphecy->reveal());
    }

    public function testProcessFilterWhichDoesNotImplementRequiredInterface(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('The filter class "%s" does not implement "%s".', DoesNotImplementInterfaceFilter::class, FilterInterface::class));

        $readerProphecy = $this->prophesize(Reader::class);
        $readerProphecy->getClassAnnotations(Argument::type(\ReflectionClass::class))->willReturn([]);
        $readerProphecy->getClassAnnotations(Argument::allOf(
            Argument::type(\ReflectionClass::class),
            Argument::which('getName', Dummy::class)
        ))->willReturn([
            new ApiFilter(['value' => DoesNotImplementInterfaceFilter::class]),
        ]);
        $readerProphecy->getPropertyAnnotations(Argument::type(\ReflectionProperty::class))->willReturn([]);

        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);
        $containerBuilderProphecy->getParameter('api_platform.resource_class_directories')->willReturn([
            __DIR__.'/../../../../../Fixtures/TestBundle/Entity/',
        ]);
        $containerBuilderProphecy->get('annotation_reader')->willReturn($readerProphecy);

        $annotationFilterPass = new AnnotationFilterPass();
        $annotationFilterPass->process($containerBuilderProphecy->reveal());
    }

    public function testProcessFilterWhichHasAlreadyBeenRegistered(): void
    {
        $readerProphecy = $this->prophesize(Reader::class);
        $readerProphecy->getClassAnnotations(Argument::type(\ReflectionClass::class))->willReturn([]);
        $readerProphecy->getClassAnnotations(Argument::allOf(
            Argument::type(\ReflectionClass::class),
            Argument::which('getName', Dummy::class)
        ))->willReturn([
            new ApiFilter(['value' => SearchFilter::class]),
        ]);
        $readerProphecy->getPropertyAnnotations(Argument::type(\ReflectionProperty::class))->willReturn([]);

        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);
        $containerBuilderProphecy->getParameter('api_platform.resource_class_directories')->willReturn([
            __DIR__.'/../../../../../Fixtures/TestBundle/Entity/',
        ]);
        $containerBuilderProphecy->get('annotation_reader')->willReturn($readerProphecy);
        $containerBuilderProphecy->has('annotated_api_platform_core_tests_fixtures_test_bundle_entity_dummy_api_platform_core_bridge_doctrine_orm_filter_search_filter')->willReturn(true);
        $containerBuilderProphecy->setDefinition(Argument::cetera())->shouldNotBeCalled();

        $annotationFilterPass = new AnnotationFilterPass();
        $annotationFilterPass->process($containerBuilderProphecy->reveal());
    }

    public function testProcessInvalidFilterClass(): void
    {
        $this->expectException(DependencyInjectionInvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Class "%s" used for service "annotated_api_platform_core_tests_fixtures_test_bundle_entity_dummy_api_platform_core_bridge_doctrine_orm_filter_search_filter" cannot be found.', SearchFilter::class));

        $readerProphecy = $this->prophesize(Reader::class);
        $readerProphecy->getClassAnnotations(Argument::type(\ReflectionClass::class))->willReturn([]);
        $readerProphecy->getClassAnnotations(Argument::allOf(
            Argument::type(\ReflectionClass::class),
            Argument::which('getName', Dummy::class)
        ))->willReturn([
            new ApiFilter(['value' => SearchFilter::class]),
        ]);
        $readerProphecy->getPropertyAnnotations(Argument::type(\ReflectionProperty::class))->willReturn([]);

        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);
        $containerBuilderProphecy->getParameter('api_platform.resource_class_directories')->willReturn([
            __DIR__.'/../../../../../Fixtures/TestBundle/Entity/',
        ]);
        $containerBuilderProphecy->get('annotation_reader')->willReturn($readerProphecy);
        $containerBuilderProphecy->has('annotated_api_platform_core_tests_fixtures_test_bundle_entity_dummy_api_platform_core_bridge_doctrine_orm_filter_search_filter')->willReturn(false);
        $containerBuilderProphecy->getReflectionClass(SearchFilter::class, false)->willReturn(null);

        $annotationFilterPass = new AnnotationFilterPass();
        $annotationFilterPass->process($containerBuilderProphecy->reveal());
    }

    public function testProcessFilterWithoutConstructor(): void
    {
        $readerProphecy = $this->prophesize(Reader::class);
        $readerProphecy->getClassAnnotations(Argument::type(\ReflectionClass::class))->willReturn([]);
        $readerProphecy->getClassAnnotations(Argument::allOf(
            Argument::type(\ReflectionClass::class),
            Argument::which('getName', Dummy::class)
        ))->willReturn([
            new ApiFilter(['value' => NoConstructorFilter::class]),
        ]);
        $readerProphecy->getPropertyAnnotations(Argument::type(\ReflectionProperty::class))->willReturn([]);

        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);
        $containerBuilderProphecy->getParameter('api_platform.resource_class_directories')->willReturn([
            __DIR__.'/../../../../../Fixtures/TestBundle/Entity/',
        ]);
        $containerBuilderProphecy->get('annotation_reader')->willReturn($readerProphecy);
        $containerBuilderProphecy->has('annotated_api_platform_core_tests_fixtures_test_bundle_entity_dummy_api_platform_core_tests_fixtures_test_bundle_filter_no_constructor_filter')->willReturn(false);
        $containerBuilderProphecy->getReflectionClass(NoConstructorFilter::class, false)->willReturn(new \ReflectionClass(NoConstructorFilter::class));
        $containerBuilderProphecy->has(NoConstructorFilter::class)->willReturn(true);
        $containerBuilderProphecy->findDefinition(NoConstructorFilter::class)->willReturn((new Definition(NoConstructorFilter::class))->setAbstract(false));
        $containerBuilderProphecy->setDefinition('annotated_api_platform_core_tests_fixtures_test_bundle_entity_dummy_api_platform_core_tests_fixtures_test_bundle_filter_no_constructor_filter', Argument::allOf(
            Argument::type(Definition::class),
            Argument::that(function (Definition $definition): bool {
                return NoConstructorFilter::class === $definition->getClass() && [] === $definition->getArguments();
            })
        ))->shouldBeCalled();

        $annotationFilterPass = new AnnotationFilterPass();
        $annotationFilterPass->process($containerBuilderProphecy->reveal());
    }

    public function testProcessFilterWithoutPropertiesArgument(): void
    {
        $readerProphecy = $this->prophesize(Reader::class);
        $readerProphecy->getClassAnnotations(Argument::type(\ReflectionClass::class))->willReturn([]);
        $readerProphecy->getClassAnnotations(Argument::allOf(
            Argument::type(\ReflectionClass::class),
            Argument::which('getName', Dummy::class)
        ))->willReturn([
            new ApiFilter(['value' => NoPropertiesArgumentFilter::class]),
        ]);
        $readerProphecy->getPropertyAnnotations(Argument::type(\ReflectionProperty::class))->willReturn([]);

        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);
        $containerBuilderProphecy->getParameter('api_platform.resource_class_directories')->willReturn([
            __DIR__.'/../../../../../Fixtures/TestBundle/Entity/',
        ]);
        $containerBuilderProphecy->get('annotation_reader')->willReturn($readerProphecy);
        $containerBuilderProphecy->has('annotated_api_platform_core_tests_fixtures_test_bundle_entity_dummy_api_platform_core_tests_fixtures_test_bundle_filter_no_properties_argument_filter')->willReturn(false);
        $containerBuilderProphecy->getReflectionClass(NoPropertiesArgumentFilter::class, false)->willReturn(new \ReflectionClass(NoPropertiesArgumentFilter::class));
        $containerBuilderProphecy->has(NoPropertiesArgumentFilter::class)->willReturn(true);
        $containerBuilderProphecy->findDefinition(NoPropertiesArgumentFilter::class)->willReturn((new Definition(NoPropertiesArgumentFilter::class))->setAbstract(false));
        $containerBuilderProphecy->setDefinition('annotated_api_platform_core_tests_fixtures_test_bundle_entity_dummy_api_platform_core_tests_fixtures_test_bundle_filter_no_properties_argument_filter', Argument::allOf(
            Argument::type(Definition::class),
            Argument::that(function (Definition $definition): bool {
                return NoPropertiesArgumentFilter::class === $definition->getClass() && [] === $definition->getArguments();
            })
        ))->shouldBeCalled();

        $annotationFilterPass = new AnnotationFilterPass();
        $annotationFilterPass->process($containerBuilderProphecy->reveal());
    }
}
