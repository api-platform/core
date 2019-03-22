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

use ApiPlatform\Core\Api\FormatsProviderInterface;
use ApiPlatform\Core\EventListener\AddFormatListener;
use Negotiation\Negotiator;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class AddFormatListenerTest extends TestCase
{
    public function testNoResourceClass()
    {
        $request = new Request();

        $eventProphecy = $this->prophesize(GetResponseEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $event = $eventProphecy->reveal();

        $formatsProviderProphecy = $this->prophesize(FormatsProviderInterface::class);

        $listener = new AddFormatListener(new Negotiator(), $formatsProviderProphecy->reveal());
        $listener->onKernelRequest($event);

        $this->assertNull($request->getRequestFormat(null));
    }

    public function testSupportedRequestFormat()
    {
        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'get']);
        $request->setRequestFormat('xml');

        $eventProphecy = $this->prophesize(GetResponseEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $event = $eventProphecy->reveal();

        $formatsProviderProphecy = $this->prophesize(FormatsProviderInterface::class);
        $formatsProviderProphecy->getFormatsFromAttributes(Argument::any())->willReturn(['xml' => ['text/xml']]);

        $listener = new AddFormatListener(new Negotiator(), $formatsProviderProphecy->reveal());
        $listener->onKernelRequest($event);

        $this->assertSame('xml', $request->getRequestFormat());
        $this->assertSame('text/xml', $request->getMimeType($request->getRequestFormat()));
    }

    public function testRespondFlag()
    {
        $request = new Request([], [], ['_api_respond' => true]);
        $request->setRequestFormat('xml');

        $eventProphecy = $this->prophesize(GetResponseEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $event = $eventProphecy->reveal();

        $formatsProviderProphecy = $this->prophesize(FormatsProviderInterface::class);
        $formatsProviderProphecy->getFormatsFromAttributes(Argument::any())->willReturn(['xml' => ['text/xml']]);

        $listener = new AddFormatListener(new Negotiator(), $formatsProviderProphecy->reveal());
        $listener->onKernelRequest($event);

        $this->assertSame('xml', $request->getRequestFormat());
        $this->assertSame('text/xml', $request->getMimeType($request->getRequestFormat()));
    }

    public function testUnsupportedRequestFormat()
    {
        $this->expectException(NotAcceptableHttpException::class);
        $this->expectExceptionMessage('Requested format "text/xml" is not supported. Supported MIME types are "application/json".');

        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'get']);
        $request->setRequestFormat('xml');

        $eventProphecy = $this->prophesize(GetResponseEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $event = $eventProphecy->reveal();

        $formatsProviderProphecy = $this->prophesize(FormatsProviderInterface::class);
        $formatsProviderProphecy->getFormatsFromAttributes(Argument::any())->willReturn(['json' => ['application/json']]);

        $listener = new AddFormatListener(new Negotiator(), $formatsProviderProphecy->reveal());
        $listener->onKernelRequest($event);

        $this->assertSame('json', $request->getRequestFormat());
    }

    public function testSupportedAcceptHeader()
    {
        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'get']);
        $request->headers->set('Accept', 'text/html, application/xhtml+xml, application/xml, application/json;q=0.9, */*;q=0.8');

        $eventProphecy = $this->prophesize(GetResponseEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $event = $eventProphecy->reveal();

        $formatsProviderProphecy = $this->prophesize(FormatsProviderInterface::class);
        $formatsProviderProphecy->getFormatsFromAttributes(Argument::any())->willReturn(['binary' => ['application/octet-stream'], 'json' => ['application/json']]);

        $listener = new AddFormatListener(new Negotiator(), $formatsProviderProphecy->reveal());
        $listener->onKernelRequest($event);

        $this->assertSame('json', $request->getRequestFormat());
    }

    public function testSupportedAcceptHeaderSymfonyBuiltInFormat()
    {
        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'get']);
        $request->headers->set('Accept', 'application/json');

        $eventProphecy = $this->prophesize(GetResponseEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $event = $eventProphecy->reveal();

        $formatsProviderProphecy = $this->prophesize(FormatsProviderInterface::class);
        $formatsProviderProphecy->getFormatsFromAttributes(Argument::any())->willReturn(['jsonld' => ['application/ld+json', 'application/json']]);

        $listener = new AddFormatListener(new Negotiator(), $formatsProviderProphecy->reveal());
        $listener->onKernelRequest($event);

        $this->assertSame('jsonld', $request->getRequestFormat());
    }

    public function testAcceptAllHeader()
    {
        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'get']);
        $request->headers->set('Accept', 'text/html, application/xhtml+xml, application/xml;q=0.9, */*;q=0.8');

        $eventProphecy = $this->prophesize(GetResponseEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $event = $eventProphecy->reveal();

        $formatsProviderProphecy = $this->prophesize(FormatsProviderInterface::class);
        $formatsProviderProphecy->getFormatsFromAttributes(Argument::any())->willReturn(['binary' => ['application/octet-stream'], 'json' => ['application/json']]);

        $listener = new AddFormatListener(new Negotiator(), $formatsProviderProphecy->reveal());
        $listener->onKernelRequest($event);

        $this->assertSame('binary', $request->getRequestFormat());
        $this->assertSame('application/octet-stream', $request->getMimeType($request->getRequestFormat()));
    }

    public function testUnsupportedAcceptHeader()
    {
        $this->expectException(NotAcceptableHttpException::class);
        $this->expectExceptionMessage('Requested format "text/html, application/xhtml+xml, application/xml;q=0.9" is not supported. Supported MIME types are "application/octet-stream", "application/json".');

        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'get']);
        $request->headers->set('Accept', 'text/html, application/xhtml+xml, application/xml;q=0.9');

        $eventProphecy = $this->prophesize(GetResponseEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $event = $eventProphecy->reveal();

        $formatsProviderProphecy = $this->prophesize(FormatsProviderInterface::class);
        $formatsProviderProphecy->getFormatsFromAttributes(Argument::any())->willReturn(['binary' => ['application/octet-stream'], 'json' => ['application/json']]);

        $listener = new AddFormatListener(new Negotiator(), $formatsProviderProphecy->reveal());
        $listener->onKernelRequest($event);
    }

    public function testUnsupportedAcceptHeaderSymfonyBuiltInFormat()
    {
        $this->expectException(NotAcceptableHttpException::class);
        $this->expectExceptionMessage('Requested format "text/xml" is not supported. Supported MIME types are "application/json".');

        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'get']);
        $request->headers->set('Accept', 'text/xml');

        $eventProphecy = $this->prophesize(GetResponseEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $event = $eventProphecy->reveal();

        $formatsProviderProphecy = $this->prophesize(FormatsProviderInterface::class);
        $formatsProviderProphecy->getFormatsFromAttributes(Argument::any())->willReturn(['json' => ['application/json']]);

        $listener = new AddFormatListener(new Negotiator(), $formatsProviderProphecy->reveal());
        $listener->onKernelRequest($event);
    }

    public function testInvalidAcceptHeader()
    {
        $this->expectException(NotAcceptableHttpException::class);
        $this->expectExceptionMessage('Requested format "invalid" is not supported. Supported MIME types are "application/json".');

        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'get']);
        $request->headers->set('Accept', 'invalid');

        $eventProphecy = $this->prophesize(GetResponseEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $event = $eventProphecy->reveal();

        $formatsProviderProphecy = $this->prophesize(FormatsProviderInterface::class);
        $formatsProviderProphecy->getFormatsFromAttributes(Argument::any())->willReturn(['json' => ['application/json']]);

        $listener = new AddFormatListener(new Negotiator(), $formatsProviderProphecy->reveal());
        $listener->onKernelRequest($event);
    }

    public function testAcceptHeaderTakePrecedenceOverRequestFormat()
    {
        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'get']);
        $request->headers->set('Accept', 'application/json');
        $request->setRequestFormat('xml');

        $eventProphecy = $this->prophesize(GetResponseEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $event = $eventProphecy->reveal();

        $formatsProviderProphecy = $this->prophesize(FormatsProviderInterface::class);
        $formatsProviderProphecy->getFormatsFromAttributes(Argument::any())->willReturn(['xml' => ['application/xml'], 'json' => ['application/json']]);

        $listener = new AddFormatListener(new Negotiator(), $formatsProviderProphecy->reveal());
        $listener->onKernelRequest($event);

        $this->assertSame('json', $request->getRequestFormat());
    }

    public function testInvalidRouteFormat()
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Format "invalid" is not supported');

        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'get', '_format' => 'invalid']);

        $eventProphecy = $this->prophesize(GetResponseEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $event = $eventProphecy->reveal();

        $formatsProviderProphecy = $this->prophesize(FormatsProviderInterface::class);
        $formatsProviderProphecy->getFormatsFromAttributes(Argument::any())->willReturn(['json' => ['application/json']]);

        $listener = new AddFormatListener(new Negotiator(), $formatsProviderProphecy->reveal());
        $listener->onKernelRequest($event);
    }

    public function testResourceClassSupportedRequestFormat()
    {
        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'get']);
        $request->setRequestFormat('csv');

        $eventProphecy = $this->prophesize(GetResponseEvent::class);
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();
        $event = $eventProphecy->reveal();

        $formatsProviderProphecy = $this->prophesize(FormatsProviderInterface::class);
        $formatsProviderProphecy->getFormatsFromAttributes([
            'resource_class' => 'Foo',
            'collection_operation_name' => 'get',
            'receive' => true,
            'respond' => true,
            'persist' => true,
        ])->willReturn(['csv' => ['text/csv']])->shouldBeCalled();

        $listener = new AddFormatListener(new Negotiator(), $formatsProviderProphecy->reveal());
        $listener->onKernelRequest($event);

        $this->assertSame('csv', $request->getRequestFormat());
        $this->assertSame('text/csv', $request->getMimeType($request->getRequestFormat()));
    }

    public function testBadFormatsProviderParameterThrowsException()
    {
        $this->expectException(\ApiPlatform\Core\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "$formatsProvider" argument is expected to be an implementation of the "ApiPlatform\\Core\\Api\\FormatsProviderInterface" interface.');

        new AddFormatListener(new Negotiator(), 'foo');
    }

    /**
     * @group legacy
     * @expectedDeprecation Using an array as formats provider is deprecated since API Platform 2.3 and will not be possible anymore in API Platform 3
     */
    public function testLegacyFormatsParameter()
    {
        new AddFormatListener(new Negotiator(), ['xml' => ['text/xml']]);
    }
}
