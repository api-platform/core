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

use ApiPlatform\Core\EventListener\DeserializerViewListener;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class DeserializerViewListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testDoNotCallWhenAResponse()
    {
        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getControllerResult()->willReturn(new Response());
        $eventProphecy->setControllerResult()->shouldNotBeCalled();

        $request = new Request([], [], [], [], [], [], '{}');
        $request->setMethod(Request::METHOD_POST);
        $eventProphecy->getRequest()->willReturn($request);

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->deserialize()->shouldNotBeCalled();

        $listener = new DeserializerViewListener($serializerProphecy->reveal());
        $listener->onKernelView($eventProphecy->reveal());
    }

    public function testDoNotCallWhenRequestMethodIsSafe()
    {
        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getControllerResult()->willReturn(new \stdClass());
        $eventProphecy->setControllerResult()->shouldNotBeCalled();

        $request = new Request();
        $request->setMethod(Request::METHOD_GET);
        $eventProphecy->getRequest()->willReturn($request);

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->deserialize()->shouldNotBeCalled();

        $listener = new DeserializerViewListener($serializerProphecy->reveal());
        $listener->onKernelView($eventProphecy->reveal());
    }

    public function testDoNotCallWhenRequestNotManaged()
    {
        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getControllerResult()->willReturn(new \stdClass());
        $eventProphecy->setControllerResult()->shouldNotBeCalled();

        $request = new Request([], [], [], [], [], [], '{}');
        $request->setMethod(Request::METHOD_POST);
        $eventProphecy->getRequest()->willReturn($request);

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->deserialize()->shouldNotBeCalled();

        $listener = new DeserializerViewListener($serializerProphecy->reveal());
        $listener->onKernelView($eventProphecy->reveal());
    }

    /**
     * @dataProvider methodProvider
     */
    public function testDeserialize($method)
    {
        $result = new \stdClass();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getControllerResult()->willReturn(new \stdClass());
        $eventProphecy->setControllerResult($result)->shouldBeCalled();

        $request = new Request([], [], ['_resource_class' => 'Foo', '_collection_operation_name' => 'post', '_api_format' => 'json'], [], [], [], '{}');
        $request->setMethod($method);
        $eventProphecy->getRequest()->willReturn($request);

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->deserialize('{}', 'Foo', 'json', Argument::type('array'))->willReturn($result);

        $listener = new DeserializerViewListener($serializerProphecy->reveal());
        $listener->onKernelView($eventProphecy->reveal());
    }

    public function methodProvider()
    {
        return [[Request::METHOD_POST], [Request::METHOD_PUT]];
    }
}
