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

use ApiPlatform\Core\DataProvider\CollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\SubresourceDataProviderInterface;
use ApiPlatform\Core\EventListener\ReadListener;
use ApiPlatform\Core\Exception\InvalidIdentifierException;
use ApiPlatform\Core\Identifier\Normalizer\ChainIdentifierDenormalizer;
use ApiPlatform\Core\Exception\RuntimeException;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ReadListenerTest extends TestCase
{
    public function testNotAnApiPlatformRequest()
    {
        $identifierDenormalizer = $this->prophesize(ChainIdentifierDenormalizer::class);

        $collectionDataProvider = $this->prophesize(CollectionDataProviderInterface::class);
        $collectionDataProvider->getCollection()->shouldNotBeCalled();

        $itemDataProvider = $this->prophesize(ItemDataProviderInterface::class);
        $itemDataProvider->getItem()->shouldNotBeCalled();

        $subresourceDataProvider = $this->prophesize(SubresourceDataProviderInterface::class);
        $subresourceDataProvider->getSubresource()->shouldNotBeCalled();

        $event = $this->prophesize(GetResponseEvent::class);
        $event->getRequest()->willReturn(new Request())->shouldBeCalled();

        $listener = new ReadListener($collectionDataProvider->reveal(), $itemDataProvider->reveal(), $subresourceDataProvider->reveal(), null, $identifierDenormalizer->reveal());
        $listener->onKernelRequest($event->reveal());
    }

    /**
     * @group legacy
     */
    public function testLegacyConstructor()
    {
        $collectionDataProvider = $this->prophesize(CollectionDataProviderInterface::class);
        $collectionDataProvider->getCollection()->shouldNotBeCalled();

        $itemDataProvider = $this->prophesize(ItemDataProviderInterface::class);
        $itemDataProvider->getItem()->shouldNotBeCalled();

        $subresourceDataProvider = $this->prophesize(SubresourceDataProviderInterface::class);
        $subresourceDataProvider->getSubresource()->shouldNotBeCalled();

        $event = $this->prophesize(GetResponseEvent::class);
        $event->getRequest()->willReturn(new Request())->shouldBeCalled();

        $listener = new ReadListener($collectionDataProvider->reveal(), $itemDataProvider->reveal(), $subresourceDataProvider->reveal());
        $listener->onKernelRequest($event->reveal());
    }

    public function testDoNotCallWhenReceiveFlagIsFalse()
    {
        $identifierDenormalizer = $this->prophesize(ChainIdentifierDenormalizer::class);

        $collectionDataProvider = $this->prophesize(CollectionDataProviderInterface::class);
        $collectionDataProvider->getCollection()->shouldNotBeCalled();

        $itemDataProvider = $this->prophesize(ItemDataProviderInterface::class);
        $itemDataProvider->getItem()->shouldNotBeCalled();

        $subresourceDataProvider = $this->prophesize(SubresourceDataProviderInterface::class);
        $subresourceDataProvider->getSubresource()->shouldNotBeCalled();

        $request = new Request([], [], ['data' => new \stdClass(), '_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'post', '_api_receive' => false]);
        $request->setMethod('PUT');

        $event = $this->prophesize(GetResponseEvent::class);
        $event->getRequest()->willReturn($request)->shouldBeCalled();

        $listener = new ReadListener($collectionDataProvider->reveal(), $itemDataProvider->reveal(), $subresourceDataProvider->reveal(), null, $identifierDenormalizer->reveal());
        $listener->onKernelRequest($event->reveal());
    }

    public function testRetrieveCollectionPost()
    {
        $identifierDenormalizer = $this->prophesize(ChainIdentifierDenormalizer::class);

        $collectionDataProvider = $this->prophesize(CollectionDataProviderInterface::class);
        $collectionDataProvider->getCollection()->shouldNotBeCalled();

        $itemDataProvider = $this->prophesize(ItemDataProviderInterface::class);
        $itemDataProvider->getItem()->shouldNotBeCalled();

        $subresourceDataProvider = $this->prophesize(SubresourceDataProviderInterface::class);
        $subresourceDataProvider->getSubresource()->shouldNotBeCalled();

        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'post', '_api_format' => 'json', '_api_mime_type' => 'application/json'], [], [], [], '{}');
        $request->setMethod('POST');

        $event = $this->prophesize(GetResponseEvent::class);
        $event->getRequest()->willReturn($request)->shouldBeCalled();

        $listener = new ReadListener($collectionDataProvider->reveal(), $itemDataProvider->reveal(), $subresourceDataProvider->reveal(), null, $identifierDenormalizer->reveal());
        $listener->onKernelRequest($event->reveal());

        $this->assertTrue($request->attributes->has('data'));
        $this->assertNull($request->attributes->get('data'));
    }

    public function testRetrieveCollectionGet()
    {
        $identifierDenormalizer = $this->prophesize(ChainIdentifierDenormalizer::class);

        $collectionDataProvider = $this->prophesize(CollectionDataProviderInterface::class);
        $collectionDataProvider->getCollection('Foo', 'get', ['filters' => ['foo' => 'bar']])->willReturn([])->shouldBeCalled();

        $itemDataProvider = $this->prophesize(ItemDataProviderInterface::class);
        $itemDataProvider->getItem()->shouldNotBeCalled();

        $subresourceDataProvider = $this->prophesize(SubresourceDataProviderInterface::class);
        $subresourceDataProvider->getSubresource()->shouldNotBeCalled();

        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'get', '_api_format' => 'json', '_api_mime_type' => 'application/json'], [], [], ['QUERY_STRING' => 'foo=bar']);
        $request->setMethod('GET');

        $event = $this->prophesize(GetResponseEvent::class);
        $event->getRequest()->willReturn($request)->shouldBeCalled();

        $listener = new ReadListener($collectionDataProvider->reveal(), $itemDataProvider->reveal(), $subresourceDataProvider->reveal(), null, $identifierDenormalizer->reveal());
        $listener->onKernelRequest($event->reveal());

        $this->assertSame([], $request->attributes->get('data'));
    }

    public function testRetrieveItem()
    {
        $identifierDenormalizer = $this->prophesize(ChainIdentifierDenormalizer::class);
        $identifierDenormalizer->denormalize('1', 'Foo')->shouldBeCalled()->willReturn(['id' => '1']);

        $collectionDataProvider = $this->prophesize(CollectionDataProviderInterface::class);
        $collectionDataProvider->getCollection()->shouldNotBeCalled();

        $data = new \stdClass();
        $itemDataProvider = $this->prophesize(ItemDataProviderInterface::class);
        $itemDataProvider->getItem('Foo', ['id' => '1'], 'get', [ChainIdentifierDenormalizer::HAS_IDENTIFIER_DENORMALIZER => true])->willReturn($data)->shouldBeCalled();

        $subresourceDataProvider = $this->prophesize(SubresourceDataProviderInterface::class);
        $subresourceDataProvider->getSubresource()->shouldNotBeCalled();

        $request = new Request([], [], ['id' => 1, '_api_resource_class' => 'Foo', '_api_item_operation_name' => 'get', '_api_format' => 'json', '_api_mime_type' => 'application/json']);
        $request->setMethod('GET');

        $event = $this->prophesize(GetResponseEvent::class);
        $event->getRequest()->willReturn($request)->shouldBeCalled();

        $listener = new ReadListener($collectionDataProvider->reveal(), $itemDataProvider->reveal(), $subresourceDataProvider->reveal(), null, $identifierDenormalizer->reveal());
        $listener->onKernelRequest($event->reveal());

        $this->assertSame($data, $request->attributes->get('data'));
    }

    public function testRetrieveItemNoIdentifier()
    {
        $this->expectException(NotFoundHttpException::class);

        $collectionDataProvider = $this->prophesize(CollectionDataProviderInterface::class);
        $collectionDataProvider->getCollection()->shouldNotBeCalled();

        $itemDataProvider = $this->prophesize(ItemDataProviderInterface::class);
        $itemDataProvider->getItem()->shouldNotBeCalled();

        $subresourceDataProvider = $this->prophesize(SubresourceDataProviderInterface::class);
        $subresourceDataProvider->getSubresource()->shouldNotBeCalled();

        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_item_operation_name' => 'get', '_api_format' => 'json', '_api_mime_type' => 'application/json']);
        $request->setMethod('GET');

        $event = $this->prophesize(GetResponseEvent::class);
        $event->getRequest()->willReturn($request)->shouldBeCalled();

        $listener = new ReadListener($collectionDataProvider->reveal(), $itemDataProvider->reveal(), $subresourceDataProvider->reveal());
        $listener->onKernelRequest($event->reveal());

        $request->attributes->get('data');
    }

    public function testRetrieveSubresource()
    {
        $identifierDenormalizer = $this->prophesize(ChainIdentifierDenormalizer::class);
        $identifierDenormalizer->denormalize('1', 'Bar')->shouldBeCalled()->willReturn(['id' => '1']);

        $collectionDataProvider = $this->prophesize(CollectionDataProviderInterface::class);
        $collectionDataProvider->getCollection()->shouldNotBeCalled();

        $itemDataProvider = $this->prophesize(ItemDataProviderInterface::class);
        $itemDataProvider->getItem()->shouldNotBeCalled();

        $data = [new \stdClass()];
        $subresourceDataProvider = $this->prophesize(SubresourceDataProviderInterface::class);
        $subresourceDataProvider->getSubresource('Foo', ['id' => ['id' => '1']], ['identifiers' => [['id', 'Bar', true]], 'property' => 'bar', ChainIdentifierDenormalizer::HAS_IDENTIFIER_DENORMALIZER => true], 'get')->willReturn($data)->shouldBeCalled();

        $request = new Request([], [], ['id' => 1, '_api_resource_class' => 'Foo', '_api_subresource_operation_name' => 'get', '_api_format' => 'json', '_api_mime_type' => 'application/json', '_api_subresource_context' => ['identifiers' => [['id', 'Bar', true]], 'property' => 'bar']]);
        $request->setMethod('GET');

        $event = $this->prophesize(GetResponseEvent::class);
        $event->getRequest()->willReturn($request)->shouldBeCalled();

        $listener = new ReadListener($collectionDataProvider->reveal(), $itemDataProvider->reveal(), $subresourceDataProvider->reveal(), null, $identifierDenormalizer->reveal());
        $listener->onKernelRequest($event->reveal());

        $this->assertSame($data, $request->attributes->get('data'));
    }

    public function testRetrieveSubresourceNoDataProvider()
    {
        $this->expectException(RuntimeException::class);

        $collectionDataProvider = $this->prophesize(CollectionDataProviderInterface::class);
        $collectionDataProvider->getCollection()->shouldNotBeCalled();

        $itemDataProvider = $this->prophesize(ItemDataProviderInterface::class);
        $itemDataProvider->getItem()->shouldNotBeCalled();

        $request = new Request([], [], ['id' => 1, '_api_resource_class' => 'Foo', '_api_subresource_operation_name' => 'get', '_api_format' => 'json', '_api_mime_type' => 'application/json', '_api_subresource_context' => ['identifiers' => [['id', 'Bar', true]], 'property' => 'bar']]);
        $request->setMethod('GET');

        $event = $this->prophesize(GetResponseEvent::class);
        $event->getRequest()->willReturn($request)->shouldBeCalled();

        $listener = new ReadListener($collectionDataProvider->reveal(), $itemDataProvider->reveal());
        $listener->onKernelRequest($event->reveal());

        $request->attributes->get('data');
    }

    public function testRetrieveSubresourceNotFound()
    {
        $identifierDenormalizer = $this->prophesize(ChainIdentifierDenormalizer::class);
        $identifierDenormalizer->denormalize('1', 'Bar')->willThrow(new InvalidIdentifierException())->shouldBeCalled();
        $this->expectException(NotFoundHttpException::class);

        $collectionDataProvider = $this->prophesize(CollectionDataProviderInterface::class);
        $collectionDataProvider->getCollection()->shouldNotBeCalled();

        $itemDataProvider = $this->prophesize(ItemDataProviderInterface::class);
        $itemDataProvider->getItem()->shouldNotBeCalled();

        $request = new Request([], [], ['id' => 1, '_api_resource_class' => 'Foo', '_api_subresource_operation_name' => 'get', '_api_format' => 'json', '_api_mime_type' => 'application/json', '_api_subresource_context' => ['identifiers' => [['id', 'Bar', true]], 'property' => 'bar']]);
        $request->setMethod('GET');

        $event = $this->prophesize(GetResponseEvent::class);
        $event->getRequest()->willReturn($request)->shouldBeCalled();

        $listener = new ReadListener($collectionDataProvider->reveal(), $itemDataProvider->reveal(), $this->prophesize(SubresourceDataProviderInterface::class)->reveal(), null, $identifierDenormalizer->reveal());
        $listener->onKernelRequest($event->reveal());
    }

    public function testRetrieveItemNotFound()
    {
        $identifierDenormalizer = $this->prophesize(ChainIdentifierDenormalizer::class);
        $identifierDenormalizer->denormalize('22', 'Foo')->shouldBeCalled()->willReturn(['id' => 22]);
        $this->expectException(NotFoundHttpException::class);

        $collectionDataProvider = $this->prophesize(CollectionDataProviderInterface::class);

        $itemDataProvider = $this->prophesize(ItemDataProviderInterface::class);
        $itemDataProvider->getItem('Foo', ['id' => 22], 'get', [ChainIdentifierDenormalizer::HAS_IDENTIFIER_DENORMALIZER => true])->willReturn(null)->shouldBeCalled();

        $subresourceDataProvider = $this->prophesize(SubresourceDataProviderInterface::class);

        $request = new Request([], [], ['id' => 22, '_api_resource_class' => 'Foo', '_api_item_operation_name' => 'get', '_api_format' => 'json', '_api_mime_type' => 'application/json']);
        $request->setMethod('GET');

        $event = $this->prophesize(GetResponseEvent::class);
        $event->getRequest()->willReturn($request)->shouldBeCalled();

        $listener = new ReadListener($collectionDataProvider->reveal(), $itemDataProvider->reveal(), $subresourceDataProvider->reveal(), null, $identifierDenormalizer->reveal());
        $listener->onKernelRequest($event->reveal());
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function testRetrieveBadItemNormalizedIdentifiers()
    {
        $identifierDenormalizer = $this->prophesize(ChainIdentifierDenormalizer::class);
        $identifierDenormalizer->denormalize('1', 'Foo')->shouldBeCalled()->willThrow(new InvalidIdentifierException());

        $collectionDataProvider = $this->prophesize(CollectionDataProviderInterface::class);
        $itemDataProvider = $this->prophesize(ItemDataProviderInterface::class);
        $subresourceDataProvider = $this->prophesize(SubresourceDataProviderInterface::class);

        $request = new Request([], [], ['id' => 1, '_api_resource_class' => 'Foo', '_api_item_operation_name' => 'get', '_api_format' => 'json', '_api_mime_type' => 'application/json']);
        $request->setMethod(Request::METHOD_GET);

        $event = $this->prophesize(GetResponseEvent::class);
        $event->getRequest()->willReturn($request)->shouldBeCalled();

        $listener = new ReadListener($collectionDataProvider->reveal(), $itemDataProvider->reveal(), $subresourceDataProvider->reveal(), null, $identifierDenormalizer->reveal());
        $listener->onKernelRequest($event->reveal());
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function testRetrieveBadSubresourceNormalizedIdentifiers()
    {
        $identifierDenormalizer = $this->prophesize(ChainIdentifierDenormalizer::class);
        $identifierDenormalizer->denormalize(Argument::type('string'), Argument::type('string'))->shouldBeCalled()->willThrow(new InvalidIdentifierException());

        $collectionDataProvider = $this->prophesize(CollectionDataProviderInterface::class);
        $collectionDataProvider->getCollection()->shouldNotBeCalled();

        $itemDataProvider = $this->prophesize(ItemDataProviderInterface::class);
        $itemDataProvider->getItem()->shouldNotBeCalled();

        $data = [new \stdClass()];
        $subresourceDataProvider = $this->prophesize(SubresourceDataProviderInterface::class);
        $subresourceDataProvider->getSubresource()->shouldNotBeCalled();

        $request = new Request([], [], ['id' => 1, '_api_resource_class' => 'Foo', '_api_subresource_operation_name' => 'get', '_api_format' => 'json', '_api_mime_type' => 'application/json', '_api_subresource_context' => ['identifiers' => [['id', 'Bar', true]], 'property' => 'bar']]);
        $request->setMethod(Request::METHOD_GET);

        $event = $this->prophesize(GetResponseEvent::class);
        $event->getRequest()->willReturn($request)->shouldBeCalled();

        $listener = new ReadListener($collectionDataProvider->reveal(), $itemDataProvider->reveal(), $subresourceDataProvider->reveal(), null, $identifierDenormalizer->reveal());
        $listener->onKernelRequest($event->reveal());
    }
}
