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

namespace ApiPlatform\Core\Tests\Bridge\Symfony\Bundle\DataCollector;

use ApiPlatform\Core\Bridge\Symfony\Bundle\DataCollector\RequestDataCollector;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\Fixtures\DummyEntity;
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
    private $request;
    private $response;
    private $attributes;
    private $metadataFactory;
    private $filterLocator;

    public function setUp()
    {
        $this->response = $this->createMock(Response::class);
        $this->attributes = $this->prophesize(ParameterBag::class);
        $this->request = $this->prophesize(Request::class);
        $this->request
            ->getAcceptableContentTypes()
            ->shouldBeCalled()
            ->willReturn(['foo', 'bar']);

        $this->metadataFactory = $this->prophesize(ResourceMetadataFactoryInterface::class);
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
        $this->assertNull($dataCollector->getResourceMetadata());
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
        $this->assertNull($dataCollector->getResourceMetadata());
    }

    public function testWithResource()
    {
        $this->apiResourceClassWillReturn(DummyEntity::class, ['_api_item_operation_name' => 'get', '_api_receive' => true]);
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

        $this->assertSame(['resource_class' => DummyEntity::class,  'item_operation_name' => 'get', 'receive' => true], $dataCollector->getRequestAttributes());
        $this->assertSame(['foo', 'bar'], $dataCollector->getAcceptableContentTypes());
        $this->assertSame(DummyEntity::class, $dataCollector->getResourceClass());
        $this->assertSame(['foo' => null, 'a_filter' => \stdClass::class], $dataCollector->getFilters());
        $this->assertSame(['ignored_filters' => 1], $dataCollector->getCounters());
        $this->assertInstanceOf(Data::class, $dataCollector->getResourceMetadata());
        $this->assertSame(ResourceMetadata::class, $dataCollector->getResourceMetadata()->getType());
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
                    new ResourceMetadata(null, null, null, [], [], ['filters' => ['foo', 'a_filter']])
                );
        }
    }
}
