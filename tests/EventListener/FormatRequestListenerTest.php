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

use ApiPlatform\Core\EventListener\FormatRequestListener;
use Negotiation\Negotiator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class FormatRequestListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testNoResourceClass()
    {
        $request = new Request();

        $eventProphecy = $this->prophesize(GetResponseEvent::class);
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();
        $event = $eventProphecy->reveal();

        $listener = new FormatRequestListener(new Negotiator(), []);
        $listener->onKernelRequest($event);

        $this->assertFalse($request->attributes->has('_api_format'));
        $this->assertFalse($request->attributes->has('_api_mime_type'));
    }

    public function testSupportedRequestFormat()
    {
        $request = new Request();
        $request->attributes->set('_api_resource_class', 'Foo');
        $request->setRequestFormat('xml');

        $eventProphecy = $this->prophesize(GetResponseEvent::class);
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();
        $event = $eventProphecy->reveal();

        $listener = new FormatRequestListener(new Negotiator(), ['text/xml' => 'xml']);
        $listener->onKernelRequest($event);

        $this->assertSame('xml', $request->attributes->get('_api_format'));
        $this->assertSame('text/xml', $request->attributes->get('_api_mime_type'));
    }

    public function testUnsupportedRequestFormat()
    {
        $request = new Request();
        $request->attributes->set('_api_resource_class', 'Foo');
        $request->setRequestFormat('xml');

        $eventProphecy = $this->prophesize(GetResponseEvent::class);
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();
        $event = $eventProphecy->reveal();

        $listener = new FormatRequestListener(new Negotiator(), ['application/json' => 'json']);
        $listener->onKernelRequest($event);

        $this->assertSame('json', $request->attributes->get('_api_format'));
        $this->assertSame('application/json', $request->attributes->get('_api_mime_type'));
    }

    public function testSupportedAcceptHeader()
    {
        $request = new Request();
        $request->attributes->set('_api_resource_class', 'Foo');
        $request->headers->set('Accept', 'text/html, application/xhtml+xml, application/xml, application/json;q=0.9, */*;q=0.8');

        $eventProphecy = $this->prophesize(GetResponseEvent::class);
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();
        $event = $eventProphecy->reveal();

        $listener = new FormatRequestListener(new Negotiator(), ['application/octet-stream' => 'binary', 'application/json' => 'json']);
        $listener->onKernelRequest($event);

        $this->assertSame('json', $request->attributes->get('_api_format'));
        $this->assertSame('application/json', $request->attributes->get('_api_mime_type'));
    }

    public function testUnsupportedAcceptHeader()
    {
        $request = new Request();
        $request->attributes->set('_api_resource_class', 'Foo');
        $request->headers->set('Accept', 'text/html, application/xhtml+xml, application/xml;q=0.9, */*;q=0.8');

        $eventProphecy = $this->prophesize(GetResponseEvent::class);
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();
        $event = $eventProphecy->reveal();

        $listener = new FormatRequestListener(new Negotiator(), ['application/octet-stream' => 'binary', 'application/json' => 'json']);
        $listener->onKernelRequest($event);

        $this->assertSame('binary', $request->attributes->get('_api_format'));
        $this->assertSame('application/octet-stream', $request->attributes->get('_api_mime_type'));
    }
}
