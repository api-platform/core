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
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Bridge\Symfony\Bundle\DependencyInjection\Compiler\AnnotationFilterPass;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Serializer\Filter\GroupFilter;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Doctrine\Orm\Filter\AnotherDummyFilter;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
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
    public function testProcess()
    {
        $annotationFilterPass = new AnnotationFilterPass();

        $this->assertInstanceOf(CompilerPassInterface::class, $annotationFilterPass);

        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);

        $containerBuilderProphecy->getParameter('api_platform.resource_class_directories')->shouldBeCalled()->willReturn([
            __DIR__.'/../../../../../Fixtures/TestBundle/Entity/',
        ]);

        $reader = $this->prophesize(Reader::class);

        $reader->getPropertyAnnotations(Argument::type(\ReflectionProperty::class))->will(function ($args) {
            if (Dummy::class === $args[0]->class && 'dummyDate' === $args[0]->name) {
                return [new ApiFilter(['value' => DateFilter::class]), new ApiProperty()];
            }

            return [];
        });

        $reader->getClassAnnotations(Argument::type(\ReflectionClass::class))->will(function ($args) {
            if (Dummy::class === $args[0]->name) {
                return [new ApiFilter(['value' => SearchFilter::class, 'strategy' => 'exact', 'properties' => ['description', 'relatedDummy.name', 'name']]), new ApiResource([]), new ApiFilter(['value' => GroupFilter::class, 'arguments' => ['parameterName' => 'foobar']])];
            }

            return [];
        });

        $containerBuilderProphecy->get('annotation_reader')->shouldBeCalled()->willReturn($reader->reveal());

        $containerBuilderProphecy->has('annotated_api_platform_core_tests_fixtures_test_bundle_entity_dummy_api_platform_core_bridge_doctrine_orm_filter_search_filter')->shouldBeCalled()->willReturn(false);
        $containerBuilderProphecy->has('annotated_api_platform_core_tests_fixtures_test_bundle_entity_dummy_api_platform_core_serializer_filter_group_filter')->shouldBeCalled()->willReturn(false);
        $containerBuilderProphecy->has('annotated_api_platform_core_tests_fixtures_test_bundle_entity_dummy_api_platform_core_bridge_doctrine_orm_filter_date_filter')->shouldBeCalled()->willReturn(false);

        $containerBuilderProphecy->has(SearchFilter::class)->willReturn(false)->shouldBeCalled();
        $containerBuilderProphecy->has(GroupFilter::class)->willReturn(false)->shouldBeCalled();
        $containerBuilderProphecy->has(DateFilter::class)->willReturn(true)->shouldBeCalled();

        $containerBuilderProphecy->findDefinition(DateFilter::class)->willReturn((new Definition(DateFilter::class))->setAbstract(true))->shouldBeCalled();

        $containerBuilderProphecy->getReflectionClass(SearchFilter::class, false)->willReturn(new \ReflectionClass(SearchFilter::class))->shouldBeCalled();
        $containerBuilderProphecy->getReflectionClass(GroupFilter::class, false)->willReturn(new \ReflectionClass(GroupFilter::class))->shouldBeCalled();

        $containerBuilderProphecy->setDefinition('annotated_api_platform_core_tests_fixtures_test_bundle_entity_dummy_api_platform_core_bridge_doctrine_orm_filter_search_filter', Argument::that(function ($def) {
            $this->assertInstanceOf(Definition::class, $def);
            $this->assertEquals(SearchFilter::class, $def->getClass());
            $this->assertEquals(['$properties' => ['description' => null, 'relatedDummy.name' => null, 'name' => null]], $def->getArguments());

            return true;
        }))->shouldBeCalled();

        $containerBuilderProphecy->setDefinition('annotated_api_platform_core_tests_fixtures_test_bundle_entity_dummy_api_platform_core_serializer_filter_group_filter', Argument::that(function ($def) {
            $this->assertInstanceOf(Definition::class, $def);
            $this->assertEquals(GroupFilter::class, $def->getClass());
            $this->assertEquals(['$parameterName' => 'foobar'], $def->getArguments());

            return true;
        }))->shouldBeCalled();

        $containerBuilderProphecy->setDefinition('annotated_api_platform_core_tests_fixtures_test_bundle_entity_dummy_api_platform_core_bridge_doctrine_orm_filter_date_filter', Argument::that(function ($def) {
            $this->assertInstanceOf(ChildDefinition::class, $def);
            $this->assertEquals(DateFilter::class, $def->getParent());
            $this->assertEquals(['$properties' => ['dummyDate' => null]], $def->getArguments());

            return true;
        }))->shouldBeCalled();

        $annotationFilterPass->process($containerBuilderProphecy->reveal());
    }

    public function testProcessWrongFilter()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The filter class "ApiPlatform\\Core\\Tests\\Fixtures\\TestBundle\\Doctrine\\Orm\\Filter\\AnotherDummyFilter" does not implement "ApiPlatform\\Core\\Api\\FilterInterface".');

        $annotationFilterPass = new AnnotationFilterPass();

        $this->assertInstanceOf(CompilerPassInterface::class, $annotationFilterPass);

        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);

        $containerBuilderProphecy->getParameter('api_platform.resource_class_directories')->shouldBeCalled()->willReturn([
            __DIR__.'/../../../../../Fixtures/TestBundle/Entity/',
        ]);

        $reader = $this->prophesize(Reader::class);

        $reader->getPropertyAnnotations(Argument::type(\ReflectionProperty::class))->will(function ($args) {
            return [];
        });

        $reader->getClassAnnotations(Argument::type(\ReflectionClass::class))->will(function ($args) {
            if (Dummy::class === $args[0]->name) {
                return [new ApiFilter(['value' => AnotherDummyFilter::class])];
            }

            return [];
        });

        $containerBuilderProphecy->get('annotation_reader')->shouldBeCalled()->willReturn($reader->reveal());

        $annotationFilterPass->process($containerBuilderProphecy->reveal());
    }

    public function testProcessExistingFilter()
    {
        $annotationFilterPass = new AnnotationFilterPass();

        $this->assertInstanceOf(CompilerPassInterface::class, $annotationFilterPass);

        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);

        $containerBuilderProphecy->getParameter('api_platform.resource_class_directories')->shouldBeCalled()->willReturn([
            __DIR__.'/../../../../../Fixtures/TestBundle/Entity/',
        ]);

        $reader = $this->prophesize(Reader::class);

        $reader->getPropertyAnnotations(Argument::type(\ReflectionProperty::class))->will(function ($args) {
            return [];
        });

        $reader->getClassAnnotations(Argument::type(\ReflectionClass::class))->will(function ($args) {
            if (Dummy::class === $args[0]->name) {
                return [new ApiFilter(['value' => SearchFilter::class])];
            }

            return [];
        });

        $containerBuilderProphecy->get('annotation_reader')->shouldBeCalled()->willReturn($reader->reveal());

        $containerBuilderProphecy->has('annotated_api_platform_core_tests_fixtures_test_bundle_entity_dummy_api_platform_core_bridge_doctrine_orm_filter_search_filter')->shouldBeCalled()->willReturn(true);

        $annotationFilterPass->process($containerBuilderProphecy->reveal());
    }

    public function testProcessInvalidFilterClass()
    {
        $annotationFilterPass = new AnnotationFilterPass();

        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);
        $containerBuilderProphecy->getParameter('api_platform.resource_class_directories')->shouldBeCalled()->willReturn([
            __DIR__.'/../../../../../Fixtures/TestBundle/Entity/',
        ]);

        $reader = $this->prophesize(Reader::class);
        $reader->getPropertyAnnotations(Argument::type(\ReflectionProperty::class))->will(function ($args) {
            return [];
        });
        $reader->getClassAnnotations(Argument::type(\ReflectionClass::class))->will(function ($args) {
            if (Dummy::class === $args[0]->name) {
                return [new ApiFilter(['value' => SearchFilter::class])];
            }

            return [];
        });

        $containerBuilderProphecy->get('annotation_reader')->shouldBeCalled()->willReturn($reader->reveal());
        $containerBuilderProphecy->has('annotated_api_platform_core_tests_fixtures_test_bundle_entity_dummy_api_platform_core_bridge_doctrine_orm_filter_search_filter')->shouldBeCalled()->willReturn(false);
        $containerBuilderProphecy->has(SearchFilter::class)->willReturn(false)->shouldBeCalled();
        $containerBuilderProphecy->getReflectionClass(SearchFilter::class, false)->willReturn(null)->shouldBeCalled();

        $this->expectException(DependencyInjectionInvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Class "%s" used for service "annotated_api_platform_core_tests_fixtures_test_bundle_entity_dummy_api_platform_core_bridge_doctrine_orm_filter_search_filter" cannot be found.', SearchFilter::class));

        $annotationFilterPass->process($containerBuilderProphecy->reveal());
    }
}
