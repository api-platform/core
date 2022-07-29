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
use ApiPlatform\Tests\ProphecyTrait;
use PackageVersions\Versions;
use PHPUnit\Framework\TestCase;
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
    private $response;
    private $attributes;
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

        $this->assertEquals([], $dataCollector->getRequestAttributes());
        $this->assertEquals([], $dataCollector->getFilters());
        $this->assertEquals(['ignored_filters' => 0], $dataCollector->getCounters());
        $this->assertEquals(['foo', 'bar'], $dataCollector->getAcceptableContentTypes());
        $this->assertNull($dataCollector->getResourceClass());
        $this->assertEmpty($dataCollector->getResourceMetadataCollection()->getValue());
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

        $this->assertEquals([], $dataCollector->getRequestAttributes());
        $this->assertEquals([], $dataCollector->getAcceptableContentTypes());
        $this->assertEquals([], $dataCollector->getFilters());
        $this->assertEquals([], $dataCollector->getCounters());
        $this->assertNull($dataCollector->getResourceClass());
        $this->assertNull($dataCollector->getResourceMetadataCollection());
    }

    public function testWithResource(): void
    {
        $this->apiResourceClassWillReturn(DummyEntity::class, ['_api_operation_name' => 'get', '_api_receive' => true]);
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

        $this->assertEquals([
            'resource_class' => DummyEntity::class,
            'has_composite_identifier' => false,
            'operation_name' => 'get',
            'receive' => true,
            'respond' => true,
            'persist' => true,
        ], $dataCollector->getRequestAttributes());
        $this->assertEquals(['foo', 'bar'], $dataCollector->getAcceptableContentTypes());
        $this->assertEquals(DummyEntity::class, $dataCollector->getResourceClass());
        $this->assertEquals([['foo' => null, 'a_filter' => \stdClass::class]], $dataCollector->getFilters());
        $this->assertEquals(['ignored_filters' => 1], $dataCollector->getCounters());
        $this->assertInstanceOf(Data::class, $dataCollector->getResourceMetadataCollection());
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

    public function testVersionCollection(): void
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

        $this->assertEquals(null !== $dataCollector->getVersion(), class_exists(Versions::class));
    }

    public function testWithPreviousData(): void
    {
        $data = new \stdClass();
        $data->a = $data;

        $this->apiResourceClassWillReturn(DummyEntity::class, ['_api_operation_name' => 'get', '_api_receive' => true, 'previous_data' => $data]);
        $this->request->attributes = $this->attributes->reveal();

        $this->filterLocator->has('foo')->willReturn(false);
        $this->filterLocator->has('a_filter')->willReturn(true);
        $this->filterLocator->get('a_filter')->willReturn(new \stdClass());

        $dataCollector = new RequestDataCollector(
            $this->metadataFactory->reveal(),
            $this->filterLocator->reveal()
        );

        $dataCollector->collect(
            $this->request->reveal(),
            $this->response
        );

        $this->assertArrayHasKey('previous_data', $requestAttributes = $dataCollector->getRequestAttributes());
        $this->assertNotSame($requestAttributes['previous_data']->data, $requestAttributes['previous_data']);
    }

    private function apiResourceClassWillReturn(?string $data, array $context = []): void
    {
        $this->attributes->get('_api_resource_class')->shouldBeCalled()->willReturn($data);
        $this->attributes->all()->shouldBeCalled()->willReturn([
            '_api_resource_class' => $data,
        ] + $context);
        $this->request->attributes = $this->attributes->reveal();

        if (!$data) {
            $this->metadataFactory // @phpstan-ignore-line
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
