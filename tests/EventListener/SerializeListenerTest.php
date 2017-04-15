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

use ApiPlatform\Core\EventListener\SerializeListener;
use ApiPlatform\Core\Serializer\SerializerContextBuilderInterface;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class SerializeListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testDoNotSerializeResponse()
    {
        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->serialize()->shouldNotBeCalled();

        $request = new Request();
        $request->setRequestFormat('xml');

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getControllerResult()->willReturn(new Response())->shouldBeCalled();
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();

        $serializerContextBuilderProphecy = $this->prophesize(SerializerContextBuilderInterface::class);
        $serializerContextBuilderProphecy->createFromRequest()->shouldNotBeCalled();

        $listener = new SerializeListener($serializerProphecy->reveal(), $serializerContextBuilderProphecy->reveal());
        $listener->onKernelView($eventProphecy->reveal());
    }

    public function testDoNotSerializeWhenFormatNotSet()
    {
        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->serialize()->shouldNotBeCalled();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getControllerResult()->willReturn(new \stdClass())->shouldBeCalled();
        $eventProphecy->getRequest()->willReturn(new Request())->shouldBeCalled();

        $serializerContextBuilderProphecy = $this->prophesize(SerializerContextBuilderInterface::class);
        $serializerContextBuilderProphecy->createFromRequest()->shouldNotBeCalled();

        $listener = new SerializeListener($serializerProphecy->reveal(), $serializerContextBuilderProphecy->reveal());
        $listener->onKernelView($eventProphecy->reveal());
    }

    public function testDoNotSerializeWhenResourceClassNotSet()
    {
        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->serialize()->shouldNotBeCalled();

        $request = new Request([], [], ['_api_collection_operation_name' => 'get']);
        $request->setRequestFormat('xml');

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getControllerResult()->willReturn(new \stdClass())->shouldBeCalled();
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();

        $serializerContextBuilderProphecy = $this->prophesize(SerializerContextBuilderInterface::class);
        $serializerContextBuilderProphecy->createFromRequest()->shouldNotBeCalled();

        $listener = new SerializeListener($serializerProphecy->reveal(), $serializerContextBuilderProphecy->reveal());
        $listener->onKernelView($eventProphecy->reveal());
    }

    public function testDoNotSerializeWhenOperationNotSet()
    {
        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->serialize()->shouldNotBeCalled();

        $request = new Request([], [], ['_api_resource_class' => 'Foo']);
        $request->setRequestFormat('xml');

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getControllerResult()->willReturn(new \stdClass())->shouldBeCalled();
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();

        $serializerContextBuilderProphecy = $this->prophesize(SerializerContextBuilderInterface::class);
        $serializerContextBuilderProphecy->createFromRequest()->shouldNotBeCalled();

        $listener = new SerializeListener($serializerProphecy->reveal(), $serializerContextBuilderProphecy->reveal());
        $listener->onKernelView($eventProphecy->reveal());
    }

    public function testSerializeCollectionOperation()
    {
        $expectedContext = ['request_uri' => '', 'resource_class' => 'Foo', 'collection_operation_name' => 'get'];
        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->serialize(Argument::any(), 'xml', $expectedContext)->willReturn('bar')->shouldBeCalled();

        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'get']);
        $request->setRequestFormat('xml');

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getControllerResult()->willReturn(new \stdClass())->shouldBeCalled();
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();
        $eventProphecy->setControllerResult('bar')->shouldBeCalled();

        $serializerContextBuilderProphecy = $this->prophesize(SerializerContextBuilderInterface::class);
        $serializerContextBuilderProphecy->createFromRequest(Argument::type(Request::class), true, Argument::type('array'))->willReturn($expectedContext)->shouldBeCalled();

        $listener = new SerializeListener($serializerProphecy->reveal(), $serializerContextBuilderProphecy->reveal());
        $listener->onKernelView($eventProphecy->reveal());
    }

    public function testSerializeItemOperation()
    {
        $expectedContext = ['request_uri' => '', 'resource_class' => 'Foo', 'item_operation_name' => 'get'];
        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->serialize(Argument::any(), 'xml', $expectedContext)->willReturn('bar')->shouldBeCalled();

        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_item_operation_name' => 'get']);
        $request->setRequestFormat('xml');

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getControllerResult()->willReturn(new \stdClass())->shouldBeCalled();
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();
        $eventProphecy->setControllerResult('bar')->shouldBeCalled();

        $serializerContextBuilderProphecy = $this->prophesize(SerializerContextBuilderInterface::class);
        $serializerContextBuilderProphecy->createFromRequest(Argument::type(Request::class), true, Argument::type('array'))->willReturn($expectedContext)->shouldBeCalled();

        $listener = new SerializeListener($serializerProphecy->reveal(), $serializerContextBuilderProphecy->reveal());
        $listener->onKernelView($eventProphecy->reveal());
    }

    public function testEncode()
    {
        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->willImplement(EncoderInterface::class);
        $serializerProphecy->encode(Argument::any(), 'xml')->willReturn('bar')->shouldBeCalled();
        $serializerProphecy->serialize()->shouldNotBeCalled();

        $request = new Request([], [], ['_api_respond' => true]);
        $request->setRequestFormat('xml');

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getControllerResult()->willReturn([])->shouldBeCalled();
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();
        $eventProphecy->setControllerResult('bar')->shouldBeCalled();

        $serializerContextBuilderProphecy = $this->prophesize(SerializerContextBuilderInterface::class);

        $listener = new SerializeListener($serializerProphecy->reveal(), $serializerContextBuilderProphecy->reveal());
        $listener->onKernelView($eventProphecy->reveal());
    }
}
