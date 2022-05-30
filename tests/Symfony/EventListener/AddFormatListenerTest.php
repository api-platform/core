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

namespace ApiPlatform\Tests\Symfony\EventListener;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Symfony\EventListener\AddFormatListener;
use ApiPlatform\Tests\ProphecyTrait;
use Negotiation\Negotiator;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class AddFormatListenerTest extends TestCase
{
    use ExpectDeprecationTrait;
    use ProphecyTrait;

    public function testNoResourceClass()
    {
        $request = new Request();

        $eventProphecy = $this->prophesize(RequestEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $event = $eventProphecy->reveal();

        $listener = new AddFormatListener(new Negotiator());
        $listener->onKernelRequest($event);

        $this->assertNull($request->getRequestFormat(null));
    }

    public function testSupportedRequestFormat(): void
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->shouldBeCalled()->willReturn(new ResourceMetadataCollection('Foo', [
            (new ApiResource(operations: [
                'get' => new Get(outputFormats: ['xml' => ['text/xml']]),
            ])),
        ]));

        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_operation_name' => 'get']);
        $request->setRequestFormat('xml');

        $eventProphecy = $this->prophesize(RequestEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $event = $eventProphecy->reveal();

        $listener = new AddFormatListener(new Negotiator(), $resourceMetadataFactoryProphecy->reveal());
        $listener->onKernelRequest($event);

        $this->assertSame('xml', $request->getRequestFormat());
        $this->assertSame('text/xml', $request->getMimeType($request->getRequestFormat()));
    }

    public function testRespondFlag(): void
    {
        $request = new Request([], [], ['_api_respond' => true]);
        $request->setRequestFormat('xml');

        $eventProphecy = $this->prophesize(RequestEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $event = $eventProphecy->reveal();

        $listener = new AddFormatListener(new Negotiator(), null, ['xml' => ['text/xml']]);
        $listener->onKernelRequest($event);

        $this->assertSame('xml', $request->getRequestFormat());
        $this->assertSame('text/xml', $request->getMimeType($request->getRequestFormat()));
    }

    public function testUnsupportedRequestFormat(): void
    {
        $this->expectException(NotAcceptableHttpException::class);
        $this->expectExceptionMessage('Requested format "text/xml" is not supported. Supported MIME types are "application/json".');

        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_operation_name' => 'get']);
        $request->setRequestFormat('xml');

        $eventProphecy = $this->prophesize(RequestEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $event = $eventProphecy->reveal();

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->shouldBeCalled()->willReturn(new ResourceMetadataCollection('Foo', [
            (new ApiResource(operations: [
                'get' => new Get(outputFormats: ['json' => ['application/json']]),
            ])),
        ]));

        $listener = new AddFormatListener(new Negotiator(), $resourceMetadataFactoryProphecy->reveal());
        $listener->onKernelRequest($event);

        $this->assertSame('json', $request->getRequestFormat());
    }

    public function testSupportedAcceptHeader(): void
    {
        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_operation_name' => 'get']);
        $request->headers->set('Accept', 'text/html, application/xhtml+xml, application/xml, application/json;q=0.9, */*;q=0.8');

        $eventProphecy = $this->prophesize(RequestEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $event = $eventProphecy->reveal();

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->shouldBeCalled()->willReturn(new ResourceMetadataCollection('Foo', [
            (new ApiResource(operations: [
                'get' => new Get(outputFormats: [
                    'binary' => ['application/octet-stream'],
                    'json' => ['application/json'],
                ]),
            ])),
        ]));

        $listener = new AddFormatListener(new Negotiator(), $resourceMetadataFactoryProphecy->reveal());
        $listener->onKernelRequest($event);

        $this->assertSame('json', $request->getRequestFormat());
    }

    public function testSupportedAcceptHeaderSymfonyBuiltInFormat(): void
    {
        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_operation_name' => 'get']);
        $request->headers->set('Accept', 'application/json');

        $eventProphecy = $this->prophesize(RequestEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $event = $eventProphecy->reveal();

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->shouldBeCalled()->willReturn(new ResourceMetadataCollection('Foo', [
            (new ApiResource(operations: [
                'get' => new Get(outputFormats: [
                    'jsonld' => ['application/ld+json', 'application/json'],
                ]),
            ])),
        ]));

        $listener = new AddFormatListener(new Negotiator(), $resourceMetadataFactoryProphecy->reveal());
        $listener->onKernelRequest($event);

        $this->assertSame('jsonld', $request->getRequestFormat());
    }

    public function testAcceptAllHeader(): void
    {
        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_operation_name' => 'get']);
        $request->headers->set('Accept', 'text/html, application/xhtml+xml, application/xml;q=0.9, */*;q=0.8');

        $eventProphecy = $this->prophesize(RequestEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $event = $eventProphecy->reveal();

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->shouldBeCalled()->willReturn(new ResourceMetadataCollection('Foo', [
            (new ApiResource(operations: [
                'get' => new Get(outputFormats: [
                    'binary' => ['application/octet-stream'],
                    'json' => ['application/json'],
                ]),
            ])),
        ]));

        $listener = new AddFormatListener(new Negotiator(), $resourceMetadataFactoryProphecy->reveal());
        $listener->onKernelRequest($event);

        $this->assertSame('binary', $request->getRequestFormat());
        $this->assertSame('application/octet-stream', $request->getMimeType($request->getRequestFormat()));
    }

    public function testUnsupportedAcceptHeader(): void
    {
        $this->expectException(NotAcceptableHttpException::class);
        $this->expectExceptionMessage('Requested format "text/html, application/xhtml+xml, application/xml;q=0.9" is not supported. Supported MIME types are "application/octet-stream", "application/json".');

        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_operation_name' => 'get']);
        $request->headers->set('Accept', 'text/html, application/xhtml+xml, application/xml;q=0.9');

        $eventProphecy = $this->prophesize(RequestEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $event = $eventProphecy->reveal();

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->shouldBeCalled()->willReturn(new ResourceMetadataCollection('Foo', [
            (new ApiResource(operations: [
                'get' => new Get(outputFormats: [
                    'binary' => ['application/octet-stream'],
                    'json' => ['application/json'],
                ]),
            ])),
        ]));

        $listener = new AddFormatListener(new Negotiator(), $resourceMetadataFactoryProphecy->reveal());
        $listener->onKernelRequest($event);
    }

    public function testUnsupportedAcceptHeaderSymfonyBuiltInFormat(): void
    {
        $this->expectException(NotAcceptableHttpException::class);
        $this->expectExceptionMessage('Requested format "text/xml" is not supported. Supported MIME types are "application/json".');

        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_operation_name' => 'get']);
        $request->headers->set('Accept', 'text/xml');

        $eventProphecy = $this->prophesize(RequestEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $event = $eventProphecy->reveal();

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->shouldBeCalled()->willReturn(new ResourceMetadataCollection('Foo', [
            (new ApiResource(operations: [
                'get' => new Get(outputFormats: [
                    'json' => ['application/json'],
                ]),
            ])),
        ]));

        $listener = new AddFormatListener(new Negotiator(), $resourceMetadataFactoryProphecy->reveal());
        $listener->onKernelRequest($event);
    }

    public function testInvalidAcceptHeader(): void
    {
        $this->expectException(NotAcceptableHttpException::class);
        $this->expectExceptionMessage('Requested format "invalid" is not supported. Supported MIME types are "application/json".');

        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_operation_name' => 'get']);
        $request->headers->set('Accept', 'invalid');

        $eventProphecy = $this->prophesize(RequestEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $event = $eventProphecy->reveal();

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->shouldBeCalled()->willReturn(new ResourceMetadataCollection('Foo', [
            (new ApiResource(operations: [
                'get' => new Get(outputFormats: [
                    'json' => ['application/json'],
                ]),
            ])),
        ]));

        $listener = new AddFormatListener(new Negotiator(), $resourceMetadataFactoryProphecy->reveal());
        $listener->onKernelRequest($event);
    }

    public function testAcceptHeaderTakePrecedenceOverRequestFormat(): void
    {
        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_operation_name' => 'get']);
        $request->headers->set('Accept', 'application/json');
        $request->setRequestFormat('xml');

        $eventProphecy = $this->prophesize(RequestEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $event = $eventProphecy->reveal();

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->shouldBeCalled()->willReturn(new ResourceMetadataCollection('Foo', [
            (new ApiResource(operations: [
                'get' => new Get(outputFormats: [
                    'xml' => ['application/xml'],
                    'json' => ['application/json'],
                ]),
            ])),
        ]));

        $listener = new AddFormatListener(new Negotiator(), $resourceMetadataFactoryProphecy->reveal());
        $listener->onKernelRequest($event);

        $this->assertSame('json', $request->getRequestFormat());
    }

    public function testInvalidRouteFormat(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Format "invalid" is not supported');

        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_operation_name' => 'get', '_format' => 'invalid']);

        $eventProphecy = $this->prophesize(RequestEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $event = $eventProphecy->reveal();

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->shouldBeCalled()->willReturn(new ResourceMetadataCollection('Foo', [
            (new ApiResource(operations: [
                'get' => new Get(outputFormats: [
                    'json' => ['application/json'],
                ]),
            ])),
        ]));

        $listener = new AddFormatListener(new Negotiator(), $resourceMetadataFactoryProphecy->reveal());
        $listener->onKernelRequest($event);
    }

    public function testResourceClassSupportedRequestFormat(): void
    {
        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_operation_name' => 'get']);
        $request->setRequestFormat('csv');

        $eventProphecy = $this->prophesize(RequestEvent::class);
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();
        $event = $eventProphecy->reveal();

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->shouldBeCalled()->willReturn(new ResourceMetadataCollection('Foo', [
            (new ApiResource(operations: [
                'get' => new Get(outputFormats: [
                    'csv' => ['text/csv'],
                ]),
            ])),
        ]));

        $listener = new AddFormatListener(new Negotiator(), $resourceMetadataFactoryProphecy->reveal());
        $listener->onKernelRequest($event);

        $this->assertSame('csv', $request->getRequestFormat());
        $this->assertSame('text/csv', $request->getMimeType($request->getRequestFormat()));
    }
}
