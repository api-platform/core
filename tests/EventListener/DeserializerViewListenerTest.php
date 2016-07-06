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
use ApiPlatform\Core\Serializer\SerializerContextBuilderInterface;
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

        $serializerContextBuilderProphecy = $this->prophesize(SerializerContextBuilderInterface::class);
        $serializerContextBuilderProphecy->createFromRequest(Argument::type(Request::class), false, Argument::type('array'))->shouldNotBeCalled();

        $listener = new DeserializerViewListener($serializerProphecy->reveal(), $serializerContextBuilderProphecy->reveal());
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

        $serializerContextBuilderProphecy = $this->prophesize(SerializerContextBuilderInterface::class);
        $serializerContextBuilderProphecy->createFromRequest(Argument::type(Request::class), false, Argument::type('array'))->shouldNotBeCalled();

        $listener = new DeserializerViewListener($serializerProphecy->reveal(), $serializerContextBuilderProphecy->reveal());
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

        $serializerContextBuilderProphecy = $this->prophesize(SerializerContextBuilderInterface::class);
        $serializerContextBuilderProphecy->createFromRequest(Argument::type(Request::class), false, Argument::type('array'))->shouldNotBeCalled();

        $listener = new DeserializerViewListener($serializerProphecy->reveal(), $serializerContextBuilderProphecy->reveal());
        $listener->onKernelView($eventProphecy->reveal());
    }

    /**
     * @dataProvider methodProvider
     */
    public function testDeserialize(string $method, bool $populateObject)
    {
        $result = $populateObject ? new \stdClass() : null;
        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getControllerResult()->willReturn($result);
        $eventProphecy->setControllerResult($result)->shouldBeCalled();

        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'post', '_api_format' => 'json', '_api_mime_type' => 'application/json'], [], [], [], '{}');
        $request->setMethod($method);
        $eventProphecy->getRequest()->willReturn($request);

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $context = $populateObject ? ['object_to_populate' => $populateObject] : [];
        $serializerProphecy->deserialize('{}', 'Foo', 'json', $context)->willReturn($result);

        $serializerContextBuilderProphecy = $this->prophesize(SerializerContextBuilderInterface::class);
        $serializerContextBuilderProphecy->createFromRequest(Argument::type(Request::class), false, Argument::type('array'))->willReturn([]);

        $listener = new DeserializerViewListener($serializerProphecy->reveal(), $serializerContextBuilderProphecy->reveal());
        $listener->onKernelView($eventProphecy->reveal());
    }

    public function methodProvider()
    {
        return [[Request::METHOD_POST, false], [Request::METHOD_PUT, true]];
    }
}
