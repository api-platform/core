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

use ApiPlatform\Core\EventListener\ResponderViewListener;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ResponderViewListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testDoNotHandleResponse()
    {
        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getControllerResult()->willReturn(new Response());
        $eventProphecy->getRequest()->willReturn(new Request([], [], ['_api_mime_type' => 'text/xml']));

        $listener = new ResponderViewListener();
        $listener->onKernelView($eventProphecy->reveal());
    }

    public function testDoNotHandleWhenMimeTypeNotSet()
    {
        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getControllerResult()->willReturn('foo');
        $eventProphecy->getRequest()->willReturn(new Request());

        $listener = new ResponderViewListener();
        $listener->onKernelView($eventProphecy->reveal());
    }

    public function testCreate200Response()
    {
        $kernelProphecy = $this->prophesize(HttpKernelInterface::class);
        $event = new GetResponseForControllerResultEvent(
            $kernelProphecy->reveal(),
            new Request([], [], ['_api_mime_type' => 'text/xml']),
            HttpKernelInterface::MASTER_REQUEST,
            'foo'
        );

        $listener = new ResponderViewListener();
        $listener->onKernelView($event);

        $response = $event->getResponse();
        $this->assertEquals('foo', $response->getContent());
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals('text/xml', $response->headers->get('Content-Type'));
    }

    public function testCreate201Response()
    {
        $kernelProphecy = $this->prophesize(HttpKernelInterface::class);

        $request = new Request([], [], ['_api_mime_type' => 'text/xml']);
        $request->setMethod(Request::METHOD_POST);

        $event = new GetResponseForControllerResultEvent(
            $kernelProphecy->reveal(),
            $request,
            HttpKernelInterface::MASTER_REQUEST,
            'foo'
        );

        $listener = new ResponderViewListener();
        $listener->onKernelView($event);

        $response = $event->getResponse();
        $this->assertEquals('foo', $response->getContent());
        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
        $this->assertEquals('text/xml', $response->headers->get('Content-Type'));
    }

    public function testCreate204Response()
    {
        $kernelProphecy = $this->prophesize(HttpKernelInterface::class);

        $request = new Request([], [], ['_api_mime_type' => 'text/xml']);
        $request->setMethod(Request::METHOD_DELETE);

        $event = new GetResponseForControllerResultEvent(
            $kernelProphecy->reveal(),
            $request,
            HttpKernelInterface::MASTER_REQUEST,
            'foo'
        );

        $listener = new ResponderViewListener();
        $listener->onKernelView($event);

        $response = $event->getResponse();
        $this->assertEquals('foo', $response->getContent());
        $this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->assertEquals('text/xml', $response->headers->get('Content-Type'));
    }
}
