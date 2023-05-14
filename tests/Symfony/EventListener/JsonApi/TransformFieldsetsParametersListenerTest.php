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

namespace ApiPlatform\Tests\Symfony\EventListener\JsonApi;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Symfony\EventListener\JsonApi\TransformFieldsetsParametersListener;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class TransformFieldsetsParametersListenerTest extends TestCase
{
    use ProphecyTrait;

    private TransformFieldsetsParametersListener $listener;

    protected function setUp(): void
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadataCollection(Dummy::class, [
            new ApiResource(operations: [
                'get' => new Get(shortName: 'dummy'),
            ]),
        ]));

        $this->listener = new TransformFieldsetsParametersListener($resourceMetadataFactoryProphecy->reveal());
    }

    public function testOnKernelRequestWithInvalidFormat(): void
    {
        $expectedRequest = new Request();
        $expectedRequest->setRequestFormat('badformat');

        $request = $expectedRequest->duplicate();

        $eventProphecy = $this->prophesize(RequestEvent::class);
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();

        $this->listener->onKernelRequest($eventProphecy->reveal());

        $this->assertEquals($expectedRequest, $request);
    }

    public function testOnKernelRequestWithInvalidFilter(): void
    {
        $eventProphecy = $this->prophesize(RequestEvent::class);

        $expectedRequest = new Request();
        $expectedRequest->setRequestFormat('jsonapi');

        $request = $expectedRequest->duplicate();
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();
        $this->listener->onKernelRequest($eventProphecy->reveal());

        $this->assertEquals($expectedRequest, $request);

        $expectedRequest = $expectedRequest->duplicate(['fields' => 'foo']);

        $request = $expectedRequest->duplicate();
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();
        $this->listener->onKernelRequest($eventProphecy->reveal());

        $this->assertEquals($expectedRequest, $request);
    }

    public function testOnKernelRequest(): void
    {
        $request = new Request(
            ['fields' => ['dummy' => 'id,name,dummyFloat', 'relatedDummy' => 'id,name'], 'include' => 'relatedDummy,foo'],
            [],
            ['_api_resource_class' => Dummy::class]
        );
        $request->setRequestFormat('jsonapi');

        $eventProphecy = $this->prophesize(RequestEvent::class);
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();

        $this->listener->onKernelRequest($eventProphecy->reveal());

        $expectedRequest = new Request(
            ['fields' => ['dummy' => 'id,name,dummyFloat', 'relatedDummy' => 'id,name'], 'include' => 'relatedDummy,foo'],
            [],
            [
                '_api_resource_class' => Dummy::class,
                '_api_filter_property' => ['id', 'name', 'dummyFloat', 'relatedDummy' => ['id', 'name']],
                '_api_included' => ['relatedDummy'],
            ]
        );
        $expectedRequest->setRequestFormat('jsonapi');

        $this->assertEquals($expectedRequest, $request);
    }

    public function testOnKernelRequestWithIncludeWithoutFields(): void
    {
        $request = new Request(
            ['include' => 'relatedDummy,foo'],
            [],
            ['_api_resource_class' => Dummy::class]
        );
        $request->setRequestFormat('jsonapi');

        $eventProphecy = $this->prophesize(RequestEvent::class);
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();

        $this->listener->onKernelRequest($eventProphecy->reveal());

        $expectedRequest = new Request(
            ['include' => 'relatedDummy,foo'],
            [],
            [
                '_api_resource_class' => Dummy::class,
                '_api_included' => ['relatedDummy', 'foo'],
            ]
        );
        $expectedRequest->setRequestFormat('jsonapi');

        $this->assertEquals($expectedRequest, $request);
    }

    public function testOnKernelRequestWithWrongParametersTypesDoesnTAffectRequestAttributes(): void
    {
        $request = new Request(
            ['fields' => 'foo', 'include' => ['relatedDummy,foo']],
            [],
            ['_api_resource_class' => Dummy::class]
        );
        $request->setRequestFormat('jsonapi');

        $eventProphecy = $this->prophesize(RequestEvent::class);
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();

        $this->listener->onKernelRequest($eventProphecy->reveal());

        $expectedRequest = new Request(
            ['fields' => 'foo', 'include' => ['relatedDummy,foo']],
            [],
            ['_api_resource_class' => Dummy::class]
        );
        $expectedRequest->setRequestFormat('jsonapi');

        $this->assertEquals($expectedRequest, $request);
    }
}
