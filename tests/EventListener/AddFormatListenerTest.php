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

namespace ApiPlatform\Core\Tests\EventListener;

use ApiPlatform\Core\EventListener\AddFormatListener;
use Negotiation\Negotiator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class AddFormatListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testNoResourceClass()
    {
        $request = new Request();

        $eventProphecy = $this->prophesize(GetResponseEvent::class);
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();
        $event = $eventProphecy->reveal();

        $listener = new AddFormatListener(new Negotiator(), ['jsonld' => 'application/ld+json']);
        $listener->onKernelRequest($event);

        $this->assertNull($request->getFormat('application/ld+json'));
    }

    public function testSupportedRequestFormat()
    {
        $request = new Request([], [], ['_api_resource_class' => 'Foo']);
        $request->setRequestFormat('xml');

        $eventProphecy = $this->prophesize(GetResponseEvent::class);
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();
        $event = $eventProphecy->reveal();

        $listener = new AddFormatListener(new Negotiator(), ['xml' => ['text/xml']]);
        $listener->onKernelRequest($event);

        $this->assertSame('xml', $request->getRequestFormat());
        $this->assertSame('text/xml', $request->getMimeType($request->getRequestFormat()));
    }

    public function testRespondFlag()
    {
        $request = new Request([], [], ['_api_respond' => true]);
        $request->setRequestFormat('xml');

        $eventProphecy = $this->prophesize(GetResponseEvent::class);
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();
        $event = $eventProphecy->reveal();

        $listener = new AddFormatListener(new Negotiator(), ['xml' => ['text/xml']]);
        $listener->onKernelRequest($event);

        $this->assertSame('xml', $request->getRequestFormat());
        $this->assertSame('text/xml', $request->getMimeType($request->getRequestFormat()));
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException
     * @expectedExceptionMessage Requested format "text/xml" is not supported. Supported MIME types are "application/json".
     */
    public function testUnsupportedRequestFormat()
    {
        $request = new Request([], [], ['_api_resource_class' => 'Foo']);
        $request->setRequestFormat('xml');

        $eventProphecy = $this->prophesize(GetResponseEvent::class);
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();
        $event = $eventProphecy->reveal();

        $listener = new AddFormatListener(new Negotiator(), ['json' => ['application/json']]);
        $listener->onKernelRequest($event);

        $this->assertSame('json', $request->getRequestFormat());
    }

    public function testSupportedAcceptHeader()
    {
        $request = new Request([], [], ['_api_resource_class' => 'Foo']);
        $request->headers->set('Accept', 'text/html, application/xhtml+xml, application/xml, application/json;q=0.9, */*;q=0.8');

        $eventProphecy = $this->prophesize(GetResponseEvent::class);
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();
        $event = $eventProphecy->reveal();

        $listener = new AddFormatListener(new Negotiator(), ['binary' => ['application/octet-stream'], 'json' => ['application/json']]);
        $listener->onKernelRequest($event);

        $this->assertSame('json', $request->getRequestFormat());
    }

    public function testAcceptAllHeader()
    {
        $request = new Request([], [], ['_api_resource_class' => 'Foo']);
        $request->headers->set('Accept', 'text/html, application/xhtml+xml, application/xml;q=0.9, */*;q=0.8');

        $eventProphecy = $this->prophesize(GetResponseEvent::class);
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();
        $event = $eventProphecy->reveal();

        $listener = new AddFormatListener(new Negotiator(), ['binary' => ['application/octet-stream'], 'json' => ['application/json']]);
        $listener->onKernelRequest($event);

        $this->assertSame('binary', $request->getRequestFormat());
        $this->assertSame('application/octet-stream', $request->getMimeType($request->getRequestFormat()));
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException
     * @expectedExceptionMessage Requested format "text/html, application/xhtml+xml, application/xml;q=0.9" is not supported. Supported MIME types are "application/octet-stream", "application/json".
     */
    public function testUnsupportedAcceptHeader()
    {
        $request = new Request([], [], ['_api_resource_class' => 'Foo']);
        $request->headers->set('Accept', 'text/html, application/xhtml+xml, application/xml;q=0.9');

        $eventProphecy = $this->prophesize(GetResponseEvent::class);
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();
        $event = $eventProphecy->reveal();

        $listener = new AddFormatListener(new Negotiator(), ['binary' => ['application/octet-stream'], 'json' => ['application/json']]);
        $listener->onKernelRequest($event);
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException
     * @expectedExceptionMessage Requested format "invalid" is not supported. Supported MIME types are "application/json".
     */
    public function testInvalidAcceptHeader()
    {
        $request = new Request([], [], ['_api_resource_class' => 'Foo']);
        $request->headers->set('Accept', 'invalid');

        $eventProphecy = $this->prophesize(GetResponseEvent::class);
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();
        $event = $eventProphecy->reveal();

        $listener = new AddFormatListener(new Negotiator(), ['json' => ['application/json']]);
        $listener->onKernelRequest($event);
    }

    public function testAcceptHeaderTakePrecedenceOverRequestFormat()
    {
        $request = new Request([], [], ['_api_resource_class' => 'Foo']);
        $request->headers->set('Accept', 'application/json');
        $request->setRequestFormat('xml');

        $eventProphecy = $this->prophesize(GetResponseEvent::class);
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();
        $event = $eventProphecy->reveal();

        $listener = new AddFormatListener(new Negotiator(), ['xml' => ['application/xml'], 'json' => ['application/json']]);
        $listener->onKernelRequest($event);

        $this->assertSame('json', $request->getRequestFormat());
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @expectedExceptionMessage Not Found
     */
    public function testInvalidRouteFormat()
    {
        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_format' => 'invalid']);

        $eventProphecy = $this->prophesize(GetResponseEvent::class);
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();
        $event = $eventProphecy->reveal();

        $listener = new AddFormatListener(new Negotiator(), ['json' => ['application/json']]);
        $listener->onKernelRequest($event);
    }
}
