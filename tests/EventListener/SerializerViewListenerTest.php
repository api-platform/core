<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Tests\EventListener;

use ApiPlatform\Core\EventListener\SerializerViewListener;
use ApiPlatform\Core\Serializer\SerializerContextBuilderInterface;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class SerializerViewListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testDoNotSerializeResponse()
    {
        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->serialize(Argument::any(), Argument::type('string'), Argument::type('array'))->shouldNotBeCalled();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getControllerResult()->willReturn(new Response());
        $eventProphecy->getRequest()->willReturn(new Request([], [], ['_api_format' => 'xml']));

        $serializerContextBuilderProphecy = $this->prophesize(SerializerContextBuilderInterface::class);
        $serializerContextBuilderProphecy->createFromRequest(Argument::type(Request::class), false, Argument::type('array'))->shouldNotBeCalled();

        $listener = new SerializerViewListener($serializerProphecy->reveal(), $serializerContextBuilderProphecy->reveal());
        $listener->onKernelView($eventProphecy->reveal());
    }

    public function testDoNotSerializeWhenFormatNotSet()
    {
        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->serialize(Argument::any(), Argument::type('string'), Argument::type('array'))->shouldNotBeCalled();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getControllerResult()->willReturn(new \stdClass());
        $eventProphecy->getRequest()->willReturn(new Request());

        $serializerContextBuilderProphecy = $this->prophesize(SerializerContextBuilderInterface::class);
        $serializerContextBuilderProphecy->createFromRequest(Argument::type(Request::class), false, Argument::type('array'))->shouldNotBeCalled();

        $listener = new SerializerViewListener($serializerProphecy->reveal(), $serializerContextBuilderProphecy->reveal());
        $listener->onKernelView($eventProphecy->reveal());
    }

    public function testDoNotSerializeWhenResourceClassNotSet()
    {
        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->serialize(Argument::any(), Argument::type('string'), Argument::type('array'))->shouldNotBeCalled();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getControllerResult()->willReturn(new \stdClass());
        $eventProphecy->getRequest()->willReturn(new Request([], [], ['_api_format' => 'xml', '_collection_operation_name' => 'get']));

        $serializerContextBuilderProphecy = $this->prophesize(SerializerContextBuilderInterface::class);
        $serializerContextBuilderProphecy->createFromRequest(Argument::type(Request::class), false, Argument::type('array'))->shouldNotBeCalled();

        $listener = new SerializerViewListener($serializerProphecy->reveal(), $serializerContextBuilderProphecy->reveal());
        $listener->onKernelView($eventProphecy->reveal());
    }

    public function testDoNotSerializeWhenOperationNotSet()
    {
        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->serialize(Argument::any(), Argument::type('string'), Argument::type('array'))->shouldNotBeCalled();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getControllerResult()->willReturn(new \stdClass());
        $eventProphecy->getRequest()->willReturn(new Request([], [], ['_api_format' => 'xml', '_resource_class' => 'Foo']));

        $serializerContextBuilderProphecy = $this->prophesize(SerializerContextBuilderInterface::class);
        $serializerContextBuilderProphecy->createFromRequest(Argument::type(Request::class), false, Argument::type('array'))->shouldNotBeCalled();

        $listener = new SerializerViewListener($serializerProphecy->reveal(), $serializerContextBuilderProphecy->reveal());
        $listener->onKernelView($eventProphecy->reveal());
    }

    public function testSerializeCollectionOperation()
    {
        $expectedContext = ['request_uri' => '', 'resource_class' => 'Foo', 'collection_operation_name' => 'get'];
        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->serialize(Argument::any(), Argument::type('string'), $expectedContext)->willReturn('bar')->shouldBeCalled();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getControllerResult()->willReturn(new \stdClass());
        $eventProphecy->getRequest()->willReturn(new Request([], [], ['_api_format' => 'xml', '_resource_class' => 'Foo', '_collection_operation_name' => 'get']));
        $eventProphecy->setControllerResult('bar')->shouldBeCalled();

        $serializerContextBuilderProphecy = $this->prophesize(SerializerContextBuilderInterface::class);
        $serializerContextBuilderProphecy->createFromRequest(Argument::type(Request::class), true, Argument::type('array'))->willReturn($expectedContext);

        $listener = new SerializerViewListener($serializerProphecy->reveal(), $serializerContextBuilderProphecy->reveal());
        $listener->onKernelView($eventProphecy->reveal());
    }

    public function testSerializeItemOperation()
    {
        $expectedContext = ['request_uri' => '', 'resource_class' => 'Foo', 'item_operation_name' => 'get'];
        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->serialize(Argument::any(), Argument::type('string'), $expectedContext)->willReturn('bar')->shouldBeCalled();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getControllerResult()->willReturn(new \stdClass());
        $eventProphecy->getRequest()->willReturn(new Request([], [], ['_api_format' => 'xml', '_resource_class' => 'Foo', '_item_operation_name' => 'get']));
        $eventProphecy->setControllerResult('bar')->shouldBeCalled();

        $serializerContextBuilderProphecy = $this->prophesize(SerializerContextBuilderInterface::class);
        $serializerContextBuilderProphecy->createFromRequest(Argument::type(Request::class), true, Argument::type('array'))->willReturn($expectedContext);

        $listener = new SerializerViewListener($serializerProphecy->reveal(), $serializerContextBuilderProphecy->reveal());
        $listener->onKernelView($eventProphecy->reveal());
    }
}
