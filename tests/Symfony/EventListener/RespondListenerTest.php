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
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Symfony\EventListener\RespondListener;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @group legacy
 */
class RespondListenerTest extends TestCase
{
    use ProphecyTrait;

    public function testDoNotHandleResponse(): void
    {
        $listener = new RespondListener();
        $event = new ViewEvent($this->prophesize(HttpKernelInterface::class)->reveal(), new Request(), \defined(HttpKernelInterface::class.'::MAIN_REQUEST') ? HttpKernelInterface::MAIN_REQUEST : HttpKernelInterface::MASTER_REQUEST, null);
        $listener->onKernelView($event);

        $this->assertNull($event->getResponse());
    }

    public function testDoNotHandleWhenRespondFlagIsFalse(): void
    {
        $listener = new RespondListener();
        $event = new ViewEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            new Request([], [], ['_api_respond' => false]),
            \defined(HttpKernelInterface::class.'::MAIN_REQUEST') ? HttpKernelInterface::MAIN_REQUEST : HttpKernelInterface::MASTER_REQUEST,
            'foo'
        );
        $listener->onKernelView($event);

        $this->assertNull($event->getResponse());
    }

    public function testCreate200Response(): void
    {
        $request = new Request([], [], ['_api_respond' => true]);
        $request->setRequestFormat('xml');

        $event = new ViewEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $request,
            \defined(HttpKernelInterface::class.'::MAIN_REQUEST') ? HttpKernelInterface::MAIN_REQUEST : HttpKernelInterface::MASTER_REQUEST,
            'foo'
        );

        $listener = new RespondListener();
        $listener->onKernelView($event);

        $response = $event->getResponse();
        $this->assertSame('foo', $response->getContent());
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('text/xml; charset=utf-8', $response->headers->get('Content-Type'));
        $this->assertSame('Accept', $response->headers->get('Vary'));
        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertSame('deny', $response->headers->get('X-Frame-Options'));
    }

    public function testPost200WithoutLocation(): void
    {
        $request = new Request([], [], ['_api_resource_class' => Dummy::class, '_api_operation_name' => 'post', '_api_respond' => true, '_api_write_item_iri' => '/dummy_entities/1']);
        $request->setMethod('POST');

        $event = new ViewEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $request,
            \defined(HttpKernelInterface::class.'::MAIN_REQUEST') ? HttpKernelInterface::MAIN_REQUEST : HttpKernelInterface::MASTER_REQUEST,
            'bar'
        );
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn(new ResourceMetadataCollection(Dummy::class, [
            new ApiResource(operations: [
                'post' => new Post(status: Response::HTTP_OK),
            ]),
        ]));

        $listener = new RespondListener($resourceMetadataFactoryProphecy->reveal());
        $listener->onKernelView($event);

        $response = $event->getResponse();
        $this->assertFalse($response->headers->has('Location'));
        $this->assertSame(Response::HTTP_OK, $event->getResponse()->getStatusCode());
    }

    public function testPost301WithLocation(): void
    {
        $request = new Request([], [], ['_api_resource_class' => Dummy::class, '_api_operation_name' => 'get', '_api_respond' => true, '_api_write_item_iri' => '/dummy_entities/1']);
        $request->setMethod('POST');

        $event = new ViewEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $request,
            \defined(HttpKernelInterface::class.'::MAIN_REQUEST') ? HttpKernelInterface::MAIN_REQUEST : HttpKernelInterface::MASTER_REQUEST,
            'bar'
        );

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn(new ResourceMetadataCollection(Dummy::class, [
            new ApiResource(operations: [
                'get' => new Get(status: Response::HTTP_MOVED_PERMANENTLY),
            ]),
        ]));

        $listener = new RespondListener($resourceMetadataFactoryProphecy->reveal());
        $listener->onKernelView($event);

        $response = $event->getResponse();
        $this->assertTrue($response->headers->has('Location'));
        $this->assertSame('/dummy_entities/1', $response->headers->get('Location'));
        $this->assertSame(Response::HTTP_MOVED_PERMANENTLY, $event->getResponse()->getStatusCode());
    }

    public function testCreate201Response(): void
    {
        $request = new Request([], [], ['_api_respond' => true, '_api_write_item_iri' => '/dummy_entities/1']);
        $request->setMethod('POST');
        $request->setRequestFormat('xml');

        $event = new ViewEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $request,
            \defined(HttpKernelInterface::class.'::MAIN_REQUEST') ? HttpKernelInterface::MAIN_REQUEST : HttpKernelInterface::MASTER_REQUEST,
            'foo'
        );

        $listener = new RespondListener();
        $listener->onKernelView($event);

        $response = $event->getResponse();
        $this->assertSame('foo', $response->getContent());
        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        $this->assertSame('text/xml; charset=utf-8', $response->headers->get('Content-Type'));
        $this->assertSame('Accept', $response->headers->get('Vary'));
        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertSame('deny', $response->headers->get('X-Frame-Options'));
        $this->assertSame('/dummy_entities/1', $response->headers->get('Location'));
        $this->assertSame('/dummy_entities/1', $response->headers->get('Content-Location'));
        $this->assertTrue($response->headers->has('Location'));
    }

    public function testCreate204Response(): void
    {
        $request = new Request([], [], ['_api_respond' => true]);
        $request->setRequestFormat('xml');
        $request->setMethod('DELETE');

        $event = new ViewEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $request,
            \defined(HttpKernelInterface::class.'::MAIN_REQUEST') ? HttpKernelInterface::MAIN_REQUEST : HttpKernelInterface::MASTER_REQUEST,
            'foo'
        );

        $listener = new RespondListener();
        $listener->onKernelView($event);

        $response = $event->getResponse();
        $this->assertSame('foo', $response->getContent());
        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->assertSame('text/xml; charset=utf-8', $response->headers->get('Content-Type'));
        $this->assertSame('Accept', $response->headers->get('Vary'));
        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertSame('deny', $response->headers->get('X-Frame-Options'));
    }

    public function testSetSunsetHeader(): void
    {
        $event = new ViewEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            new Request([], [], ['_api_resource_class' => Dummy::class, '_api_operation_name' => 'get', '_api_respond' => true]),
            \defined(HttpKernelInterface::class.'::MAIN_REQUEST') ? HttpKernelInterface::MAIN_REQUEST : HttpKernelInterface::MASTER_REQUEST,
            'bar'
        );

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn(new ResourceMetadataCollection(Dummy::class, [
            new ApiResource(operations: [
                'get' => new Get(sunset: 'tomorrow'),
            ]),
        ]));

        $listener = new RespondListener($resourceMetadataFactoryProphecy->reveal());
        $listener->onKernelView($event);

        $response = $event->getResponse();
        /** @var string $value */
        $value = $response->headers->get('Sunset');
        $this->assertEquals(new \DateTimeImmutable('tomorrow'), \DateTime::createFromFormat(\DATE_RFC1123, $value));
    }

    public function testSetCustomStatus(): void
    {
        $event = new ViewEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            new Request([], [], ['_api_resource_class' => Dummy::class, '_api_operation_name' => 'get', '_api_respond' => true]),
            \defined(HttpKernelInterface::class.'::MAIN_REQUEST') ? HttpKernelInterface::MAIN_REQUEST : HttpKernelInterface::MASTER_REQUEST,
            'bar'
        );
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn(new ResourceMetadataCollection(Dummy::class, [
            new ApiResource(operations: [
                'get' => new Get(status: Response::HTTP_ACCEPTED),
            ]),
        ]));

        $listener = new RespondListener($resourceMetadataFactoryProphecy->reveal());
        $listener->onKernelView($event);

        $this->assertSame(Response::HTTP_ACCEPTED, $event->getResponse()->getStatusCode());
    }

    public function testSetCustomStatusForPut(): void
    {
        $request = new Request([], [], ['_api_resource_class' => Dummy::class, '_api_operation_name' => 'put', '_api_respond' => true], [], [], ['REQUEST_METHOD' => 'PUT']);
        $event = new ViewEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $request,
            \defined(HttpKernelInterface::class.'::MAIN_REQUEST') ? HttpKernelInterface::MAIN_REQUEST : HttpKernelInterface::MASTER_REQUEST,
            'bar'
        );
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn(new ResourceMetadataCollection(Dummy::class, [
            new ApiResource(operations: [
                'put' => new Put(status: Response::HTTP_ACCEPTED),
            ]),
        ]));

        $listener = new RespondListener($resourceMetadataFactoryProphecy->reveal());
        $listener->onKernelView($event);

        $this->assertSame(Response::HTTP_ACCEPTED, $event->getResponse()->getStatusCode());
    }

    public function testHandleResponse(): void
    {
        $listener = new RespondListener();

        $response = new Response();
        $event = new ViewEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            new Request([], [], ['_api_resource_class' => Dummy::class, '_api_operation_name' => 'get', '_api_respond' => true]),
            \defined(HttpKernelInterface::class.'::MAIN_REQUEST') ? HttpKernelInterface::MAIN_REQUEST : HttpKernelInterface::MASTER_REQUEST,
            $response
        );
        $listener->onKernelView($event);

        $this->assertSame($response, $event->getResponse());
    }
}
