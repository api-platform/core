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

    private $request;
    private $response;
    private $attributes;
    private $metadataFactory;
    private $filterLocator;

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

    public function testNoResourceClass()
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

        $this->assertSame([], $dataCollector->getRequestAttributes());
        $this->assertSame([], $dataCollector->getFilters());
        $this->assertSame(['ignored_filters' => 0], $dataCollector->getCounters());
        $this->assertSame(['foo', 'bar'], $dataCollector->getAcceptableContentTypes());
        $this->assertNull($dataCollector->getResourceClass());
        $this->assertEmpty($dataCollector->getResourceMetadataCollection()->getValue());
    }

    public function testNotCallingCollect()
    {
        $this->request
            ->getAcceptableContentTypes()
            ->shouldNotBeCalled();
        $dataCollector = new RequestDataCollector(
            $this->metadataFactory->reveal(),
            $this->filterLocator->reveal()
        );

        $this->assertSame([], $dataCollector->getRequestAttributes());
        $this->assertSame([], $dataCollector->getAcceptableContentTypes());
        $this->assertSame([], $dataCollector->getFilters());
        $this->assertSame([], $dataCollector->getCounters());
        $this->assertNull($dataCollector->getResourceClass());
        $this->assertNull($dataCollector->getResourceMetadataCollection());
    }

    public function testWithResource()
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

        $this->assertSame([
            'resource_class' => DummyEntity::class,
            'has_composite_identifier' => false,
            'operation_name' => 'get',
            'receive' => true,
            'respond' => true,
            'persist' => true,
        ], $dataCollector->getRequestAttributes());
        $this->assertSame(['foo', 'bar'], $dataCollector->getAcceptableContentTypes());
        $this->assertSame(DummyEntity::class, $dataCollector->getResourceClass());
        $this->assertSame([['foo' => null, 'a_filter' => \stdClass::class]], $dataCollector->getFilters());
        $this->assertSame(['ignored_filters' => 1], $dataCollector->getCounters());
        $this->assertInstanceOf(Data::class, $dataCollector->getResourceMetadataCollection());
    }

    public function testWithResourceWithTraceables()
    {
        $this->apiResourceClassWillReturn(DummyEntity::class);

        $dataCollector = new RequestDataCollector(
            $this->metadataFactory->reveal(),
            $this->filterLocator->reveal(),
        );

        $dataCollector->collect(
            $this->request->reveal(),
            $this->response
        );
    }

    public function testVersionCollection()
    {
        $this->apiResourceClassWillReturn(DummyEntity::class);

        $dataCollector = new RequestDataCollector(
            $this->metadataFactory->reveal(),
            $this->filterLocator->reveal(),
        );

        $dataCollector->collect(
            $this->request->reveal(),
            $this->response
        );

        $this->assertSame(null !== $dataCollector->getVersion(), class_exists(Versions::class));
    }

    public function testWithPreviousData()
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

    private function apiResourceClassWillReturn($data, $context = [])
    {
        $this->attributes->get('_api_resource_class')->shouldBeCalled()->willReturn($data);
        $this->attributes->all()->shouldBeCalled()->willReturn([
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
