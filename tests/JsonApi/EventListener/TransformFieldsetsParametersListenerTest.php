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

namespace ApiPlatform\Core\Tests\JsonApi\EventListener;

use ApiPlatform\Core\JsonApi\EventListener\TransformFieldsetsParametersListener;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class TransformFieldsetsParametersListenerTest extends TestCase
{
    private $listener;

    protected function setUp()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata('dummy'));

        $this->listener = new TransformFieldsetsParametersListener($resourceMetadataFactoryProphecy->reveal());
    }

    public function testOnKernelRequestWithInvalidFormat()
    {
        $expectedRequest = new Request();
        $expectedRequest->setRequestFormat('badformat');

        $request = $expectedRequest->duplicate();

        $eventProphecy = $this->prophesize(GetResponseEvent::class);
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();

        $this->listener->onKernelRequest($eventProphecy->reveal());

        $this->assertEquals($expectedRequest, $request);
    }

    public function testOnKernelRequestWithInvalidFilter()
    {
        $eventProphecy = $this->prophesize(GetResponseEvent::class);

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

    public function testOnKernelRequest()
    {
        $request = new Request(
            ['fields' => ['dummy' => 'id,name,dummyFloat', 'relatedDummy' => 'id,name'], 'include' => 'relatedDummy,foo'],
            [],
            ['_api_resource_class' => Dummy::class]
        );
        $request->setRequestFormat('jsonapi');

        $eventProphecy = $this->prophesize(GetResponseEvent::class);
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

    public function testOnKernelRequestWithIncludeWithoutFields()
    {
        $request = new Request(
            ['include' => 'relatedDummy,foo'],
            [],
            ['_api_resource_class' => Dummy::class]
        );
        $request->setRequestFormat('jsonapi');

        $eventProphecy = $this->prophesize(GetResponseEvent::class);
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

    public function testOnKernelRequestWithWrongParametersTypesDoesnTAffectRequestAttributes()
    {
        $request = new Request(
            ['fields' => 'foo', 'include' => ['relatedDummy,foo']],
            [],
            ['_api_resource_class' => Dummy::class]
        );
        $request->setRequestFormat('jsonapi');

        $eventProphecy = $this->prophesize(GetResponseEvent::class);
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
