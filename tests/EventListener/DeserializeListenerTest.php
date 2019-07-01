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

use ApiPlatform\Core\Api\FormatsProviderInterface;
use ApiPlatform\Core\EventListener\DeserializeListener;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Serializer\SerializerContextBuilderInterface;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class DeserializeListenerTest extends TestCase
{
    public const FORMATS = ['json' => ['application/json']];

    public function testDoNotCallWhenRequestMethodIsSafe()
    {
        $eventProphecy = $this->prophesize(GetResponseEvent::class);

        $request = new Request([], [], ['data' => new \stdClass()]);
        $request->setMethod('GET');
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->deserialize(Argument::cetera())->shouldNotBeCalled();

        $serializerContextBuilderProphecy = $this->prophesize(SerializerContextBuilderInterface::class);
        $serializerContextBuilderProphecy->createFromRequest(Argument::type(Request::class), false, Argument::type('array'))->shouldNotBeCalled();

        $formatsProviderProphecy = $this->prophesize(FormatsProviderInterface::class);
        $formatsProviderProphecy->getFormatsFromAttributes(Argument::type('array'))->shouldNotBeCalled();

        $listener = new DeserializeListener($serializerProphecy->reveal(), $serializerContextBuilderProphecy->reveal(), $formatsProviderProphecy->reveal());
        $listener->onKernelRequest($eventProphecy->reveal());
    }

    public function testDoNotCallWhenRequestNotManaged()
    {
        $eventProphecy = $this->prophesize(GetResponseEvent::class);

        $request = new Request([], [], ['data' => new \stdClass()], [], [], [], '{}');
        $request->setMethod('POST');
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->deserialize(Argument::cetera())->shouldNotBeCalled();

        $serializerContextBuilderProphecy = $this->prophesize(SerializerContextBuilderInterface::class);
        $serializerContextBuilderProphecy->createFromRequest(Argument::type(Request::class), false, Argument::type('array'))->shouldNotBeCalled();

        $formatsProviderProphecy = $this->prophesize(FormatsProviderInterface::class);
        $formatsProviderProphecy->getFormatsFromAttributes(Argument::type('array'))->shouldNotBeCalled();

        $listener = new DeserializeListener($serializerProphecy->reveal(), $serializerContextBuilderProphecy->reveal(), $formatsProviderProphecy->reveal());
        $listener->onKernelRequest($eventProphecy->reveal());
    }

    public function testDoNotDeserializeWhenReceiveFlagIsFalse()
    {
        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->deserialize(Argument::cetera())->shouldNotBeCalled();

        $serializerContextBuilderProphecy = $this->prophesize(SerializerContextBuilderInterface::class);

        $formatsProviderProphecy = $this->prophesize(FormatsProviderInterface::class);

        $request = new Request([], [], ['data' => new Dummy(), '_api_resource_class' => Dummy::class, '_api_collection_operation_name' => 'post', '_api_receive' => false]);
        $request->setMethod('POST');

        $eventProphecy = $this->prophesize(GetResponseEvent::class);
        $eventProphecy->getRequest()->willReturn($request);

        $listener = new DeserializeListener($serializerProphecy->reveal(), $serializerContextBuilderProphecy->reveal(), $formatsProviderProphecy->reveal());
        $listener->onKernelRequest($eventProphecy->reveal());
    }

    public function testDoNotDeserializeWhenDisabledInOperationAttribute()
    {
        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->deserialize(Argument::cetera())->shouldNotBeCalled();

        $serializerContextBuilderProphecy = $this->prophesize(SerializerContextBuilderInterface::class);

        $formatsProviderProphecy = $this->prophesize(FormatsProviderInterface::class);
        $formatsProviderProphecy->getFormatsFromAttributes(Argument::type('array'));

        $resourceMetadata = new ResourceMetadata('Dummy', null, null, [], [
            'post' => [
                'deserialize' => false,
            ],
        ]);

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn($resourceMetadata);

        $request = new Request([], [], ['data' => new Dummy(), '_api_resource_class' => Dummy::class, '_api_collection_operation_name' => 'post']);
        $request->setMethod('POST');

        $eventProphecy = $this->prophesize(GetResponseEvent::class);
        $eventProphecy->getRequest()->willReturn($request);

        $listener = new DeserializeListener($serializerProphecy->reveal(), $serializerContextBuilderProphecy->reveal(), $formatsProviderProphecy->reveal(), $resourceMetadataFactoryProphecy->reveal());
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
        $context = $populateObject ? [AbstractNormalizer::OBJECT_TO_POPULATE => $populateObject] : [];
        $context['input'] = ['class' => 'Foo'];
        $context['output'] = ['class' => 'Foo'];
        $context['resource_class'] = 'Foo';
        $serializerProphecy->deserialize('{}', 'Foo', 'json', $context)->willReturn($result)->shouldBeCalled();

        $formatsProviderProphecy = $this->prophesize(FormatsProviderInterface::class);
        $formatsProviderProphecy->getFormatsFromAttributes(Argument::type('array'))->willReturn(self::FORMATS)->shouldBeCalled();

        $serializerContextBuilderProphecy = $this->prophesize(SerializerContextBuilderInterface::class);
        $serializerContextBuilderProphecy->createFromRequest(Argument::type(Request::class), false, Argument::type('array'))->willReturn(['input' => ['class' => 'Foo'], 'output' => ['class' => 'Foo'], 'resource_class' => 'Foo'])->shouldBeCalled();

        $listener = new DeserializeListener($serializerProphecy->reveal(), $serializerContextBuilderProphecy->reveal(), $formatsProviderProphecy->reveal());
        $listener->onKernelRequest($eventProphecy->reveal());
    }

    /**
     * @dataProvider methodProvider
     */
    public function testDeserializeResourceClassSupportedFormat(string $method, bool $populateObject)
    {
        $result = $populateObject ? new \stdClass() : null;
        $eventProphecy = $this->prophesize(GetResponseEvent::class);

        $request = new Request([], [], ['data' => $result, '_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'post'], [], [], [], '{}');
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

        $formatsProviderProphecy = $this->prophesize(FormatsProviderInterface::class);
        $formatsProviderProphecy->getFormatsFromAttributes([
            'resource_class' => 'Foo',
            'collection_operation_name' => 'post',
            'receive' => true,
            'respond' => true,
            'persist' => true,
        ])->willReturn(self::FORMATS)->shouldBeCalled();

        $listener = new DeserializeListener($serializerProphecy->reveal(), $serializerContextBuilderProphecy->reveal(), $formatsProviderProphecy->reveal());

        $listener->onKernelRequest($eventProphecy->reveal());
    }

    public function methodProvider()
    {
        return [['POST', false], ['PUT', true]];
    }

    public function testContentNegotiation()
    {
        $eventProphecy = $this->prophesize(GetResponseEvent::class);

        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'post'], [], [], [], '{}');
        $request->setMethod('POST');
        $request->headers->set('Content-Type', 'text/xml');
        $request->setFormat('xml', 'text/xml'); // Workaround to avoid weird behaviors
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();

        $context = ['input' => ['class' => 'Foo'], 'output' => ['class' => 'Foo'], 'resource_class' => 'Foo'];

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->deserialize('{}', 'Foo', 'xml', $context)->willReturn(new \stdClass())->shouldBeCalled();

        $serializerContextBuilderProphecy = $this->prophesize(SerializerContextBuilderInterface::class);
        $serializerContextBuilderProphecy->createFromRequest(Argument::type(Request::class), false, Argument::type('array'))->willReturn($context)->shouldBeCalled();

        $formatsProviderProphecy = $this->prophesize(FormatsProviderInterface::class);
        $formatsProviderProphecy->getFormatsFromAttributes(Argument::type('array'))->willReturn(['jsonld' => ['application/ld+json'], 'xml' => ['text/xml']])->shouldBeCalled();

        $listener = new DeserializeListener(
            $serializerProphecy->reveal(),
            $serializerContextBuilderProphecy->reveal(),
            $formatsProviderProphecy->reveal()
        );
        $listener->onKernelRequest($eventProphecy->reveal());
    }

    public function testNotSupportedContentType()
    {
        $this->expectException(UnsupportedMediaTypeHttpException::class);
        $this->expectExceptionMessage('The content-type "application/rdf+xml" is not supported. Supported MIME types are "application/ld+json", "text/xml".');

        $eventProphecy = $this->prophesize(GetResponseEvent::class);

        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'post'], [], [], [], '{}');
        $request->setMethod('POST');
        $request->headers->set('Content-Type', 'application/rdf+xml');
        $request->setRequestFormat('xml');
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->deserialize(Argument::cetera())->shouldNotBeCalled();

        $serializerContextBuilderProphecy = $this->prophesize(SerializerContextBuilderInterface::class);
        $serializerContextBuilderProphecy->createFromRequest(Argument::type(Request::class), false, Argument::type('array'))->willReturn(['input' => ['class' => 'Foo'], 'output' => ['class' => 'Foo']]);

        $formatsProviderProphecy = $this->prophesize(FormatsProviderInterface::class);
        $formatsProviderProphecy->getFormatsFromAttributes(Argument::type('array'))->willReturn(['jsonld' => ['application/ld+json'], 'xml' => ['text/xml']])->shouldBeCalled();

        $listener = new DeserializeListener(
            $serializerProphecy->reveal(),
            $serializerContextBuilderProphecy->reveal(),
            $formatsProviderProphecy->reveal()
        );
        $listener->onKernelRequest($eventProphecy->reveal());
    }

    public function testNoContentType()
    {
        $this->expectException(UnsupportedMediaTypeHttpException::class);
        $this->expectExceptionMessage('The "Content-Type" header must exist.');

        $eventProphecy = $this->prophesize(GetResponseEvent::class);

        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'post'], [], [], [], '{}');
        $request->setMethod('POST');
        $request->setRequestFormat('unknown');
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->deserialize(Argument::cetera())->shouldNotBeCalled();

        $serializerContextBuilderProphecy = $this->prophesize(SerializerContextBuilderInterface::class);
        $serializerContextBuilderProphecy->createFromRequest(Argument::type(Request::class), false, Argument::type('array'))->willReturn(['input' => ['class' => 'Foo'], 'output' => ['class' => 'Foo']]);

        $formatsProviderProphecy = $this->prophesize(FormatsProviderInterface::class);
        $formatsProviderProphecy->getFormatsFromAttributes(Argument::type('array'))->willReturn(['jsonld' => ['application/ld+json'], 'xml' => ['text/xml']])->shouldBeCalled();

        $listener = new DeserializeListener(
            $serializerProphecy->reveal(),
            $serializerContextBuilderProphecy->reveal(),
            $formatsProviderProphecy->reveal()
        );
        $listener->onKernelRequest($eventProphecy->reveal());
    }

    /**
     * @group legacy
     * @expectedDeprecation Passing an array or an instance of "ApiPlatform\Core\Api\FormatsProviderInterface" as 3rd parameter of the constructor of "ApiPlatform\Core\EventListener\DeserializeListener" is deprecated since API Platform 2.5, pass an instance of "ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface" instead
     */
    public function testLegacyFormatsParameter()
    {
        $serializerProphecy = $this->prophesize(SerializerInterface::class);

        $serializerContextBuilderProphecy = $this->prophesize(SerializerContextBuilderInterface::class);

        new DeserializeListener(
            $serializerProphecy->reveal(),
            $serializerContextBuilderProphecy->reveal(),
            self::FORMATS
        );
    }
}
