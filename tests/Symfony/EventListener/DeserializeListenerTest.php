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
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Serializer\SerializerContextBuilderInterface;
use ApiPlatform\Symfony\EventListener\DeserializeListener;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Tests\ProphecyTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class DeserializeListenerTest extends TestCase
{
    use ProphecyTrait;

    private const FORMATS = ['json' => ['application/json']];

    public function testDoNotCallWhenRequestMethodIsSafe(): void
    {
        $eventProphecy = $this->prophesize(RequestEvent::class);

        $request = new Request([], [], ['data' => new \stdClass()]);
        $request->setMethod('GET');
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->deserialize(Argument::cetera())->shouldNotBeCalled();

        $serializerContextBuilderProphecy = $this->prophesize(SerializerContextBuilderInterface::class);
        $serializerContextBuilderProphecy->createFromRequest(Argument::type(Request::class), false, Argument::type('array'))->shouldNotBeCalled();

        $resourceMetadataFactory = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactory->create()->shouldNotBeCalled();

        $listener = new DeserializeListener($serializerProphecy->reveal(), $serializerContextBuilderProphecy->reveal(), $resourceMetadataFactory->reveal());
        $listener->onKernelRequest($eventProphecy->reveal());
    }

    public function testDoNotCallWhenRequestNotManaged(): void
    {
        $eventProphecy = $this->prophesize(RequestEvent::class);

        $request = new Request([], [], ['data' => new \stdClass()], [], [], [], '{}');
        $request->setMethod('POST');
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->deserialize(Argument::cetera())->shouldNotBeCalled();

        $serializerContextBuilderProphecy = $this->prophesize(SerializerContextBuilderInterface::class);
        $serializerContextBuilderProphecy->createFromRequest(Argument::type(Request::class), false, Argument::type('array'))->shouldNotBeCalled();

        $resourceMetadataFactory = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactory->create()->shouldNotBeCalled();

        $listener = new DeserializeListener($serializerProphecy->reveal(), $serializerContextBuilderProphecy->reveal(), $resourceMetadataFactory->reveal());
        $listener->onKernelRequest($eventProphecy->reveal());
    }

    public function testDoNotDeserializeWhenReceiveFlagIsFalse(): void
    {
        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->deserialize(Argument::cetera())->shouldNotBeCalled();

        $serializerContextBuilderProphecy = $this->prophesize(SerializerContextBuilderInterface::class);

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create()->shouldNotBeCalled();

        $request = new Request([], [], ['data' => new Dummy(), '_api_resource_class' => Dummy::class, '_api_operation_name' => 'post', '_api_receive' => false]);
        $request->setMethod('POST');

        $eventProphecy = $this->prophesize(RequestEvent::class);
        $eventProphecy->getRequest()->willReturn($request);

        $listener = new DeserializeListener($serializerProphecy->reveal(), $serializerContextBuilderProphecy->reveal(), $resourceMetadataFactoryProphecy->reveal());
        $listener->onKernelRequest($eventProphecy->reveal());
    }

    public function testDoNotDeserializeWhenDisabledInOperationAttribute(): void
    {
        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->deserialize(Argument::cetera())->shouldNotBeCalled();

        $serializerContextBuilderProphecy = $this->prophesize(SerializerContextBuilderInterface::class);

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn(new ResourceMetadataCollection('Foo', [
            (new ApiResource(operations: [
                'post' => new Post(deserialize: false),
            ])),
        ]));

        $request = new Request([], [], ['data' => new Dummy(), '_api_resource_class' => Dummy::class, '_api_operation_name' => 'post']);
        $request->setMethod('POST');

        $eventProphecy = $this->prophesize(RequestEvent::class);
        $eventProphecy->getRequest()->willReturn($request);

        $listener = new DeserializeListener($serializerProphecy->reveal(), $serializerContextBuilderProphecy->reveal(), $resourceMetadataFactoryProphecy->reveal());
        $listener->onKernelRequest($eventProphecy->reveal());
    }

    /**
     * @dataProvider methodProvider
     */
    public function testDeserialize(string $method, bool $populateObject): void
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->shouldBeCalled()->willReturn(new ResourceMetadataCollection('Foo', [
            (new ApiResource(operations: [
                'put' => new Put(inputFormats: self::FORMATS),
                'post' => new Post(inputFormats: self::FORMATS),
            ])),
        ]));

        $this->doTestDeserialize($method, $populateObject, $resourceMetadataFactoryProphecy->reveal());
    }

    private function doTestDeserialize(string $method, bool $populateObject, $resourceMetadataFactory): void
    {
        $result = $populateObject ? new \stdClass() : null;
        $eventProphecy = $this->prophesize(RequestEvent::class);

        $request = new Request([], [], ['data' => $result, '_api_resource_class' => 'Foo', '_api_operation_name' => 'post'], [], [], [], '{}');
        $request->setMethod($method);
        $request->headers->set('Content-Type', 'application/json');
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $context = $populateObject ? [AbstractNormalizer::OBJECT_TO_POPULATE => $populateObject] : [];
        $context['input'] = ['class' => 'Foo'];
        $context['output'] = ['class' => 'Foo'];
        $context['resource_class'] = 'Foo';
        $serializerProphecy->deserialize('{}', 'Foo', 'json', $context)->willReturn($result)->shouldBeCalled();

        $serializerContextBuilderProphecy = $this->prophesize(SerializerContextBuilderInterface::class);
        $serializerContextBuilderProphecy->createFromRequest(Argument::type(Request::class), false, Argument::type('array'))->willReturn(['input' => ['class' => 'Foo'], 'output' => ['class' => 'Foo'], 'resource_class' => 'Foo'])->shouldBeCalled();

        $listener = new DeserializeListener($serializerProphecy->reveal(), $serializerContextBuilderProphecy->reveal(), $resourceMetadataFactory);
        $listener->onKernelRequest($eventProphecy->reveal());
    }

    /**
     * @dataProvider methodProvider
     */
    public function testDeserializeResourceClassSupportedFormat(string $method, bool $populateObject): void
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->shouldBeCalled()->willReturn(new ResourceMetadataCollection('Foo', [
            (new ApiResource(operations: [
                'post' => new Post(inputFormats: self::FORMATS),
            ])),
        ]));

        $this->doTestDeserializeResourceClassSupportedFormat($method, $populateObject, $resourceMetadataFactoryProphecy->reveal());
    }

    private function doTestDeserializeResourceClassSupportedFormat(string $method, bool $populateObject, $resourceMetadataFactory): void
    {
        $result = $populateObject ? new \stdClass() : null;
        $eventProphecy = $this->prophesize(RequestEvent::class);

        $request = new Request([], [], ['data' => $result, '_api_resource_class' => 'Foo', '_api_operation_name' => 'post'], [], [], [], '{}');
        $request->setMethod($method);
        $request->headers->set('Content-Type', 'application/json');
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $context = $populateObject ? [AbstractNormalizer::OBJECT_TO_POPULATE => $populateObject] : [];
        $context['input'] = ['class' => 'Foo'];
        $context['output'] = ['class' => 'Foo'];
        $context['resource_class'] = 'Foo';
        $serializerProphecy->deserialize('{}', 'Foo', 'json', $context)->willReturn($result)->shouldBeCalled();

        $serializerContextBuilderProphecy = $this->prophesize(SerializerContextBuilderInterface::class);
        $serializerContextBuilderProphecy->createFromRequest(Argument::type(Request::class), false, Argument::type('array'))->willReturn(['input' => ['class' => 'Foo'], 'output' => ['class' => 'Foo'], 'resource_class' => 'Foo'])->shouldBeCalled();

        $listener = new DeserializeListener($serializerProphecy->reveal(), $serializerContextBuilderProphecy->reveal(), $resourceMetadataFactory);

        $listener->onKernelRequest($eventProphecy->reveal());
    }

    public function methodProvider(): iterable
    {
        yield ['POST', false];
        yield ['PUT', true];
    }

    public function testContentNegotiation(): void
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->shouldBeCalled()->willReturn(new ResourceMetadataCollection('Foo', [
            (new ApiResource(operations: [
                'post' => new Post(inputFormats: ['jsonld' => ['application/ld+json'], 'xml' => ['text/xml']]),
            ])),
        ]));

        $this->doTestContentNegotiation($resourceMetadataFactoryProphecy->reveal());
    }

    private function doTestContentNegotiation($resourceMetadataFactory): void
    {
        $eventProphecy = $this->prophesize(RequestEvent::class);

        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_operation_name' => 'post'], [], [], [], '{}');
        $request->setMethod('POST');
        $request->headers->set('Content-Type', 'text/xml');
        $request->setFormat('xml', 'text/xml'); // Workaround to avoid weird behaviors
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();

        $context = ['input' => ['class' => 'Foo'], 'output' => ['class' => 'Foo'], 'resource_class' => 'Foo'];

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->deserialize('{}', 'Foo', 'xml', $context)->willReturn(new \stdClass())->shouldBeCalled();

        $serializerContextBuilderProphecy = $this->prophesize(SerializerContextBuilderInterface::class);
        $serializerContextBuilderProphecy->createFromRequest(Argument::type(Request::class), false, Argument::type('array'))->willReturn($context)->shouldBeCalled();

        $listener = new DeserializeListener(
            $serializerProphecy->reveal(),
            $serializerContextBuilderProphecy->reveal(),
            $resourceMetadataFactory
        );
        $listener->onKernelRequest($eventProphecy->reveal());
    }

    public function testNotSupportedContentType(): void
    {
        $this->expectException(UnsupportedMediaTypeHttpException::class);
        $this->expectExceptionMessage('The content-type "application/rdf+xml" is not supported. Supported MIME types are "application/ld+json", "text/xml".');

        $eventProphecy = $this->prophesize(RequestEvent::class);

        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_operation_name' => 'post'], [], [], [], '{}');
        $request->setMethod('POST');
        $request->headers->set('Content-Type', 'application/rdf+xml');
        $request->setRequestFormat('xml');
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->deserialize(Argument::cetera())->shouldNotBeCalled();

        $serializerContextBuilderProphecy = $this->prophesize(SerializerContextBuilderInterface::class);
        $serializerContextBuilderProphecy->createFromRequest(Argument::type(Request::class), false, Argument::type('array'))->willReturn(['input' => ['class' => 'Foo'], 'output' => ['class' => 'Foo']]);

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->shouldBeCalled()->willReturn(new ResourceMetadataCollection('Foo', [
            (new ApiResource(operations: [
                'post' => new Post(inputFormats: ['jsonld' => ['application/ld+json'], 'xml' => ['text/xml']]),
            ])),
        ]));

        $listener = new DeserializeListener(
            $serializerProphecy->reveal(),
            $serializerContextBuilderProphecy->reveal(),
            $resourceMetadataFactoryProphecy->reveal()
        );
        $listener->onKernelRequest($eventProphecy->reveal());
    }

    public function testNoContentType(): void
    {
        $this->expectException(UnsupportedMediaTypeHttpException::class);
        $this->expectExceptionMessage('The "Content-Type" header must exist.');

        $eventProphecy = $this->prophesize(RequestEvent::class);

        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_operation_name' => 'post'], [], [], [], '{}');
        $request->setMethod('POST');
        $request->setRequestFormat('unknown');
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->deserialize(Argument::cetera())->shouldNotBeCalled();

        $serializerContextBuilderProphecy = $this->prophesize(SerializerContextBuilderInterface::class);
        $serializerContextBuilderProphecy->createFromRequest(Argument::type(Request::class), false, Argument::type('array'))->willReturn(['input' => ['class' => 'Foo'], 'output' => ['class' => 'Foo']]);

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->shouldBeCalled()->willReturn(new ResourceMetadataCollection('Foo', [
            (new ApiResource(operations: [
                'post' => new Post(formats: ['jsonld' => ['application/ld+json'], 'xml' => ['text/xml']]),
            ])),
        ]));

        $listener = new DeserializeListener(
            $serializerProphecy->reveal(),
            $serializerContextBuilderProphecy->reveal(),
            $resourceMetadataFactoryProphecy->reveal()
        );
        $listener->onKernelRequest($eventProphecy->reveal());
    }
}
