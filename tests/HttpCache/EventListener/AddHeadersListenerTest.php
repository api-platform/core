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

namespace ApiPlatform\Core\Tests\HttpCache\EventListener;

use ApiPlatform\Core\HttpCache\EventListener\AddHeadersListener;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class AddHeadersListenerTest extends TestCase
{
    public function testDoNotSetHeaderWhenMethodNotCacheable()
    {
        $request = new Request([], [], ['_api_resource_class' => Dummy::class, '_api_item_operation_name' => 'get']);
        $request->setMethod('PUT');

        $response = new Response();

        $event = $this->prophesize(FilterResponseEvent::class);
        $event->getRequest()->willReturn($request)->shouldBeCalled();
        $event->getResponse()->willReturn($response)->shouldNotBeCalled();

        $listener = new AddHeadersListener(true);
        $listener->onKernelResponse($event->reveal());

        $this->assertNull($response->getEtag());
    }

    public function testDoNotSetHeaderOnUnsuccessfulResponse()
    {
        $request = new Request([], [], ['_api_resource_class' => Dummy::class, '_api_item_operation_name' => 'get']);

        $response = new Response('{}', Response::HTTP_BAD_REQUEST);

        $event = $this->prophesize(FilterResponseEvent::class);
        $event->getRequest()->willReturn($request)->shouldBeCalled();
        $event->getResponse()->willReturn($response)->shouldBeCalled();

        $listener = new AddHeadersListener(true);
        $listener->onKernelResponse($event->reveal());

        $this->assertNull($response->getEtag());
    }

    public function testDoNotSetHeaderWhenNotAnApiOperation()
    {
        $request = new Request();
        $response = new Response();

        $event = $this->prophesize(FilterResponseEvent::class);
        $event->getRequest()->willReturn($request)->shouldBeCalled();
        $event->getResponse()->willReturn($response)->shouldNotBeCalled();

        $listener = new AddHeadersListener(true);
        $listener->onKernelResponse($event->reveal());

        $this->assertNull($response->getEtag());
    }

    public function testDoNotSetHeaderWhenNoContent()
    {
        $request = new Request([], [], ['_api_resource_class' => Dummy::class, '_api_item_operation_name' => 'get']);
        $response = new Response();

        $event = $this->prophesize(FilterResponseEvent::class);
        $event->getRequest()->willReturn($request)->shouldBeCalled();
        $event->getResponse()->willReturn($response)->shouldBeCalled();

        $listener = new AddHeadersListener(true);
        $listener->onKernelResponse($event->reveal());

        $this->assertNull($response->getEtag());
    }

    public function testAddHeaders()
    {
        $request = new Request([], [], ['_api_resource_class' => Dummy::class, '_api_item_operation_name' => 'get']);
        $response = new Response('some content', 200, ['Vary' => ['Accept', 'Cookie']]);

        $event = $this->prophesize(FilterResponseEvent::class);
        $event->getRequest()->willReturn($request)->shouldBeCalled();
        $event->getResponse()->willReturn($response)->shouldBeCalled();

        $factory = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $factory->create(Dummy::class)->willReturn(new ResourceMetadata())->shouldBeCalled();

        $listener = new AddHeadersListener(true, 100, 200, ['Accept', 'Accept-Encoding'], true, $factory->reveal());
        $listener->onKernelResponse($event->reveal());

        $this->assertSame('"9893532233caff98cd083a116b013c0b"', $response->getEtag());
        $this->assertSame('max-age=100, public, s-maxage=200', $response->headers->get('Cache-Control'));
        $this->assertSame(['Accept', 'Cookie', 'Accept-Encoding'], $response->getVary());
    }

    public function testDoNotSetHeaderWhenAlreadySet()
    {
        $request = new Request([], [], ['_api_resource_class' => Dummy::class, '_api_item_operation_name' => 'get']);
        $response = new Response('some content', 200, ['Vary' => ['Accept', 'Cookie']]);
        $response->setEtag('etag');
        $response->setMaxAge(300);
        // This also calls setPublic
        $response->setSharedMaxAge(400);

        $event = $this->prophesize(FilterResponseEvent::class);
        $event->getRequest()->willReturn($request)->shouldBeCalled();
        $event->getResponse()->willReturn($response)->shouldBeCalled();

        $factory = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $factory->create(Dummy::class)->willReturn(new ResourceMetadata())->shouldBeCalled();

        $listener = new AddHeadersListener(true, 100, 200, [], true, $factory->reveal());
        $listener->onKernelResponse($event->reveal());

        $this->assertSame('"etag"', $response->getEtag());
        $this->assertSame('max-age=300, public, s-maxage=400', $response->headers->get('Cache-Control'));
    }

    public function testSetHeadersFromResourceMetadata()
    {
        $request = new Request([], [], ['_api_resource_class' => Dummy::class, '_api_item_operation_name' => 'get']);
        $response = new Response('some content', 200, ['Vary' => ['Accept', 'Cookie']]);

        $event = $this->prophesize(FilterResponseEvent::class);
        $event->getRequest()->willReturn($request)->shouldBeCalled();
        $event->getResponse()->willReturn($response)->shouldBeCalled();

        $metadata = new ResourceMetadata(null, null, null, null, null, ['cache_headers' => ['max_age' => 123, 'shared_max_age' => 456]]);
        $factory = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $factory->create(Dummy::class)->willReturn($metadata)->shouldBeCalled();

        $listener = new AddHeadersListener(true, 100, 200, ['Accept', 'Accept-Encoding'], true, $factory->reveal());
        $listener->onKernelResponse($event->reveal());

        $this->assertSame('max-age=123, public, s-maxage=456', $response->headers->get('Cache-Control'));
    }
}
