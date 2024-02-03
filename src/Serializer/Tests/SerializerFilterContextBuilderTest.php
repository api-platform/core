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

namespace ApiPlatform\Serializer\Tests;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\FilterInterface;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Serializer\Filter\FilterInterface as SerializerFilterInterface;
use ApiPlatform\Serializer\SerializerContextBuilderInterface;
use ApiPlatform\Serializer\SerializerFilterContextBuilder;
use ApiPlatform\Serializer\Tests\Fixtures\ApiResource\DummyGroup;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
class SerializerFilterContextBuilderTest extends TestCase
{
    use ProphecyTrait;

    public function testCreateFromRequestWithCollectionOperation(): void
    {
        $request = new Request();

        $attributes = [
            'resource_class' => DummyGroup::class,
            'operation_name' => 'get',
        ];

        $resourceMetadata = $this->getMetadataWithFilter(DummyGroup::class, ['dummy_group.group', 'dummy_group.search', 'dummy_group.nonexistent']);

        $decoratedProphecy = $this->prophesize(SerializerContextBuilderInterface::class);
        $decoratedProphecy->createFromRequest($request, true, $attributes)->willReturn([])->shouldBeCalled();

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(DummyGroup::class)->willReturn($resourceMetadata)->shouldBeCalled();

        $dummyGroupGroupFilterProphecy = $this->prophesize(SerializerFilterInterface::class);
        $dummyGroupGroupFilterProphecy->apply($request, true, $attributes, [])->shouldBeCalled();

        $dummyGroupSearchFilterProphecy = $this->prophesize(FilterInterface::class);

        $filterLocatorProphecy = $this->prophesize(ContainerInterface::class);
        $filterLocatorProphecy->has('dummy_group.group')->willReturn(true)->shouldBeCalled();
        $filterLocatorProphecy->get('dummy_group.group')->willReturn($dummyGroupGroupFilterProphecy->reveal())->shouldBeCalled();
        $filterLocatorProphecy->has('dummy_group.search')->willReturn(true)->shouldBeCalled();
        $filterLocatorProphecy->get('dummy_group.search')->willReturn($dummyGroupSearchFilterProphecy->reveal())->shouldBeCalled();
        $filterLocatorProphecy->has('dummy_group.nonexistent')->willReturn(false)->shouldBeCalled();

        $serializerContextBuilderFilter = new SerializerFilterContextBuilder($resourceMetadataFactoryProphecy->reveal(), $filterLocatorProphecy->reveal(), $decoratedProphecy->reveal());
        $serializerContextBuilderFilter->createFromRequest($request, true, $attributes);
    }

    public function testCreateFromRequestWithItemOperation(): void
    {
        $request = new Request();

        $attributes = [
            'resource_class' => DummyGroup::class,
            'operation_name' => 'get',
        ];

        $resourceMetadata = $this->getMetadataWithFilter(DummyGroup::class, ['dummy_group.group', 'dummy_group.search', 'dummy_group.nonexistent']);
        $decoratedProphecy = $this->prophesize(SerializerContextBuilderInterface::class);
        $decoratedProphecy->createFromRequest($request, true, $attributes)->willReturn([])->shouldBeCalled();

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(DummyGroup::class)->willReturn($resourceMetadata)->shouldBeCalled();

        $dummyGroupGroupFilterProphecy = $this->prophesize(SerializerFilterInterface::class);
        $dummyGroupGroupFilterProphecy->apply($request, true, $attributes, [])->shouldBeCalled();

        $dummyGroupSearchFilterProphecy = $this->prophesize(FilterInterface::class);

        $filterLocatorProphecy = $this->prophesize(ContainerInterface::class);
        $filterLocatorProphecy->has('dummy_group.group')->willReturn(true)->shouldBeCalled();
        $filterLocatorProphecy->get('dummy_group.group')->willReturn($dummyGroupGroupFilterProphecy->reveal())->shouldBeCalled();
        $filterLocatorProphecy->has('dummy_group.search')->willReturn(true)->shouldBeCalled();
        $filterLocatorProphecy->get('dummy_group.search')->willReturn($dummyGroupSearchFilterProphecy->reveal())->shouldBeCalled();
        $filterLocatorProphecy->has('dummy_group.nonexistent')->willReturn(false)->shouldBeCalled();

        $serializerContextBuilderFilter = new SerializerFilterContextBuilder($resourceMetadataFactoryProphecy->reveal(), $filterLocatorProphecy->reveal(), $decoratedProphecy->reveal());
        $serializerContextBuilderFilter->createFromRequest($request, true, $attributes);
    }

    public function testCreateFromRequestWithoutFilters(): void
    {
        $request = new Request();

        $attributes = [
            'resource_class' => DummyGroup::class,
            'operation_name' => 'get',
        ];

        $resourceMetadata = $this->getMetadataWithFilter(DummyGroup::class, null);

        $decoratedProphecy = $this->prophesize(SerializerContextBuilderInterface::class);
        $decoratedProphecy->createFromRequest($request, false, $attributes)->willReturn([])->shouldBeCalled();

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(DummyGroup::class)->willReturn($resourceMetadata)->shouldBeCalled();

        $filterLocatorProphecy = $this->prophesize(ContainerInterface::class);

        $serializerContextBuilderFilter = new SerializerFilterContextBuilder($resourceMetadataFactoryProphecy->reveal(), $filterLocatorProphecy->reveal(), $decoratedProphecy->reveal());
        $serializerContextBuilderFilter->createFromRequest($request, false, $attributes);
    }

    public function testCreateFromRequestWithoutAttributes(): void
    {
        $request = new Request([], [], [
            '_api_resource_class' => DummyGroup::class,
            '_api_operation_name' => 'get',
        ]);

        $attributes = [
            'resource_class' => DummyGroup::class,
            'operation_name' => 'get',
            'has_composite_identifier' => false,
            'receive' => true,
            'respond' => true,
            'persist' => true,
        ];

        $resourceMetadata = $this->getMetadataWithFilter(DummyGroup::class, ['dummy_group.group', 'dummy_group.search', 'dummy_group.nonexistent']);

        $decoratedProphecy = $this->prophesize(SerializerContextBuilderInterface::class);
        $decoratedProphecy->createFromRequest($request, true, $attributes)->willReturn([])->shouldBeCalled();

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(DummyGroup::class)->willReturn($resourceMetadata)->shouldBeCalled();

        $dummyGroupGroupFilterProphecy = $this->prophesize(SerializerFilterInterface::class);
        $dummyGroupGroupFilterProphecy->apply($request, true, $attributes, [])->shouldBeCalled();

        $dummyGroupSearchFilterProphecy = $this->prophesize(FilterInterface::class);

        $filterLocatorProphecy = $this->prophesize(ContainerInterface::class);
        $filterLocatorProphecy->has('dummy_group.group')->willReturn(true)->shouldBeCalled();
        $filterLocatorProphecy->get('dummy_group.group')->willReturn($dummyGroupGroupFilterProphecy->reveal())->shouldBeCalled();
        $filterLocatorProphecy->has('dummy_group.search')->willReturn(true)->shouldBeCalled();
        $filterLocatorProphecy->get('dummy_group.search')->willReturn($dummyGroupSearchFilterProphecy->reveal())->shouldBeCalled();
        $filterLocatorProphecy->has('dummy_group.nonexistent')->willReturn(false)->shouldBeCalled();

        $serializerContextBuilderFilter = new SerializerFilterContextBuilder($resourceMetadataFactoryProphecy->reveal(), $filterLocatorProphecy->reveal(), $decoratedProphecy->reveal());
        $serializerContextBuilderFilter->createFromRequest($request, true);
    }

    public function testCreateFromRequestThrowsExceptionWithoutAttributesAndRequestAttributes(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Request attributes are not valid.');

        $request = new Request();

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $filterLocatorProphecy = $this->prophesize(ContainerInterface::class);
        $decoratedProphecy = $this->prophesize(SerializerContextBuilderInterface::class);

        $serializerContextBuilderFilter = new SerializerFilterContextBuilder($resourceMetadataFactoryProphecy->reveal(), $filterLocatorProphecy->reveal(), $decoratedProphecy->reveal());
        $serializerContextBuilderFilter->createFromRequest($request, true);
    }

    private function getMetadataWithFilter(string $class, ?array $filters = null): ResourceMetadataCollection
    {
        return new ResourceMetadataCollection($class, [
            new ApiResource(operations: [
                'get' => new Get(name: 'get', filters: $filters),
            ]),
        ]);
    }
}
