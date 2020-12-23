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
use ApiPlatform\Core\Tests\ProphecyTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class AddHeadersListenerTest extends TestCase
{
    use ProphecyTrait;

    public function testDoNotSetHeaderWhenMethodNotCacheable()
    {
        $request = new Request([], [], ['_api_resource_class' => Dummy::class, '_api_item_operation_name' => 'get']);
        $request->setMethod('PUT');
        $response = new Response();
        $event = new ResponseEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $request,
            HttpKernelInterface::MASTER_REQUEST,
            $response
        );

        $listener = new AddHeadersListener(true);
        $listener->onKernelResponse($event);

        $this->assertNull($response->getEtag());
    }

    public function testDoNotSetHeaderOnUnsuccessfulResponse()
    {
        $request = new Request([], [], ['_api_resource_class' => Dummy::class, '_api_item_operation_name' => 'get']);
        $response = new Response('{}', Response::HTTP_BAD_REQUEST);
        $event = new ResponseEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $request,
            HttpKernelInterface::MASTER_REQUEST,
            $response
        );

        $listener = new AddHeadersListener(true);
        $listener->onKernelResponse($event);

        $this->assertNull($response->getEtag());
    }

    public function testDoNotSetHeaderWhenNotAnApiOperation()
    {
        $response = new Response();
        $event = new ResponseEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            new Request(),
            HttpKernelInterface::MASTER_REQUEST,
            $response
        );

        $listener = new AddHeadersListener(true);
        $listener->onKernelResponse($event);

        $this->assertNull($response->getEtag());
    }

    public function testDoNotSetHeaderWhenNoContent()
    {
        $response = new Response();
        $event = new ResponseEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            new Request([], [], ['_api_resource_class' => Dummy::class, '_api_item_operation_name' => 'get']),
            HttpKernelInterface::MASTER_REQUEST,
            $response
        );
        $listener = new AddHeadersListener(true);
        $listener->onKernelResponse($event);

        $this->assertNull($response->getEtag());
    }

    public function testAddHeaders()
    {
        $response = new Response('some content', 200, ['Vary' => ['Accept', 'Cookie']]);
        $event = new ResponseEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            new Request([], [], ['_api_resource_class' => Dummy::class, '_api_item_operation_name' => 'get']),
            HttpKernelInterface::MASTER_REQUEST,
            $response
        );

        $factory = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $factory->create(Dummy::class)->willReturn(new ResourceMetadata())->shouldBeCalled();

        $listener = new AddHeadersListener(true, 100, 200, ['Accept', 'Accept-Encoding'], true, $factory->reveal(), 15, 30);
        $listener->onKernelResponse($event);

        $this->assertSame('"9893532233caff98cd083a116b013c0b"', $response->getEtag());
        $this->assertSame('max-age=100, public, s-maxage=200, stale-if-error=30, stale-while-revalidate=15', $response->headers->get('Cache-Control'));
        $this->assertSame(['Accept', 'Cookie', 'Accept-Encoding'], $response->getVary());
    }

    public function testDoNotSetHeaderWhenAlreadySet()
    {
        $response = new Response('some content', 200, ['Vary' => ['Accept', 'Cookie']]);
        $response->setEtag('etag');
        $response->setMaxAge(300);
        // This also calls setPublic
        $response->setSharedMaxAge(400);

        $event = new ResponseEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            new Request([], [], ['_api_resource_class' => Dummy::class, '_api_item_operation_name' => 'get']),
            HttpKernelInterface::MASTER_REQUEST,
            $response
        );

        $factory = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $factory->create(Dummy::class)->willReturn(new ResourceMetadata())->shouldBeCalled();

        $listener = new AddHeadersListener(true, 100, 200, ['Accept', 'Accept-Encoding'], true, $factory->reveal(), 15, 30);
        $listener->onKernelResponse($event);

        $this->assertSame('"etag"', $response->getEtag());
        $this->assertSame('max-age=300, public, s-maxage=400, stale-if-error=30, stale-while-revalidate=15', $response->headers->get('Cache-Control'));
        $this->assertSame(['Accept', 'Cookie', 'Accept-Encoding'], $response->getVary());
    }

    public function testSetHeadersFromResourceMetadata()
    {
        $response = new Response('some content', 200, ['Vary' => ['Accept', 'Cookie']]);
        $event = new ResponseEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            new Request([], [], ['_api_resource_class' => Dummy::class, '_api_item_operation_name' => 'get']),
            HttpKernelInterface::MASTER_REQUEST,
            $response
        );

        $metadata = new ResourceMetadata(null, null, null, null, null, ['cache_headers' => ['max_age' => 123, 'shared_max_age' => 456, 'stale_while_revalidate' => 928, 'stale_if_error' => 70,  'vary' => ['Vary-1', 'Vary-2']]]);
        $factory = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $factory->create(Dummy::class)->willReturn($metadata)->shouldBeCalled();

        $listener = new AddHeadersListener(true, 100, 200, ['Accept', 'Accept-Encoding'], true, $factory->reveal(), 15, 30);
        $listener->onKernelResponse($event);

        $this->assertSame('max-age=123, public, s-maxage=456, stale-if-error=70, stale-while-revalidate=928', $response->headers->get('Cache-Control'));
        $this->assertSame(['Vary-1', 'Vary-2'], $response->getVary());
    }

    public function testSetHeadersFromResourceMetadataMarkedAsPrivate()
    {
        $response = new Response('some content', 200);
        $event = new ResponseEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            new Request([], [], ['_api_resource_class' => Dummy::class, '_api_item_operation_name' => 'get']),
            HttpKernelInterface::MASTER_REQUEST,
            $response
        );

        $metadata = new ResourceMetadata(null, null, null, null, null, [
            'cache_headers' => [
                'max_age' => 123,
                'public' => false,
                'shared_max_age' => 456,
            ],
        ]);
        $factory = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $factory->create(Dummy::class)->willReturn($metadata)->shouldBeCalled();

        $listener = new AddHeadersListener(true, 100, 200, [], true, $factory->reveal());
        $listener->onKernelResponse($event);

        $this->assertSame('max-age=123, private', $response->headers->get('Cache-Control'));

        // resource's cache marked as private must not contain s-maxage
        $this->assertStringNotContainsString('s-maxage', $response->headers->get('Cache-Control'));
    }

    public function testSetHeadersFromResourceMetadataMarkedAsPublic()
    {
        $response = new Response('some content', 200);
        $event = new ResponseEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            new Request([], [], ['_api_resource_class' => Dummy::class, '_api_item_operation_name' => 'get']),
            HttpKernelInterface::MASTER_REQUEST,
            $response
        );

        $metadata = new ResourceMetadata(null, null, null, null, null, [
            'cache_headers' => [
                'max_age' => 123,
                'public' => true,
                'shared_max_age' => 456,
            ],
        ]);
        $factory = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $factory->create(Dummy::class)->willReturn($metadata)->shouldBeCalled();

        $listener = new AddHeadersListener(true, 100, 200, [], true, $factory->reveal());
        $listener->onKernelResponse($event);

        $this->assertSame('max-age=123, public, s-maxage=456', $response->headers->get('Cache-Control'));
    }

    public function testSetHeadersFromResourceMetadataWithNoPrivacy()
    {
        $response = new Response('some content', 200);
        $event = new ResponseEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            new Request([], [], ['_api_resource_class' => Dummy::class, '_api_item_operation_name' => 'get']),
            HttpKernelInterface::MASTER_REQUEST,
            $response
        );

        $metadata = new ResourceMetadata(null, null, null, null, null, [
            'cache_headers' => [
                'max_age' => 123,
                'shared_max_age' => 456,
            ],
        ]);
        $factory = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $factory->create(Dummy::class)->willReturn($metadata)->shouldBeCalled();

        $listener = new AddHeadersListener(true, 100, 200, [], true, $factory->reveal());
        $listener->onKernelResponse($event);

        $this->assertSame('max-age=123, public, s-maxage=456', $response->headers->get('Cache-Control'));
    }

    public function testSetHeadersFromResourceMetadataWithNoPrivacyDefaultsPrivate()
    {
        $response = new Response('some content', 200);
        $event = new ResponseEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            new Request([], [], ['_api_resource_class' => Dummy::class, '_api_item_operation_name' => 'get']),
            HttpKernelInterface::MASTER_REQUEST,
            $response
        );

        $metadata = new ResourceMetadata(null, null, null, null, null, [
            'cache_headers' => [
                'max_age' => 123,
                'shared_max_age' => 456,
            ],
        ]);
        $factory = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $factory->create(Dummy::class)->willReturn($metadata)->shouldBeCalled();

        $listener = new AddHeadersListener(true, 100, 200, ['Accept', 'Accept-Encoding'], false, $factory->reveal());
        $listener->onKernelResponse($event);

        $this->assertSame('max-age=123, private', $response->headers->get('Cache-Control'));

        // resource's cache marked as private must not contain s-maxage
        $this->assertStringNotContainsString('s-maxage', $response->headers->get('Cache-Control'));
    }
}
