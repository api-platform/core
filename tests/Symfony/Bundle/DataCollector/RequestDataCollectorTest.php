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

namespace ApiPlatform\Tests\Symfony\Bundle\DataCollector;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Symfony\Bundle\DataCollector\RequestDataCollector;
use ApiPlatform\Tests\Fixtures\DummyEntity;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\VarDumper\Cloner\Data;

/**
 * @author Julien DENIAU <julien.deniau@gmail.com>
 */
class RequestDataCollectorTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy|Request $request;
    private MockObject|Response $response;
    private ObjectProphecy|ParameterBag $attributes;
    private ObjectProphecy|ResourceMetadataCollectionFactoryInterface $metadataFactory;
    private ObjectProphecy|ContainerInterface $filterLocator;

    protected function setUp(): void
    {
        $this->response = $this->createMock(Response::class);
        $this->attributes = $this->prophesize(ParameterBag::class);
        $this->request = $this->prophesize(Request::class);
        $this->request
            ->getAcceptableContentTypes()
            ->shouldBeCalled()
            ->willReturn(['foo', 'bar']);

        $this->metadataFactory = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $this->filterLocator = $this->prophesize(ContainerInterface::class);
    }

    public function testNoResourceClass(): void
    {
        $this->apiResourceClassWillReturn(null);

        $dataCollector = new RequestDataCollector(
            $this->metadataFactory->reveal(),
            $this->filterLocator->reveal()
        );

        $dataCollector->collect(
            $this->request->reveal(),
            $this->response
        );

        $this->assertEquals(['foo', 'bar'], $dataCollector->getAcceptableContentTypes());
        $this->assertEquals([], $dataCollector->getResources());
    }

    public function testNotCallingCollect(): void
    {
        $this->request
            ->getAcceptableContentTypes()
            ->shouldNotBeCalled();
        $dataCollector = new RequestDataCollector(
            $this->metadataFactory->reveal(),
            $this->filterLocator->reveal()
        );

        $this->assertEquals([], $dataCollector->getAcceptableContentTypes());
        $this->assertEquals([], $dataCollector->getResources());
    }

    public function testWithResource(): void
    {
        $this->apiResourceClassWillReturn(DummyEntity::class, ['_api_operation_name' => 'get']);
        $this->request->attributes = $this->attributes->reveal();

        $this->filterLocator->has('foo')->willReturn(false)->shouldBeCalled();
        $this->filterLocator->has('a_filter')->willReturn(true)->shouldBeCalled();
        $this->filterLocator->get('a_filter')->willReturn(new \stdClass())->shouldBeCalled();

        $dataCollector = new RequestDataCollector(
            $this->metadataFactory->reveal(),
            $this->filterLocator->reveal()
        );

        $dataCollector->collect(
            $this->request->reveal(),
            $this->response
        );

        $this->assertEquals(['foo', 'bar'], $dataCollector->getAcceptableContentTypes());

        $resource = $dataCollector->getResources()[0];
        $this->assertSame(DummyEntity::class, $resource->getResourceClass());
        $this->assertEquals([['foo' => null, 'a_filter' => \stdClass::class]], $resource->getFilters());
        $this->assertEquals(['ignored_filters' => 1], $resource->getCounters());
        $this->assertInstanceOf(Data::class, $resource->getResourceMetadataCollection());
    }

    public function testWithResourceWithTraceables(): void
    {
        $this->apiResourceClassWillReturn(DummyEntity::class);

        $this->filterLocator->has('a_filter')->willReturn(false);
        $this->filterLocator->has('foo')->willReturn(false);

        $dataCollector = new RequestDataCollector(
            $this->metadataFactory->reveal(),
            $this->filterLocator->reveal(),
        );

        $dataCollector->collect(
            $this->request->reveal(),
            $this->response
        );
    }

    private function apiResourceClassWillReturn(?string $data, array $context = []): void
    {
        $this->attributes->get('_api_resource_class')->shouldBeCalled()->willReturn($data);
        $this->attributes->get('_api_operation_name')->shouldBeCalled()->willReturn($context['_api_operation_name'] ?? null);
        $this->attributes->get('_api_operation')->shouldBeCalled()->willReturn($context['_api_operation'] ?? new Get());
        $this->attributes->get('_graphql', false)->shouldBeCalled()->willReturn(false);
        $this->attributes->all()->willReturn([
            '_api_resource_class' => $data,
        ] + $context);
        $this->request->attributes = $this->attributes->reveal();

        if (!$data) {
            $this->metadataFactory
                ->create()
                ->shouldNotBeCalled();
        } else {
            $this->metadataFactory
                ->create($data)
                ->shouldBeCalled()
                ->willReturn(
                    new ResourceMetadataCollection($data, [(new ApiResource(operations: [new Get(filters: ['foo', 'a_filter'], uriVariables: ['id' => new Link(parameterName: 'id')])]))->withFilters(['foo', 'a_filter'])])
                );
        }
    }
}
