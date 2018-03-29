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
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author Julien DENIAU <julien.deniau@gmail.com>
 */
class RequestDataCollectorTest extends TestCase
{
    private $request;

    private $response;

    private $attributes;

    private $metadataFactory;

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
    }

    public function testNoResourceClass()
    {
        $this->apiResourceClassWillReturn(null);
        $this->request
            ->getMethod()
            ->shouldBeCalled()
            ->willReturn('GET');

        $dataCollector = new RequestDataCollector(
            $this->metadataFactory->reveal()
        );

        $dataCollector->collect(
            $this->request->reveal(),
            $this->response
        );

        $this->assertEquals($dataCollector->getMethod(), 'GET');
        $this->assertEquals($dataCollector->getAcceptableContentTypes(), ['foo', 'bar']);
        $this->assertNull($dataCollector->getResourceClass());
        $this->assertNull($dataCollector->getResourceMetadata());
    }

    public function testNotCallingCollect()
    {
        $this->request
            ->getAcceptableContentTypes()
            ->shouldNotBeCalled();
        $dataCollector = new RequestDataCollector(
            $this->metadataFactory->reveal()
        );

        $this->assertEquals($dataCollector->getMethod(), '');
        $this->assertEquals($dataCollector->getAcceptableContentTypes(), []);
        $this->assertNull($dataCollector->getResourceClass());
        $this->assertNull($dataCollector->getResourceMetadata());
    }

    public function testWithRessource()
    {
        $this->apiResourceClassWillReturn(DummyEntity::class);
        $this->request
            ->getMethod()
            ->shouldBeCalled()
            ->willReturn('GET');

        $dataCollector = new RequestDataCollector(
            $this->metadataFactory->reveal()
        );

        $dataCollector->collect(
            $this->request->reveal(),
            $this->response
        );

        $this->assertEquals($dataCollector->getMethod(), 'GET');
        $this->assertEquals($dataCollector->getAcceptableContentTypes(), ['foo', 'bar']);
        $this->assertEquals($dataCollector->getResourceClass(), DummyEntity::class);
        $this->assertInstanceOf(ResourceMetadata::class, $dataCollector->getResourceMetadata());
    }

    private function apiResourceClassWillReturn($data)
    {
        $this->attributes->get('_api_resource_class')
            ->shouldBeCalled()
            ->willReturn($data);
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
                    new ResourceMetadata()
                );
        }
    }
}
