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
    const FORMATS = ['json' => ['application/json']];

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

        $listener = new DeserializeListener($serializerProphecy->reveal(), $serializerContextBuilderProphecy->reveal(), self::FORMATS);
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

        $listener = new DeserializeListener($serializerProphecy->reveal(), $serializerContextBuilderProphecy->reveal(), self::FORMATS);
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
        $request->headers->set('Content-Type', 'application/json');
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $context = $populateObject ? ['object_to_populate' => $populateObject] : [];
        $serializerProphecy->deserialize('{}', 'Foo', 'json', $context)->willReturn($result)->shouldBeCalled();

        $serializerContextBuilderProphecy = $this->prophesize(SerializerContextBuilderInterface::class);
        $serializerContextBuilderProphecy->createFromRequest(Argument::type(Request::class), false, Argument::type('array'))->willReturn([])->shouldBeCalled();

        $listener = new DeserializeListener($serializerProphecy->reveal(), $serializerContextBuilderProphecy->reveal(), self::FORMATS);
        $listener->onKernelRequest($eventProphecy->reveal());
    }

    public function methodProvider()
    {
        return [[Request::METHOD_POST, false], [Request::METHOD_PUT, true]];
    }

    public function testContentNegotiation()
    {
        $eventProphecy = $this->prophesize(GetResponseEvent::class);

        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'post'], [], [], [], '{}');
        $request->setMethod(Request::METHOD_POST);
        $request->headers->set('Content-Type', 'text/xml');
        $request->setFormat('xml', 'text/xml'); // Workaround to avoid weird behaviors
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->deserialize('{}', 'Foo', 'xml', [])->willReturn(new \stdClass())->shouldBeCalled();

        $serializerContextBuilderProphecy = $this->prophesize(SerializerContextBuilderInterface::class);
        $serializerContextBuilderProphecy->createFromRequest(Argument::type(Request::class), false, Argument::type('array'))->willReturn([])->shouldBeCalled();

        $listener = new DeserializeListener(
            $serializerProphecy->reveal(),
            $serializerContextBuilderProphecy->reveal(),
            ['jsonld' => ['application/ld+json'], 'xml' => ['text/xml']]
        );
        $listener->onKernelRequest($eventProphecy->reveal());
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException
     * @expectedExceptionMessage The content-type "application/rdf+xml" is not supported. Supported MIME types are "application/ld+json", "text/xml".
     */
    public function testNotSupportedContentType()
    {
        $eventProphecy = $this->prophesize(GetResponseEvent::class);

        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'post'], [], [], [], '{}');
        $request->setMethod(Request::METHOD_POST);
        $request->headers->set('Content-Type', 'application/rdf+xml');
        $request->setRequestFormat('xml');
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->deserialize()->shouldNotBeCalled();

        $serializerContextBuilderProphecy = $this->prophesize(SerializerContextBuilderInterface::class);
        $serializerContextBuilderProphecy->createFromRequest()->shouldNotBeCalled();

        $listener = new DeserializeListener(
            $serializerProphecy->reveal(),
            $serializerContextBuilderProphecy->reveal(),
            ['jsonld' => ['application/ld+json'], 'xml' => ['text/xml']]
        );
        $listener->onKernelRequest($eventProphecy->reveal());
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException
     * @expectedExceptionMessage The "Content-Type" header must exist.
     */
    public function testNoContentType()
    {
        $eventProphecy = $this->prophesize(GetResponseEvent::class);

        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'post'], [], [], [], '{}');
        $request->setMethod(Request::METHOD_POST);
        $request->setRequestFormat('unknown');
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->deserialize()->shouldNotBeCalled();

        $serializerContextBuilderProphecy = $this->prophesize(SerializerContextBuilderInterface::class);
        $serializerContextBuilderProphecy->createFromRequest()->shouldNotBeCalled();

        $listener = new DeserializeListener(
            $serializerProphecy->reveal(),
            $serializerContextBuilderProphecy->reveal(),
            ['jsonld' => ['application/ld+json'], 'xml' => ['text/xml']]
        );
        $listener->onKernelRequest($eventProphecy->reveal());
    }
}
