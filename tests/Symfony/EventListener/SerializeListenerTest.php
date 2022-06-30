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

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Serializer\ResourceList;
use ApiPlatform\Serializer\SerializerContextBuilderInterface;
use ApiPlatform\Symfony\EventListener\SerializeListener;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Tests\ProphecyTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class SerializeListenerTest extends TestCase
{
    use ProphecyTrait;

    public function testDoNotSerializeWhenControllerResultIsResponse()
    {
        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->serialize(Argument::cetera())->shouldNotBeCalled();

        $serializerContextBuilderProphecy = $this->prophesize(SerializerContextBuilderInterface::class);
        $serializerContextBuilderProphecy->createFromRequest(Argument::cetera());

        $event = new ViewEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            new Request(),
            \defined(HttpKernelInterface::class.'::MAIN_REQUEST') ? HttpKernelInterface::MAIN_REQUEST : HttpKernelInterface::MASTER_REQUEST,
            null
        );

        $listener = new SerializeListener($serializerProphecy->reveal(), $serializerContextBuilderProphecy->reveal());
        $listener->onKernelView($event);

        $this->assertNull($event->getResponse());
    }

    public function testDoNotSerializeWhenRespondFlagIsFalse()
    {
        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->serialize(Argument::cetera())->shouldNotBeCalled();

        $serializerContextBuilderProphecy = $this->prophesize(SerializerContextBuilderInterface::class);

        $dummy = new Dummy();

        $request = new Request([], [], ['data' => $dummy, '_api_resource_class' => Dummy::class, '_api_operation_name' => 'post', '_api_respond' => false]);
        $request->setMethod('POST');

        $event = new ViewEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $request,
            \defined(HttpKernelInterface::class.'::MAIN_REQUEST') ? HttpKernelInterface::MAIN_REQUEST : HttpKernelInterface::MASTER_REQUEST,
            $dummy
        );

        $listener = new SerializeListener($serializerProphecy->reveal(), $serializerContextBuilderProphecy->reveal());
        $listener->onKernelView($event);

        $this->assertNull($event->getResponse());
    }

    public function testDoNotSerializeWhenDisabledInOperationAttribute()
    {
        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->serialize(Argument::cetera())->shouldNotBeCalled();

        $serializerContextBuilderProphecy = $this->prophesize(SerializerContextBuilderInterface::class);

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadataCollection(Dummy::class, [
            (new ApiResource(operations: [
                'post' => new Post(serialize: false),
            ])),
        ]));

        $dummy = new Dummy();
        $request = new Request([], [], ['data' => $dummy, '_api_resource_class' => Dummy::class, '_api_operation_name' => 'post']);
        $request->setMethod('POST');

        $event = new ViewEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $request,
            \defined(HttpKernelInterface::class.'::MAIN_REQUEST') ? HttpKernelInterface::MAIN_REQUEST : HttpKernelInterface::MASTER_REQUEST,
            $dummy
        );

        $listener = new SerializeListener($serializerProphecy->reveal(), $serializerContextBuilderProphecy->reveal(), $resourceMetadataFactoryProphecy->reveal());
        $listener->onKernelView($event);

        $this->assertNull($event->getResponse());
    }

    public function testSerializeCollectionOperation()
    {
        $expectedContext = ['request_uri' => '', 'resource_class' => 'Foo', 'collection_operation_name' => 'get'];
        $serializerProphecy = $this->prophesize(SerializerInterface::class);

        $serializerProphecy
            ->serialize(
                Argument::any(),
                'xml',
                Argument::allOf(
                    Argument::that(function (array $context) {
                        return $context['resources'] instanceof ResourceList && $context['resources_to_push'] instanceof ResourceList;
                    }),
                    Argument::withEntry('request_uri', ''),
                    Argument::withEntry('resource_class', 'Foo'),
                    Argument::withEntry('collection_operation_name', 'get')
                )
            )
            ->willReturn('bar')
            ->shouldBeCalled();

        $serializerContextBuilderProphecy = $this->prophesize(SerializerContextBuilderInterface::class);
        $serializerContextBuilderProphecy->createFromRequest(Argument::type(Request::class), true, Argument::type('array'))->willReturn($expectedContext)->shouldBeCalled();

        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_operation_name' => 'get']);
        $request->setRequestFormat('xml');
        $event = new ViewEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $request,
            \defined(HttpKernelInterface::class.'::MAIN_REQUEST') ? HttpKernelInterface::MAIN_REQUEST : HttpKernelInterface::MASTER_REQUEST,
            new \stdClass()
        );

        $listener = new SerializeListener($serializerProphecy->reveal(), $serializerContextBuilderProphecy->reveal());
        $listener->onKernelView($event);

        $this->assertSame('bar', $event->getControllerResult());
    }

    public function testSerializeCollectionOperationWithOutputClassDisabled()
    {
        $expectedContext = ['request_uri' => '', 'resource_class' => 'Foo', 'collection_operation_name' => 'post', 'output' => ['class' => null]];
        $serializerProphecy = $this->prophesize(SerializerInterface::class);

        $serializerContextBuilderProphecy = $this->prophesize(SerializerContextBuilderInterface::class);
        $serializerContextBuilderProphecy->createFromRequest(Argument::type(Request::class), true, Argument::type('array'))->willReturn($expectedContext)->shouldBeCalled();

        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_operation_name' => 'get', '_api_output_class' => false]);
        $request->setRequestFormat('xml');

        $event = new ViewEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $request,
            \defined(HttpKernelInterface::class.'::MAIN_REQUEST') ? HttpKernelInterface::MAIN_REQUEST : HttpKernelInterface::MASTER_REQUEST,
            new \stdClass()
        );

        $listener = new SerializeListener($serializerProphecy->reveal(), $serializerContextBuilderProphecy->reveal());
        $listener->onKernelView($event);

        $this->assertNull($event->getControllerResult());
    }

    public function testSerializeItemOperation()
    {
        $expectedContext = ['request_uri' => '', 'resource_class' => 'Foo', 'operation_name' => 'get'];
        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy
            ->serialize(
                Argument::any(),
                'xml',
                Argument::allOf(
                    Argument::that(function (array $context) {
                        return $context['resources'] instanceof ResourceList && $context['resources_to_push'] instanceof ResourceList;
                    }),
                    Argument::withEntry('request_uri', ''),
                    Argument::withEntry('resource_class', 'Foo'),
                    Argument::withEntry('operation_name', 'get')
                )
            )
            ->willReturn('bar')
            ->shouldBeCalled();

        $serializerContextBuilderProphecy = $this->prophesize(SerializerContextBuilderInterface::class);
        $serializerContextBuilderProphecy->createFromRequest(Argument::type(Request::class), true, Argument::type('array'))->willReturn($expectedContext)->shouldBeCalled();

        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_operation_name' => 'get']);
        $request->setRequestFormat('xml');
        $event = new ViewEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $request,
            \defined(HttpKernelInterface::class.'::MAIN_REQUEST') ? HttpKernelInterface::MAIN_REQUEST : HttpKernelInterface::MASTER_REQUEST,
            new \stdClass()
        );

        $listener = new SerializeListener($serializerProphecy->reveal(), $serializerContextBuilderProphecy->reveal());
        $listener->onKernelView($event);

        $this->assertSame('bar', $event->getControllerResult());
    }

    public function testEncode()
    {
        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->willImplement(EncoderInterface::class);
        $serializerProphecy->encode(Argument::any(), 'xml')->willReturn('bar')->shouldBeCalled();
        $serializerProphecy->serialize(Argument::cetera())->shouldNotBeCalled();

        $serializerContextBuilderProphecy = $this->prophesize(SerializerContextBuilderInterface::class);

        $request = new Request([], [], ['_api_respond' => true]);
        $request->setRequestFormat('xml');

        $event = new ViewEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $request,
            \defined(HttpKernelInterface::class.'::MAIN_REQUEST') ? HttpKernelInterface::MAIN_REQUEST : HttpKernelInterface::MASTER_REQUEST,
            []
        );

        $listener = new SerializeListener($serializerProphecy->reveal(), $serializerContextBuilderProphecy->reveal());
        $listener->onKernelView($event);

        $this->assertSame('bar', $event->getControllerResult());
    }
}
