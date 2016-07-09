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

use ApiPlatform\Core\EventListener\DeserializeListener;
use ApiPlatform\Core\Serializer\SerializerContextBuilderInterface;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class DeserializeListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testDoNotCallWhenRequestMethodIsSafe()
    {
        $eventProphecy = $this->prophesize(GetResponseEvent::class);

        $request = new Request([], [], ['data' => new \stdClass()]);
        $request->setMethod(Request::METHOD_GET);
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->deserialize()->shouldNotBeCalled();

        $serializerContextBuilderProphecy = $this->prophesize(SerializerContextBuilderInterface::class);
        $serializerContextBuilderProphecy->createFromRequest(Argument::type(Request::class), false, Argument::type('array'))->shouldNotBeCalled();

        $listener = new DeserializeListener($serializerProphecy->reveal(), $serializerContextBuilderProphecy->reveal());
        $listener->onKernelRequest($eventProphecy->reveal());
    }

    public function testDoNotCallWhenRequestNotManaged()
    {
        $eventProphecy = $this->prophesize(GetResponseEvent::class);

        $request = new Request([], [], ['data' => new \stdClass()], [], [], [], '{}');
        $request->setMethod(Request::METHOD_POST);
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->deserialize()->shouldNotBeCalled();

        $serializerContextBuilderProphecy = $this->prophesize(SerializerContextBuilderInterface::class);
        $serializerContextBuilderProphecy->createFromRequest(Argument::type(Request::class), false, Argument::type('array'))->shouldNotBeCalled();

        $listener = new DeserializeListener($serializerProphecy->reveal(), $serializerContextBuilderProphecy->reveal());
        $listener->onKernelRequest($eventProphecy->reveal());
    }

    /**
     * @dataProvider methodProvider
     */
    public function testDeserialize(string $method, bool $populateObject)
    {
        $result = $populateObject ? new \stdClass() : null;
        $eventProphecy = $this->prophesize(GetResponseEvent::class);

        $request = new Request([], [], ['data' => $result, '_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'post'], [], [], [], '{}');
        $request->setMethod($method);
        $request->setRequestFormat('json');
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $context = $populateObject ? ['object_to_populate' => $populateObject] : [];
        $serializerProphecy->deserialize('{}', 'Foo', 'json', $context)->willReturn($result)->shouldBeCalled();

        $serializerContextBuilderProphecy = $this->prophesize(SerializerContextBuilderInterface::class);
        $serializerContextBuilderProphecy->createFromRequest(Argument::type(Request::class), false, Argument::type('array'))->willReturn([])->shouldBeCalled();

        $listener = new DeserializeListener($serializerProphecy->reveal(), $serializerContextBuilderProphecy->reveal());
        $listener->onKernelRequest($eventProphecy->reveal());
    }

    public function methodProvider()
    {
        return [[Request::METHOD_POST, false], [Request::METHOD_PUT, true]];
    }
}
