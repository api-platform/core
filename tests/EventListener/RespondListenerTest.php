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

use ApiPlatform\Core\EventListener\RespondListener;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Core\Tests\ProphecyTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class RespondListenerTest extends TestCase
{
    use ProphecyTrait;

    public function testDoNotHandleResponse()
    {
        $listener = new RespondListener();
        $event = new ViewEvent($this->prophesize(HttpKernelInterface::class)->reveal(), new Request(), HttpKernelInterface::MASTER_REQUEST, null);
        $listener->onKernelView($event);

        $this->assertNull($event->getResponse());
    }

    public function testDoNotHandleWhenRespondFlagIsFalse()
    {
        $listener = new RespondListener();
        $event = new ViewEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            new Request([], [], ['_api_respond' => false]),
            HttpKernelInterface::MASTER_REQUEST,
            'foo'
        );
        $listener->onKernelView($event);

        $this->assertNull($event->getResponse());
    }

    public function testCreate200Response()
    {
        $request = new Request([], [], ['_api_respond' => true]);
        $request->setRequestFormat('xml');

        $event = new ViewEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $request,
            HttpKernelInterface::MASTER_REQUEST,
            'foo'
        );

        $listener = new RespondListener();
        $listener->onKernelView($event);

        $response = $event->getResponse();
        $this->assertEquals('foo', $response->getContent());
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals('text/xml; charset=utf-8', $response->headers->get('Content-Type'));
        $this->assertEquals('Accept', $response->headers->get('Vary'));
        $this->assertEquals('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertEquals('deny', $response->headers->get('X-Frame-Options'));
    }

    public function testPost200WithoutLocation()
    {
        $request = new Request([], [], ['_api_resource_class' => Dummy::class, '_api_item_operation_name' => 'get', '_api_respond' => true, '_api_write_item_iri' => '/dummy_entities/1']);
        $request->setMethod('POST');

        $event = new ViewEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $request,
            HttpKernelInterface::MASTER_REQUEST,
            'bar'
        );
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata(null, null, null, ['get' => ['status' => Response::HTTP_OK]]));

        $listener = new RespondListener($resourceMetadataFactoryProphecy->reveal());
        $listener->onKernelView($event);

        $response = $event->getResponse();
        $this->assertFalse($response->headers->has('Location'));
        $this->assertSame(Response::HTTP_OK, $event->getResponse()->getStatusCode());
    }

    public function testPost301WithLocation()
    {
        $request = new Request([], [], ['_api_resource_class' => Dummy::class, '_api_item_operation_name' => 'get', '_api_respond' => true, '_api_write_item_iri' => '/dummy_entities/1']);
        $request->setMethod('POST');

        $event = new ViewEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $request,
            HttpKernelInterface::MASTER_REQUEST,
            'bar'
        );
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata(null, null, null, ['get' => ['status' => Response::HTTP_MOVED_PERMANENTLY]]));

        $listener = new RespondListener($resourceMetadataFactoryProphecy->reveal());
        $listener->onKernelView($event);

        $response = $event->getResponse();
        $this->assertTrue($response->headers->has('Location'));
        $this->assertEquals('/dummy_entities/1', $response->headers->get('Location'));
        $this->assertSame(Response::HTTP_MOVED_PERMANENTLY, $event->getResponse()->getStatusCode());
    }

    public function testCreate201Response()
    {
        $request = new Request([], [], ['_api_respond' => true, '_api_write_item_iri' => '/dummy_entities/1']);
        $request->setMethod('POST');
        $request->setRequestFormat('xml');

        $event = new ViewEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $request,
            HttpKernelInterface::MASTER_REQUEST,
            'foo'
        );

        $listener = new RespondListener();
        $listener->onKernelView($event);

        $response = $event->getResponse();
        $this->assertEquals('foo', $response->getContent());
        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
        $this->assertEquals('text/xml; charset=utf-8', $response->headers->get('Content-Type'));
        $this->assertEquals('Accept', $response->headers->get('Vary'));
        $this->assertEquals('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertEquals('deny', $response->headers->get('X-Frame-Options'));
        $this->assertEquals('/dummy_entities/1', $response->headers->get('Location'));
        $this->assertEquals('/dummy_entities/1', $response->headers->get('Content-Location'));
        $this->assertTrue($response->headers->has('Location'));
    }

    public function testCreate204Response()
    {
        $request = new Request([], [], ['_api_respond' => true]);
        $request->setRequestFormat('xml');
        $request->setMethod('DELETE');

        $event = new ViewEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $request,
            HttpKernelInterface::MASTER_REQUEST,
            'foo'
        );

        $listener = new RespondListener();
        $listener->onKernelView($event);

        $response = $event->getResponse();
        $this->assertEquals('foo', $response->getContent());
        $this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->assertEquals('text/xml; charset=utf-8', $response->headers->get('Content-Type'));
        $this->assertEquals('Accept', $response->headers->get('Vary'));
        $this->assertEquals('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertEquals('deny', $response->headers->get('X-Frame-Options'));
    }

    public function testSetSunsetHeader()
    {
        $event = new ViewEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            new Request([], [], ['_api_resource_class' => Dummy::class, '_api_item_operation_name' => 'get', '_api_respond' => true]),
            HttpKernelInterface::MASTER_REQUEST,
            'bar'
        );
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata(null, null, null, ['get' => ['sunset' => 'tomorrow']]));

        $listener = new RespondListener($resourceMetadataFactoryProphecy->reveal());
        $listener->onKernelView($event);

        $response = $event->getResponse();
        /** @var string $value */
        $value = $response->headers->get('Sunset');
        $this->assertEquals(new \DateTimeImmutable('tomorrow'), \DateTime::createFromFormat(\DATE_RFC1123, $value));
    }

    public function testSetCustomStatus()
    {
        $event = new ViewEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            new Request([], [], ['_api_resource_class' => Dummy::class, '_api_item_operation_name' => 'get', '_api_respond' => true]),
            HttpKernelInterface::MASTER_REQUEST,
            'bar'
        );
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata(null, null, null, ['get' => ['status' => Response::HTTP_ACCEPTED]]));

        $listener = new RespondListener($resourceMetadataFactoryProphecy->reveal());
        $listener->onKernelView($event);

        $this->assertSame(Response::HTTP_ACCEPTED, $event->getResponse()->getStatusCode());
    }

    public function testHandleResponse()
    {
        $listener = new RespondListener();

        $response = new Response();
        $event = new ViewEvent(
          $this->prophesize(HttpKernelInterface::class)->reveal(),
            new Request([], [], ['_api_resource_class' => Dummy::class, '_api_item_operation_name' => 'get', '_api_respond' => true]),
            HttpKernelInterface::MASTER_REQUEST,
            $response
        );
        $listener->onKernelView($event);

        $this->assertSame($response, $event->getResponse());
    }
}
