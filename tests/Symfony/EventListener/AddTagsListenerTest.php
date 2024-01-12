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

use ApiPlatform\HttpCache\PurgerInterface;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use ApiPlatform\Symfony\EventListener\AddTagsListener;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class AddTagsListenerTest extends TestCase
{
    use ProphecyTrait;

    public const DEFAULT_CACHE_TAG = 'Cache-Tags';

    public function testDoNotSetHeaderWhenMethodNotCacheable(): void
    {
        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $request = new Request([], [], ['_resources' => ['/foo', '/bar'], '_api_resource_class' => Dummy::class, '_api_operation_name' => 'get']);
        $request->setMethod('PUT');

        $response = new Response();
        $response->setPublic();
        $response->setEtag('foo');

        $event = new ResponseEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $request,
            \defined(HttpKernelInterface::class.'::MAIN_REQUEST') ? HttpKernelInterface::MAIN_REQUEST : HttpKernelInterface::MASTER_REQUEST,
            $response
        );

        $listener = new AddTagsListener($iriConverterProphecy->reveal());
        $listener->onKernelResponse($event);

        $this->assertFalse($response->headers->has(self::DEFAULT_CACHE_TAG));
    }

    public function testDoNotSetHeaderWhenResponseNotCacheable(): void
    {
        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $request = new Request([], [], ['_resources' => ['/foo', '/bar'], '_api_resource_class' => Dummy::class, '_api_operation_name' => 'get']);
        $response = new Response();
        $event = new ResponseEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $request,
            \defined(HttpKernelInterface::class.'::MAIN_REQUEST') ? HttpKernelInterface::MAIN_REQUEST : HttpKernelInterface::MASTER_REQUEST,
            $response
        );

        $listener = new AddTagsListener($iriConverterProphecy->reveal());
        $listener->onKernelResponse($event);

        $this->assertFalse($response->headers->has(self::DEFAULT_CACHE_TAG));
    }

    public function testDoNotSetHeaderWhenNotAnApiOperation(): void
    {
        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $response = new Response();
        $response->setPublic();
        $response->setEtag('foo');

        $event = new ResponseEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            new Request([], [], ['_resources' => ['/foo', '/bar']]),
            \defined(HttpKernelInterface::class.'::MAIN_REQUEST') ? HttpKernelInterface::MAIN_REQUEST : HttpKernelInterface::MASTER_REQUEST,
            $response
        );

        $listener = new AddTagsListener($iriConverterProphecy->reveal());
        $listener->onKernelResponse($event);

        $this->assertFalse($response->headers->has(self::DEFAULT_CACHE_TAG));
    }

    public function testDoNotSetHeaderWhenEmptyTagList(): void
    {
        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $response = new Response();
        $response->setPublic();
        $response->setEtag('foo');

        $event = new ResponseEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            new Request([], [], ['_resources' => [], '_api_resource_class' => Dummy::class, '_api_operation_name' => 'get']),
            \defined(HttpKernelInterface::class.'::MAIN_REQUEST') ? HttpKernelInterface::MAIN_REQUEST : HttpKernelInterface::MASTER_REQUEST,
            $response
        );

        $listener = new AddTagsListener($iriConverterProphecy->reveal());
        $listener->onKernelResponse($event);

        $this->assertFalse($response->headers->has(self::DEFAULT_CACHE_TAG));
    }

    public function testAddTags(): void
    {
        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $response = new Response();
        $response->setPublic();
        $response->setEtag('foo');

        $event = new ResponseEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            new Request([], [], ['_resources' => ['/foo', '/bar'], '_api_resource_class' => Dummy::class, '_api_operation_name' => 'get']),
            \defined(HttpKernelInterface::class.'::MAIN_REQUEST') ? HttpKernelInterface::MAIN_REQUEST : HttpKernelInterface::MASTER_REQUEST,
            $response
        );

        $listener = new AddTagsListener($iriConverterProphecy->reveal());
        $listener->onKernelResponse($event);

        $this->assertSame('/foo,/bar', $response->headers->get('Cache-Tags'));
    }

    public function testAddCollectionIri(): void
    {
        $operation = (new GetCollection());
        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromResource(Dummy::class, UrlGeneratorInterface::ABS_PATH, $operation, Argument::type('array'))->willReturn('/dummies')->shouldBeCalled();

        $response = new Response();
        $response->setPublic();
        $response->setEtag('foo');

        $event = new ResponseEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            new Request([], [], ['_resources' => ['/foo', '/bar'], '_api_resource_class' => Dummy::class, '_api_operation_name' => 'get', '_api_operation' => $operation]),
            \defined(HttpKernelInterface::class.'::MAIN_REQUEST') ? HttpKernelInterface::MAIN_REQUEST : HttpKernelInterface::MASTER_REQUEST,
            $response
        );

        $listener = new AddTagsListener($iriConverterProphecy->reveal());
        $listener->onKernelResponse($event);

        $this->assertSame('/foo,/bar,/dummies', $response->headers->get(self::DEFAULT_CACHE_TAG));
    }

    public function testAddCollectionIriWhenCollectionIsEmpty(): void
    {
        $operation = (new GetCollection());
        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromResource(Dummy::class, UrlGeneratorInterface::ABS_PATH, $operation, Argument::type('array'))->willReturn('/dummies')->shouldBeCalled();

        $response = new Response();
        $response->setPublic();
        $response->setEtag('foo');

        $event = new ResponseEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            new Request([], [], ['_resources' => [], '_api_resource_class' => Dummy::class, '_api_operation_name' => 'get', '_api_operation' => $operation]),
            \defined(HttpKernelInterface::class.'::MAIN_REQUEST') ? HttpKernelInterface::MAIN_REQUEST : HttpKernelInterface::MASTER_REQUEST,
            $response
        );

        $listener = new AddTagsListener($iriConverterProphecy->reveal());
        $listener->onKernelResponse($event);

        $this->assertSame('/dummies', $response->headers->get(self::DEFAULT_CACHE_TAG));
    }

    public function testAddTagsWithXKey(): void
    {
        $operation = (new GetCollection(name: 'get'));
        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromResource(Dummy::class, UrlGeneratorInterface::ABS_PATH, $operation, Argument::type('array'))->willReturn('/dummies')->shouldBeCalled();

        $resourceMetadataCollectionFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $dummyMetadata = new ResourceMetadataCollection(Dummy::class, [(new ApiResource())->withOperations(new Operations(['get' => $operation]))]);
        $resourceMetadataCollectionFactoryProphecy->create(Dummy::class)->willReturn($dummyMetadata);

        $response = new Response();
        $response->setPublic();
        $response->setEtag('foo');

        $event = new ResponseEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            new Request([], [], ['_resources' => ['/foo' => '/foo', '/bar' => '/bar'], '_api_resource_class' => Dummy::class, '_api_operation_name' => 'get']),
            \defined(HttpKernelInterface::class.'::MAIN_REQUEST') ? HttpKernelInterface::MAIN_REQUEST : HttpKernelInterface::MASTER_REQUEST,
            $response
        );

        $purgerProphecy = $this->prophesize(PurgerInterface::class);
        $purgerProphecy->getResponseHeaders(['/foo' => '/foo', '/bar' => '/bar', '/dummies' => '/dummies'])->willReturn(['xkey' => '/foo /bar /dummies']);

        $listener = new AddTagsListener($iriConverterProphecy->reveal(), $resourceMetadataCollectionFactoryProphecy->reveal(), $purgerProphecy->reveal());
        $listener->onKernelResponse($event);

        $this->assertSame('/foo /bar /dummies', $response->headers->get('xkey'));
    }

    public function testAddTagsWithoutHeader(): void
    {
        $operation = (new GetCollection(name: 'get'));
        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromResource(Dummy::class, UrlGeneratorInterface::ABS_PATH, $operation, Argument::type('array'))->willReturn('/dummies')->shouldBeCalled();

        $resourceMetadataCollectionFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $dummyMetadata = new ResourceMetadataCollection(Dummy::class, [(new ApiResource())->withOperations(new Operations(['get' => $operation]))]);
        $resourceMetadataCollectionFactoryProphecy->create(Dummy::class)->willReturn($dummyMetadata);

        $response = new Response();
        $response->setPublic();
        $response->setEtag('foo');

        $event = new ResponseEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            new Request([], [], ['_resources' => ['/foo' => '/foo', '/bar' => '/bar'], '_api_resource_class' => Dummy::class, '_api_operation_name' => 'get']),
            \defined(HttpKernelInterface::class.'::MAIN_REQUEST') ? HttpKernelInterface::MAIN_REQUEST : HttpKernelInterface::MASTER_REQUEST,
            $response
        );

        $purgerProphecy = $this->prophesize(PurgerInterface::class);
        $purgerProphecy->getResponseHeaders(['/foo' => '/foo', '/bar' => '/bar', '/dummies' => '/dummies'])->willReturn([]);

        $listener = new AddTagsListener($iriConverterProphecy->reveal(), $resourceMetadataCollectionFactoryProphecy->reveal(), $purgerProphecy->reveal());
        $listener->onKernelResponse($event);

        $this->assertNull($response->headers->get('xkey'));
    }

    public function testDummyHeaderTag(): void
    {
        $operation = (new GetCollection(name: 'get'));
        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromResource(Dummy::class, UrlGeneratorInterface::ABS_PATH, $operation, Argument::type('array'))->willReturn('/dummies')->shouldBeCalled();

        $resourceMetadataCollectionFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $dummyMetadata = new ResourceMetadataCollection(Dummy::class, [(new ApiResource())->withOperations(new Operations(['get' => $operation]))]);
        $resourceMetadataCollectionFactoryProphecy->create(Dummy::class)->willReturn($dummyMetadata);

        $response = new Response();
        $response->setPublic();
        $response->setEtag('foo');

        $event = new ResponseEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            new Request([], [], ['_resources' => ['/foo' => '/foo', '/bar' => '/bar'], '_api_resource_class' => Dummy::class, '_api_operation_name' => 'get']),
            \defined(HttpKernelInterface::class.'::MAIN_REQUEST') ? HttpKernelInterface::MAIN_REQUEST : HttpKernelInterface::MASTER_REQUEST,
            $response
        );

        $purgerProphecy = $this->prophesize(PurgerInterface::class);
        $purgerProphecy->getResponseHeaders(['/foo' => '/foo', '/bar' => '/bar', '/dummies' => '/dummies'])->willReturn(['Dummy-Header' => '/foo,/bar,/dummies']);

        $listener = new AddTagsListener($iriConverterProphecy->reveal(), $resourceMetadataCollectionFactoryProphecy->reveal(), $purgerProphecy->reveal());
        $listener->onKernelResponse($event);

        $this->assertNull($response->headers->get(self::DEFAULT_CACHE_TAG));
        $this->assertNull($response->headers->get('xkey'));
        $this->assertSame('/foo,/bar,/dummies', $response->headers->get('Dummy-Header'));
    }
}
