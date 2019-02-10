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
use ApiPlatform\Core\Event\EventInterface;
use ApiPlatform\Core\EventListener\ReadListener;
use ApiPlatform\Core\Exception\InvalidIdentifierException;
use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Identifier\IdentifierConverterInterface;
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
        $identifierConverter = $this->prophesize(IdentifierConverterInterface::class);

        $collectionDataProvider = $this->prophesize(CollectionDataProviderInterface::class);
        $collectionDataProvider->getCollection()->shouldNotBeCalled();

        $itemDataProvider = $this->prophesize(ItemDataProviderInterface::class);
        $itemDataProvider->getItem()->shouldNotBeCalled();

        $subresourceDataProvider = $this->prophesize(SubresourceDataProviderInterface::class);
        $subresourceDataProvider->getSubresource()->shouldNotBeCalled();

        $eventProphecy = $this->prophesize(EventInterface::class);
        $eventProphecy->getContext()->willReturn(['request' => new Request()]);

        $listener = new ReadListener($collectionDataProvider->reveal(), $itemDataProvider->reveal(), $subresourceDataProvider->reveal(), null, $identifierConverter->reveal());
        $listener->handleEvent($eventProphecy->reveal());
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

        $eventProphecy = $this->prophesize(EventInterface::class);
        $eventProphecy->getContext()->willReturn(['request' => new Request()]);

        $listener = new ReadListener($collectionDataProvider->reveal(), $itemDataProvider->reveal(), $subresourceDataProvider->reveal());
        $listener->handleEvent($eventProphecy->reveal());
    }

    public function testDoNotCallWhenReceiveFlagIsFalse()
    {
        $identifierConverter = $this->prophesize(IdentifierConverterInterface::class);

        $collectionDataProvider = $this->prophesize(CollectionDataProviderInterface::class);
        $collectionDataProvider->getCollection()->shouldNotBeCalled();

        $itemDataProvider = $this->prophesize(ItemDataProviderInterface::class);
        $itemDataProvider->getItem()->shouldNotBeCalled();

        $subresourceDataProvider = $this->prophesize(SubresourceDataProviderInterface::class);
        $subresourceDataProvider->getSubresource()->shouldNotBeCalled();

        $request = new Request([], [], ['data' => new \stdClass(), '_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'post', '_api_receive' => false]);
        $request->setMethod('PUT');

        $eventProphecy = $this->prophesize(EventInterface::class);
        $eventProphecy->getContext()->willReturn(['request' => $request]);

        $listener = new ReadListener($collectionDataProvider->reveal(), $itemDataProvider->reveal(), $subresourceDataProvider->reveal(), null, $identifierConverter->reveal());
        $listener->handleEvent($eventProphecy->reveal());
    }

    public function testRetrieveCollectionPost()
    {
        $identifierConverter = $this->prophesize(IdentifierConverterInterface::class);

        $collectionDataProvider = $this->prophesize(CollectionDataProviderInterface::class);
        $collectionDataProvider->getCollection()->shouldNotBeCalled();

        $itemDataProvider = $this->prophesize(ItemDataProviderInterface::class);
        $itemDataProvider->getItem()->shouldNotBeCalled();

        $subresourceDataProvider = $this->prophesize(SubresourceDataProviderInterface::class);
        $subresourceDataProvider->getSubresource()->shouldNotBeCalled();

        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'post', '_api_format' => 'json', '_api_mime_type' => 'application/json'], [], [], [], '{}');
        $request->setMethod('POST');

        $eventProphecy = $this->prophesize(EventInterface::class);
        $eventProphecy->getContext()->willReturn(['request' => $request]);

        $listener = new ReadListener($collectionDataProvider->reveal(), $itemDataProvider->reveal(), $subresourceDataProvider->reveal(), null, $identifierConverter->reveal());
        $listener->handleEvent($eventProphecy->reveal());

        $this->assertTrue($request->attributes->has('data'));
        $this->assertNull($request->attributes->get('data'));
    }

    public function testRetrieveCollectionGet()
    {
        $identifierConverter = $this->prophesize(IdentifierConverterInterface::class);

        $collectionDataProvider = $this->prophesize(CollectionDataProviderInterface::class);
        $collectionDataProvider->getCollection('Foo', 'get', ['filters' => ['foo' => 'bar']])->willReturn([])->shouldBeCalled();

        $itemDataProvider = $this->prophesize(ItemDataProviderInterface::class);
        $itemDataProvider->getItem()->shouldNotBeCalled();

        $subresourceDataProvider = $this->prophesize(SubresourceDataProviderInterface::class);
        $subresourceDataProvider->getSubresource()->shouldNotBeCalled();

        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'get', '_api_format' => 'json', '_api_mime_type' => 'application/json'], [], [], ['QUERY_STRING' => 'foo=bar']);
        $request->setMethod('GET');

        $eventProphecy = $this->prophesize(EventInterface::class);
        $eventProphecy->getContext()->willReturn(['request' => $request]);

        $listener = new ReadListener($collectionDataProvider->reveal(), $itemDataProvider->reveal(), $subresourceDataProvider->reveal(), null, $identifierConverter->reveal());
        $listener->handleEvent($eventProphecy->reveal());

        $this->assertSame([], $request->attributes->get('data'));
    }

    public function testRetrieveItem()
    {
        $identifierConverter = $this->prophesize(IdentifierConverterInterface::class);
        $identifierConverter->convert('1', 'Foo')->shouldBeCalled()->willReturn(['id' => '1']);

        $collectionDataProvider = $this->prophesize(CollectionDataProviderInterface::class);
        $collectionDataProvider->getCollection()->shouldNotBeCalled();

        $data = new \stdClass();
        $itemDataProvider = $this->prophesize(ItemDataProviderInterface::class);
        $itemDataProvider->getItem('Foo', ['id' => '1'], 'get', [IdentifierConverterInterface::HAS_IDENTIFIER_CONVERTER => true])->willReturn($data)->shouldBeCalled();

        $subresourceDataProvider = $this->prophesize(SubresourceDataProviderInterface::class);
        $subresourceDataProvider->getSubresource()->shouldNotBeCalled();

        $request = new Request([], [], ['id' => 1, '_api_resource_class' => 'Foo', '_api_item_operation_name' => 'get', '_api_format' => 'json', '_api_mime_type' => 'application/json']);
        $request->setMethod('GET');

        $eventProphecy = $this->prophesize(EventInterface::class);
        $eventProphecy->getContext()->willReturn(['request' => $request]);

        $listener = new ReadListener($collectionDataProvider->reveal(), $itemDataProvider->reveal(), $subresourceDataProvider->reveal(), null, $identifierConverter->reveal());
        $listener->handleEvent($eventProphecy->reveal());

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

        $eventProphecy = $this->prophesize(EventInterface::class);
        $eventProphecy->getContext()->willReturn(['request' => $request]);

        $listener = new ReadListener($collectionDataProvider->reveal(), $itemDataProvider->reveal(), $subresourceDataProvider->reveal());
        $listener->handleEvent($eventProphecy->reveal());

        $request->attributes->get('data');
    }

    public function testRetrieveSubresource()
    {
        $identifierConverter = $this->prophesize(IdentifierConverterInterface::class);
        $identifierConverter->convert('1', 'Bar')->shouldBeCalled()->willReturn(['id' => '1']);

        $collectionDataProvider = $this->prophesize(CollectionDataProviderInterface::class);
        $collectionDataProvider->getCollection()->shouldNotBeCalled();

        $itemDataProvider = $this->prophesize(ItemDataProviderInterface::class);
        $itemDataProvider->getItem()->shouldNotBeCalled();

        $data = [new \stdClass()];
        $subresourceDataProvider = $this->prophesize(SubresourceDataProviderInterface::class);
        $subresourceDataProvider->getSubresource('Foo', ['id' => ['id' => '1']], ['identifiers' => [['id', 'Bar', true]], 'property' => 'bar', IdentifierConverterInterface::HAS_IDENTIFIER_CONVERTER => true], 'get')->willReturn($data)->shouldBeCalled();

        $request = new Request([], [], ['id' => 1, '_api_resource_class' => 'Foo', '_api_subresource_operation_name' => 'get', '_api_format' => 'json', '_api_mime_type' => 'application/json', '_api_subresource_context' => ['identifiers' => [['id', 'Bar', true]], 'property' => 'bar']]);
        $request->setMethod('GET');

        $eventProphecy = $this->prophesize(EventInterface::class);
        $eventProphecy->getContext()->willReturn(['request' => $request]);

        $listener = new ReadListener($collectionDataProvider->reveal(), $itemDataProvider->reveal(), $subresourceDataProvider->reveal(), null, $identifierConverter->reveal());
        $listener->handleEvent($eventProphecy->reveal());

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

        $eventProphecy = $this->prophesize(EventInterface::class);
        $eventProphecy->getContext()->willReturn(['request' => $request]);

        $listener = new ReadListener($collectionDataProvider->reveal(), $itemDataProvider->reveal());
        $listener->handleEvent($eventProphecy->reveal());

        $request->attributes->get('data');
    }

    public function testRetrieveSubresourceNotFound()
    {
        $identifierConverter = $this->prophesize(IdentifierConverterInterface::class);
        $identifierConverter->convert('1', 'Bar')->willThrow(new InvalidIdentifierException())->shouldBeCalled();
        $this->expectException(NotFoundHttpException::class);

        $collectionDataProvider = $this->prophesize(CollectionDataProviderInterface::class);
        $collectionDataProvider->getCollection()->shouldNotBeCalled();

        $itemDataProvider = $this->prophesize(ItemDataProviderInterface::class);
        $itemDataProvider->getItem()->shouldNotBeCalled();

        $request = new Request([], [], ['id' => 1, '_api_resource_class' => 'Foo', '_api_subresource_operation_name' => 'get', '_api_format' => 'json', '_api_mime_type' => 'application/json', '_api_subresource_context' => ['identifiers' => [['id', 'Bar', true]], 'property' => 'bar']]);
        $request->setMethod('GET');

        $eventProphecy = $this->prophesize(EventInterface::class);
        $eventProphecy->getContext()->willReturn(['request' => $request]);

        $listener = new ReadListener($collectionDataProvider->reveal(), $itemDataProvider->reveal(), $this->prophesize(SubresourceDataProviderInterface::class)->reveal(), null, $identifierConverter->reveal());
        $listener->handleEvent($eventProphecy->reveal());
    }

    public function testRetrieveItemNotFound()
    {
        $identifierConverter = $this->prophesize(IdentifierConverterInterface::class);
        $identifierConverter->convert('22', 'Foo')->shouldBeCalled()->willReturn(['id' => 22]);
        $this->expectException(NotFoundHttpException::class);

        $collectionDataProvider = $this->prophesize(CollectionDataProviderInterface::class);

        $itemDataProvider = $this->prophesize(ItemDataProviderInterface::class);
        $itemDataProvider->getItem('Foo', ['id' => 22], 'get', [IdentifierConverterInterface::HAS_IDENTIFIER_CONVERTER => true])->willReturn(null)->shouldBeCalled();

        $subresourceDataProvider = $this->prophesize(SubresourceDataProviderInterface::class);

        $request = new Request([], [], ['id' => 22, '_api_resource_class' => 'Foo', '_api_item_operation_name' => 'get', '_api_format' => 'json', '_api_mime_type' => 'application/json']);
        $request->setMethod('GET');

        $eventProphecy = $this->prophesize(EventInterface::class);
        $eventProphecy->getContext()->willReturn(['request' => $request]);

        $listener = new ReadListener($collectionDataProvider->reveal(), $itemDataProvider->reveal(), $subresourceDataProvider->reveal(), null, $identifierConverter->reveal());
        $listener->handleEvent($eventProphecy->reveal());
    }

    public function testRetrieveBadItemNormalizedIdentifiers()
    {
        $this->expectException(NotFoundHttpException::class);

        $identifierConverter = $this->prophesize(IdentifierConverterInterface::class);
        $identifierConverter->convert('1', 'Foo')->shouldBeCalled()->willThrow(new InvalidIdentifierException());

        $collectionDataProvider = $this->prophesize(CollectionDataProviderInterface::class);
        $itemDataProvider = $this->prophesize(ItemDataProviderInterface::class);
        $subresourceDataProvider = $this->prophesize(SubresourceDataProviderInterface::class);

        $request = new Request([], [], ['id' => 1, '_api_resource_class' => 'Foo', '_api_item_operation_name' => 'get', '_api_format' => 'json', '_api_mime_type' => 'application/json']);
        $request->setMethod(Request::METHOD_GET);

        $eventProphecy = $this->prophesize(EventInterface::class);
        $eventProphecy->getContext()->willReturn(['request' => $request]);

        $listener = new ReadListener($collectionDataProvider->reveal(), $itemDataProvider->reveal(), $subresourceDataProvider->reveal(), null, $identifierConverter->reveal());
        $listener->handleEvent($eventProphecy->reveal());
    }

    public function testRetrieveBadSubresourceNormalizedIdentifiers()
    {
        $this->expectException(NotFoundHttpException::class);

        $identifierConverter = $this->prophesize(IdentifierConverterInterface::class);
        $identifierConverter->convert(Argument::type('string'), Argument::type('string'))->shouldBeCalled()->willThrow(new InvalidIdentifierException());

        $collectionDataProvider = $this->prophesize(CollectionDataProviderInterface::class);
        $collectionDataProvider->getCollection()->shouldNotBeCalled();

        $itemDataProvider = $this->prophesize(ItemDataProviderInterface::class);
        $itemDataProvider->getItem()->shouldNotBeCalled();

        $subresourceDataProvider = $this->prophesize(SubresourceDataProviderInterface::class);
        $subresourceDataProvider->getSubresource()->shouldNotBeCalled();

        $request = new Request([], [], ['id' => 1, '_api_resource_class' => 'Foo', '_api_subresource_operation_name' => 'get', '_api_format' => 'json', '_api_mime_type' => 'application/json', '_api_subresource_context' => ['identifiers' => [['id', 'Bar', true]], 'property' => 'bar']]);
        $request->setMethod(Request::METHOD_GET);

        $eventProphecy = $this->prophesize(EventInterface::class);
        $eventProphecy->getContext()->willReturn(['request' => $request]);

        $listener = new ReadListener($collectionDataProvider->reveal(), $itemDataProvider->reveal(), $subresourceDataProvider->reveal(), null, $identifierConverter->reveal());
        $listener->handleEvent($eventProphecy->reveal());
    }

    /**
     * @group legacy
     *
     * @expectedDeprecation The method ApiPlatform\Core\EventListener\ReadListener::onKernelRequest() is deprecated since 2.5 and will be removed in 3.0.
     * @expectedDeprecation Passing an instance of "Symfony\Component\HttpKernel\Event\GetResponseEvent" as argument of "ApiPlatform\Core\EventListener\ReadListener::handleEvent" is deprecated since 2.5 and will not be possible anymore in 3.0. Pass an instance of "ApiPlatform\Core\Event\EventInterface" instead.
     */
    public function testLegacyOnKernelRequest()
    {
        $collectionDataProvider = $this->prophesize(CollectionDataProviderInterface::class);
        $itemDataProvider = $this->prophesize(ItemDataProviderInterface::class);
        $subresourceDataProvider = $this->prophesize(SubresourceDataProviderInterface::class);
        $identifierConverter = $this->prophesize(IdentifierConverterInterface::class);

        $eventProphecy = $this->prophesize(GetResponseEvent::class);
        $eventProphecy->getRequest()->willReturn(new Request());

        $listener = new ReadListener($collectionDataProvider->reveal(), $itemDataProvider->reveal(), $subresourceDataProvider->reveal(), null, $identifierConverter->reveal());
        $listener->onKernelRequest($eventProphecy->reveal());
    }
}
