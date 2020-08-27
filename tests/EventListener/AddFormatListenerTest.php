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
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\ProphecyTrait;
use Negotiation\Negotiator;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class AddFormatListenerTest extends TestCase
{
    use ProphecyTrait;

    public function testNoResourceClass()
    {
        $request = new Request();

        $eventProphecy = $this->prophesize(RequestEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $event = $eventProphecy->reveal();

        $formatsProviderProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);

        $listener = new AddFormatListener(new Negotiator(), $formatsProviderProphecy->reveal());
        $listener->onKernelRequest($event);

        $this->assertNull($request->getRequestFormat(null));
    }

    public function testSupportedRequestFormat(): void
    {
        $resourceMetadataFactory = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactory->create('Foo')->willReturn(new ResourceMetadata(
            null,
            null,
            null,
            null,
            ['get' => ['output_formats' => ['xml' => ['text/xml']]]]
        ));

        $this->doTestSupportedRequestFormat($resourceMetadataFactory->reveal());
    }

    public function testLegacySupportedRequestFormat(): void
    {
        $formatsProviderProphecy = $this->prophesize(FormatsProviderInterface::class);
        $formatsProviderProphecy->getFormatsFromAttributes(Argument::any())->willReturn(['xml' => ['text/xml']]);

        $this->doTestSupportedRequestFormat($formatsProviderProphecy->reveal());
    }

    private function doTestSupportedRequestFormat($resourceMetadataFactory): void
    {
        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'get']);
        $request->setRequestFormat('xml');

        $eventProphecy = $this->prophesize(RequestEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $event = $eventProphecy->reveal();

        $listener = new AddFormatListener(new Negotiator(), $resourceMetadataFactory);
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

        $resourceMetadataFactory = $this->prophesize(ResourceMetadataFactoryInterface::class);

        $listener = new AddFormatListener(new Negotiator(), $resourceMetadataFactory->reveal(), ['xml' => ['text/xml']]);
        $listener->onKernelRequest($event);

        $this->assertSame('xml', $request->getRequestFormat());
        $this->assertSame('text/xml', $request->getMimeType($request->getRequestFormat()));
    }

    public function testUnsupportedRequestFormat(): void
    {
        $this->expectException(NotAcceptableHttpException::class);
        $this->expectExceptionMessage('Requested format "text/xml" is not supported. Supported MIME types are "application/json".');

        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'get']);
        $request->setRequestFormat('xml');

        $eventProphecy = $this->prophesize(RequestEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $event = $eventProphecy->reveal();

        $resourceMetadataFactory = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactory->create('Foo')->willReturn(new ResourceMetadata(
            null,
            null,
            null,
            null,
            ['get' => ['output_formats' => ['json' => ['application/json']]]]
        ));

        $listener = new AddFormatListener(new Negotiator(), $resourceMetadataFactory->reveal());
        $listener->onKernelRequest($event);

        $this->assertSame('json', $request->getRequestFormat());
    }

    public function testSupportedAcceptHeader(): void
    {
        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'get']);
        $request->headers->set('Accept', 'text/html, application/xhtml+xml, application/xml, application/json;q=0.9, */*;q=0.8');

        $eventProphecy = $this->prophesize(RequestEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $event = $eventProphecy->reveal();

        $resourceMetadataFactory = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactory->create('Foo')->willReturn(new ResourceMetadata(
            null,
            null,
            null,
            null,
            ['get' => ['output_formats' => [
                'binary' => ['application/octet-stream'],
                'json' => ['application/json'], ],
            ]]
        ));

        $listener = new AddFormatListener(new Negotiator(), $resourceMetadataFactory->reveal());
        $listener->onKernelRequest($event);

        $this->assertSame('json', $request->getRequestFormat());
    }

    public function testSupportedAcceptHeaderSymfonyBuiltInFormat(): void
    {
        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'get']);
        $request->headers->set('Accept', 'application/json');

        $eventProphecy = $this->prophesize(RequestEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $event = $eventProphecy->reveal();

        $resourceMetadataFactory = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactory->create('Foo')->willReturn(new ResourceMetadata(
            null,
            null,
            null,
            null,
            ['get' => ['output_formats' => ['jsonld' => ['application/ld+json', 'application/json']]]]
        ));

        $listener = new AddFormatListener(new Negotiator(), $resourceMetadataFactory->reveal());
        $listener->onKernelRequest($event);

        $this->assertSame('jsonld', $request->getRequestFormat());
    }

    public function testAcceptAllHeader(): void
    {
        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'get']);
        $request->headers->set('Accept', 'text/html, application/xhtml+xml, application/xml;q=0.9, */*;q=0.8');

        $eventProphecy = $this->prophesize(RequestEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $event = $eventProphecy->reveal();

        $resourceMetadataFactory = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactory->create('Foo')->willReturn(new ResourceMetadata(
            null,
            null,
            null,
            null,
            ['get' => ['output_formats' => ['binary' => ['application/octet-stream'], 'json' => ['application/json']]]]
        ));

        $listener = new AddFormatListener(new Negotiator(), $resourceMetadataFactory->reveal());
        $listener->onKernelRequest($event);

        $this->assertSame('binary', $request->getRequestFormat());
        $this->assertSame('application/octet-stream', $request->getMimeType($request->getRequestFormat()));
    }

    public function testUnsupportedAcceptHeader(): void
    {
        $this->expectException(NotAcceptableHttpException::class);
        $this->expectExceptionMessage('Requested format "text/html, application/xhtml+xml, application/xml;q=0.9" is not supported. Supported MIME types are "application/octet-stream", "application/json".');

        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'get']);
        $request->headers->set('Accept', 'text/html, application/xhtml+xml, application/xml;q=0.9');

        $eventProphecy = $this->prophesize(RequestEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $event = $eventProphecy->reveal();

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(
            null,
            null,
            null,
            null,
            ['get' => ['output_formats' => ['binary' => ['application/octet-stream'], 'json' => ['application/json']]]]
        ));

        $listener = new AddFormatListener(new Negotiator(), $resourceMetadataFactoryProphecy->reveal());
        $listener->onKernelRequest($event);
    }

    public function testUnsupportedAcceptHeaderSymfonyBuiltInFormat(): void
    {
        $this->expectException(NotAcceptableHttpException::class);
        $this->expectExceptionMessage('Requested format "text/xml" is not supported. Supported MIME types are "application/json".');

        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'get']);
        $request->headers->set('Accept', 'text/xml');

        $eventProphecy = $this->prophesize(RequestEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $event = $eventProphecy->reveal();

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(
            null,
            null,
            null,
            null,
            ['get' => ['output_formats' => ['json' => ['application/json']]]]
        ));

        $listener = new AddFormatListener(new Negotiator(), $resourceMetadataFactoryProphecy->reveal());
        $listener->onKernelRequest($event);
    }

    public function testInvalidAcceptHeader(): void
    {
        $this->expectException(NotAcceptableHttpException::class);
        $this->expectExceptionMessage('Requested format "invalid" is not supported. Supported MIME types are "application/json".');

        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'get']);
        $request->headers->set('Accept', 'invalid');

        $eventProphecy = $this->prophesize(RequestEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $event = $eventProphecy->reveal();

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(
            null,
            null,
            null,
            null,
            ['get' => ['output_formats' => ['json' => ['application/json']]]]
        ));

        $listener = new AddFormatListener(new Negotiator(), $resourceMetadataFactoryProphecy->reveal());
        $listener->onKernelRequest($event);
    }

    public function testAcceptHeaderTakePrecedenceOverRequestFormat(): void
    {
        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'get']);
        $request->headers->set('Accept', 'application/json');
        $request->setRequestFormat('xml');

        $eventProphecy = $this->prophesize(RequestEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $event = $eventProphecy->reveal();

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(
            null,
            null,
            null,
            [],
            ['get' => ['output_formats' => ['xml' => ['application/xml'], 'json' => ['application/json']]]]
        ));

        $listener = new AddFormatListener(new Negotiator(), $resourceMetadataFactoryProphecy->reveal());
        $listener->onKernelRequest($event);

        $this->assertSame('json', $request->getRequestFormat());
    }

    public function testInvalidRouteFormat(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Format "invalid" is not supported');

        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'get', '_format' => 'invalid']);

        $eventProphecy = $this->prophesize(RequestEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $event = $eventProphecy->reveal();

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(
            null,
            null,
            null,
            null,
            null,
            ['formats' => ['json' => ['application/json']]]
        ));

        $listener = new AddFormatListener(new Negotiator(), $resourceMetadataFactoryProphecy->reveal());
        $listener->onKernelRequest($event);
    }

    public function testResourceClassSupportedRequestFormat(): void
    {
        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'get']);
        $request->setRequestFormat('csv');

        $eventProphecy = $this->prophesize(RequestEvent::class);
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();
        $event = $eventProphecy->reveal();

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(
            null,
            null,
            null,
            null,
            ['get' => ['output_formats' => ['csv' => ['text/csv']]]]
        ));

        $listener = new AddFormatListener(new Negotiator(), $resourceMetadataFactoryProphecy->reveal());
        $listener->onKernelRequest($event);

        $this->assertSame('csv', $request->getRequestFormat());
        $this->assertSame('text/csv', $request->getMimeType($request->getRequestFormat()));
    }

    /**
     * @group legacy
     * @expectedDeprecation Passing an array or an instance of "ApiPlatform\Core\Api\FormatsProviderInterface" as 2nd parameter of the constructor of "ApiPlatform\Core\EventListener\AddFormatListener" is deprecated since API Platform 2.5, pass an instance of "ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface" instead
     * @dataProvider legacyFormatsProvider
     */
    public function testLegacyFormatsParameter($formatsProvider): void
    {
        new AddFormatListener(new Negotiator(), $formatsProvider);
    }

    public function legacyFormatsProvider(): iterable
    {
        yield [['xml' => ['text/xml']]];
        yield [$this->prophesize(FormatsProviderInterface::class)->reveal()];
    }
}
