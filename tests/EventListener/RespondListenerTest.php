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

use ApiPlatform\Core\Event\EventInterface;
use ApiPlatform\Core\Event\RespondEvent;
use ApiPlatform\Core\EventListener\RespondListener;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class RespondListenerTest extends TestCase
{
    public function testDoNotHandleResponse()
    {
        $request = new Request();

        $eventProphecy = $this->prophesize(EventInterface::class);
        $eventProphecy->getData()->willReturn(new Response());
        $eventProphecy->getContext()->willReturn(['request' => $request]);
        $eventProphecy->setData(Argument::any())->shouldNotBeCalled();

        $listener = new RespondListener();
        $listener->handleEvent($eventProphecy->reveal());
    }

    public function testDoNotHandleWhenRespondFlagIsFalse()
    {
        $request = new Request([], [], ['_api_respond' => false]);

        $eventProphecy = $this->prophesize(EventInterface::class);
        $eventProphecy->getData()->willReturn('foo');
        $eventProphecy->getContext()->willReturn(['request' => $request]);
        $eventProphecy->setData(Argument::any())->shouldNotBeCalled();

        $listener = new RespondListener();
        $listener->handleEvent($eventProphecy->reveal());
    }

    public function testCreate200Response()
    {
        $request = new Request([], [], ['_api_respond' => true]);
        $request->setRequestFormat('xml');

        $event = new RespondEvent('foo', ['request' => $request]);

        $listener = new RespondListener();
        $listener->handleEvent($event);

        $response = $event->getContext()['response'];
        $this->assertEquals('foo', $response->getContent());
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals('text/xml; charset=utf-8', $response->headers->get('Content-Type'));
        $this->assertEquals('Accept', $response->headers->get('Vary'));
        $this->assertEquals('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertEquals('deny', $response->headers->get('X-Frame-Options'));
    }

    public function testCreate201Response()
    {
        $request = new Request([], [], ['_api_respond' => true, '_api_write_item_iri' => '/dummy_entities/1']);
        $request->setMethod('POST');
        $request->setRequestFormat('xml');

        $event = new RespondEvent('foo', ['request' => $request]);

        $listener = new RespondListener();
        $listener->handleEvent($event);

        $response = $event->getContext()['response'];
        $this->assertEquals('foo', $response->getContent());
        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
        $this->assertEquals('text/xml; charset=utf-8', $response->headers->get('Content-Type'));
        $this->assertEquals('Accept', $response->headers->get('Vary'));
        $this->assertEquals('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertEquals('deny', $response->headers->get('X-Frame-Options'));
        $this->assertEquals('/dummy_entities/1', $response->headers->get('Location'));
        $this->assertEquals('/dummy_entities/1', $response->headers->get('Content-Location'));
    }

    public function testCreate204Response()
    {
        $request = new Request([], [], ['_api_respond' => true]);
        $request->setRequestFormat('xml');
        $request->setMethod('DELETE');

        $event = new RespondEvent('foo', ['request' => $request]);

        $listener = new RespondListener();
        $listener->handleEvent($event);

        $response = $event->getContext()['response'];
        $this->assertEquals('foo', $response->getContent());
        $this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->assertEquals('text/xml; charset=utf-8', $response->headers->get('Content-Type'));
        $this->assertEquals('Accept', $response->headers->get('Vary'));
        $this->assertEquals('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertEquals('deny', $response->headers->get('X-Frame-Options'));
    }

    public function testSetSunsetHeader()
    {
        $request = new Request([], [], ['_api_resource_class' => Dummy::class, '_api_item_operation_name' => 'get', '_api_respond' => true]);

        $event = new RespondEvent('bar', ['request' => $request]);

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata(null, null, null, ['get' => ['sunset' => 'tomorrow']]));

        $listener = new RespondListener($resourceMetadataFactoryProphecy->reveal());
        $listener->handleEvent($event);

        $response = $event->getContext()['response'];
        /** @var string $value */
        $value = $response->headers->get('Sunset');
        $this->assertEquals(new \DateTimeImmutable('tomorrow'), \DateTime::createFromFormat(DATE_RFC1123, $value));
    }

    public function testSetCustomStatus()
    {
        $request = new Request([], [], ['_api_resource_class' => Dummy::class, '_api_item_operation_name' => 'get', '_api_respond' => true]);

        $event = new RespondEvent('bar', ['request' => $request]);

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata(null, null, null, ['get' => ['status' => Response::HTTP_ACCEPTED]]));

        $listener = new RespondListener($resourceMetadataFactoryProphecy->reveal());
        $listener->handleEvent($event);

        $this->assertSame(Response::HTTP_ACCEPTED, $event->getContext()['response']->getStatusCode());
    }

    public function testHandleResponse()
    {
        $request = new Request([], [], ['_api_resource_class' => Dummy::class, '_api_item_operation_name' => 'get', '_api_respond' => true]);
        $response = new Response();
        $eventProphecy = $this->prophesize(EventInterface::class);

        $eventProphecy->getContext()->willReturn(['request' => $request])->shouldBeCalled();
        $eventProphecy->getData()->willReturn($response);
        $eventProphecy->setContext(['request' => $request, 'response' => $response])->shouldBeCalled();

        $listener = new RespondListener();
        $listener->handleEvent($eventProphecy->reveal());
    }

    /**
     * @group legacy
     *
     * @expectedDeprecation The method ApiPlatform\Core\EventListener\RespondListener::onKernelView() is deprecated since 2.5 and will be removed in 3.0.
     * @expectedDeprecation Passing an instance of "Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent" as argument of "ApiPlatform\Core\EventListener\RespondListener::handleEvent" is deprecated since 2.5 and will not be possible anymore in 3.0. Pass an instance of "ApiPlatform\Core\Event\EventInterface" instead.
     */
    public function testLegacyOnKernelView()
    {
        $request = new Request([], [], ['_api_respond' => true]);
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
    }
}
