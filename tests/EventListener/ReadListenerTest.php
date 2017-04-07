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
use ApiPlatform\Core\EventListener\ReadListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ReadListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testNotAnApiPlatformRequest()
    {
        $collectionDataProvider = $this->prophesize(CollectionDataProviderInterface::class);
        $collectionDataProvider->getCollection()->shouldNotBeCalled();

        $itemDataProvider = $this->prophesize(ItemDataProviderInterface::class);
        $itemDataProvider->getItem()->shouldNotBeCalled();

        $request = new Request();

        $event = $this->prophesize(GetResponseEvent::class);
        $event->getRequest()->willReturn($request)->shouldBeCalled();

        $listener = new ReadListener($collectionDataProvider->reveal(), $itemDataProvider->reveal());
        $listener->onKernelRequest($event->reveal());
    }

    public function testRetrieveCollectionPost()
    {
        $collectionDataProvider = $this->prophesize(CollectionDataProviderInterface::class);
        $collectionDataProvider->getCollection()->shouldNotBeCalled();

        $itemDataProvider = $this->prophesize(ItemDataProviderInterface::class);
        $itemDataProvider->getItem()->shouldNotBeCalled();

        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'post', '_api_format' => 'json', '_api_mime_type' => 'application/json'], [], [], [], '{}');
        $request->setMethod(Request::METHOD_POST);

        $event = $this->prophesize(GetResponseEvent::class);
        $event->getRequest()->willReturn($request)->shouldBeCalled();

        $listener = new ReadListener($collectionDataProvider->reveal(), $itemDataProvider->reveal());
        $listener->onKernelRequest($event->reveal());

        $this->assertTrue($request->attributes->has('data'));
        $this->assertNull($request->attributes->get('data'));
    }

    public function testRetrieveCollectionGet()
    {
        $collectionDataProvider = $this->prophesize(CollectionDataProviderInterface::class);
        $collectionDataProvider->getCollection('Foo', 'get')->willReturn([])->shouldBeCalled();

        $itemDataProvider = $this->prophesize(ItemDataProviderInterface::class);
        $itemDataProvider->getItem()->shouldNotBeCalled();

        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'get', '_api_format' => 'json', '_api_mime_type' => 'application/json']);
        $request->setMethod(Request::METHOD_GET);

        $event = $this->prophesize(GetResponseEvent::class);
        $event->getRequest()->willReturn($request)->shouldBeCalled();

        $listener = new ReadListener($collectionDataProvider->reveal(), $itemDataProvider->reveal());
        $listener->onKernelRequest($event->reveal());

        $this->assertSame([], $request->attributes->get('data'));
    }

    public function testRetrieveItem()
    {
        $collectionDataProvider = $this->prophesize(CollectionDataProviderInterface::class);
        $collectionDataProvider->getCollection()->shouldNotBeCalled();

        $data = new \stdClass();
        $itemDataProvider = $this->prophesize(ItemDataProviderInterface::class);
        $itemDataProvider->getItem('Foo', 1, 'get')->willReturn($data)->shouldBeCalled();

        $request = new Request([], [], ['id' => 1, '_api_resource_class' => 'Foo', '_api_item_operation_name' => 'get', '_api_format' => 'json', '_api_mime_type' => 'application/json']);
        $request->setMethod(Request::METHOD_GET);

        $event = $this->prophesize(GetResponseEvent::class);
        $event->getRequest()->willReturn($request)->shouldBeCalled();

        $listener = new ReadListener($collectionDataProvider->reveal(), $itemDataProvider->reveal());
        $listener->onKernelRequest($event->reveal());

        $this->assertSame($data, $request->attributes->get('data'));
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function testRetrieveItemNotFound()
    {
        $collectionDataProvider = $this->prophesize(CollectionDataProviderInterface::class);

        $itemDataProvider = $this->prophesize(ItemDataProviderInterface::class);
        $itemDataProvider->getItem('Foo', 22, 'get')->willReturn(null)->shouldBeCalled();

        $request = new Request([], [], ['id' => 22, '_api_resource_class' => 'Foo', '_api_item_operation_name' => 'get', '_api_format' => 'json', '_api_mime_type' => 'application/json']);
        $request->setMethod(Request::METHOD_GET);

        $event = $this->prophesize(GetResponseEvent::class);
        $event->getRequest()->willReturn($request)->shouldBeCalled();

        $listener = new ReadListener($collectionDataProvider->reveal(), $itemDataProvider->reveal());
        $listener->onKernelRequest($event->reveal());
    }
}
