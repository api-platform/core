<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Tests\EventListener;

use ApiPlatform\Core\EventListener\RespondListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class RespondListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testDoNotHandleResponse()
    {
        $request = new Request();
        $request->setRequestFormat('xml');

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getControllerResult()->willReturn(new Response())->shouldBeCalled();
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();

        $listener = new RespondListener();
        $listener->onKernelView($eventProphecy->reveal());
    }

    public function testCreate200Response()
    {
        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_item_operation_name' => 'get']);
        $request->setRequestFormat('xml');

        $kernelProphecy = $this->prophesize(HttpKernelInterface::class);
        $event = new GetResponseForControllerResultEvent(
            $kernelProphecy->reveal(),
            $request,
            HttpKernelInterface::MASTER_REQUEST,
            'foo'
        );

        $listener = new RespondListener();
        $listener->onKernelView($event);

        $response = $event->getResponse();
        $this->assertEquals('foo', $response->getContent());
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals('text/xml', $response->headers->get('Content-Type'));
    }

    public function testCreate201Response()
    {
        $kernelProphecy = $this->prophesize(HttpKernelInterface::class);

        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_item_operation_name' => 'get']);
        $request->setMethod(Request::METHOD_POST);
        $request->setRequestFormat('xml');

        $event = new GetResponseForControllerResultEvent(
            $kernelProphecy->reveal(),
            $request,
            HttpKernelInterface::MASTER_REQUEST,
            'foo'
        );

        $listener = new RespondListener();
        $listener->onKernelView($event);

        $response = $event->getResponse();
        $this->assertEquals('foo', $response->getContent());
        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
        $this->assertEquals('text/xml', $response->headers->get('Content-Type'));
    }

    public function testCreate204Response()
    {
        $kernelProphecy = $this->prophesize(HttpKernelInterface::class);

        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_item_operation_name' => 'get']);
        $request->setRequestFormat('xml');
        $request->setMethod(Request::METHOD_DELETE);

        $event = new GetResponseForControllerResultEvent(
            $kernelProphecy->reveal(),
            $request,
            HttpKernelInterface::MASTER_REQUEST,
            'foo'
        );

        $listener = new RespondListener();
        $listener->onKernelView($event);

        $response = $event->getResponse();
        $this->assertEquals('foo', $response->getContent());
        $this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->assertEquals('text/xml', $response->headers->get('Content-Type'));
    }
}
