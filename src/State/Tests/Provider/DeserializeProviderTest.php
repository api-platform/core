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

namespace ApiPlatform\State\Tests\Provider;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\Provider\DeserializeProvider;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\State\SerializerContextBuilderInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

class DeserializeProviderTest extends TestCase
{
    public function testDeserialize(): void
    {
        $objectToPopulate = new \stdClass();
        $serializerContext = [];
        $operation = new Post(deserialize: true, class: 'Test');
        $decorated = $this->createStub(ProviderInterface::class);
        $decorated->method('provide')->willReturn($objectToPopulate);

        $serializerContextBuilder = $this->createMock(SerializerContextBuilderInterface::class);
        $serializerContextBuilder->expects($this->once())->method('createFromRequest')->willReturn($serializerContext);
        $serializer = $this->createMock(SerializerInterface::class);
        $serializer->expects($this->once())->method('deserialize')->with('test', 'Test', 'format', ['uri_variables' => ['id' => 1], AbstractNormalizer::OBJECT_TO_POPULATE => $objectToPopulate] + $serializerContext)->willReturn(new \stdClass());

        $provider = new DeserializeProvider($decorated, $serializer, $serializerContextBuilder);
        $request = new Request(content: 'test');
        $request->headers->set('CONTENT_TYPE', 'ok');
        $request->attributes->set('input_format', 'format');
        $provider->provide($operation, ['id' => 1], ['request' => $request]);
    }

    public function testDeserializeNoContentType(): void
    {
        $this->expectException(UnsupportedMediaTypeHttpException::class);
        $operation = new Get(deserialize: true, class: 'Test');
        $decorated = $this->createStub(ProviderInterface::class);
        $decorated->method('provide')->willReturn(null);

        $serializerContextBuilder = $this->createMock(SerializerContextBuilderInterface::class);
        $serializer = $this->createMock(SerializerInterface::class);

        $provider = new DeserializeProvider($decorated, $serializer, $serializerContextBuilder);
        $request = new Request(content: 'test');
        $request->attributes->set('input_format', 'format');
        $provider->provide($operation, ['id' => 1], ['request' => $request]);
    }

    public function testDeserializeNoInput(): void
    {
        $this->expectException(UnsupportedMediaTypeHttpException::class);
        $operation = new Get(deserialize: true, class: 'Test');
        $decorated = $this->createStub(ProviderInterface::class);
        $decorated->method('provide')->willReturn(null);

        $serializerContextBuilder = $this->createMock(SerializerContextBuilderInterface::class);
        $serializer = $this->createMock(SerializerInterface::class);

        $provider = new DeserializeProvider($decorated, $serializer, $serializerContextBuilder);
        $request = new Request(content: 'test');
        $request->headers->set('CONTENT_TYPE', 'ok');
        $provider->provide($operation, ['id' => 1], ['request' => $request]);
    }

    public function testDeserializeWithContextClass(): void
    {
        $serializerContext = ['deserializer_type' => 'Test'];
        $operation = new Get(deserialize: true);
        $decorated = $this->createStub(ProviderInterface::class);
        $decorated->method('provide')->willReturn(null);

        $serializerContextBuilder = $this->createMock(SerializerContextBuilderInterface::class);
        $serializerContextBuilder->expects($this->once())->method('createFromRequest')->willReturn($serializerContext);
        $serializer = $this->createMock(SerializerInterface::class);
        $serializer->expects($this->once())->method('deserialize')->with('test', 'Test', 'format', ['uri_variables' => ['id' => 1]] + $serializerContext)->willReturn(new \stdClass());

        $provider = new DeserializeProvider($decorated, $serializer, $serializerContextBuilder);
        $request = new Request(content: 'test');
        $request->headers->set('CONTENT_TYPE', 'ok');
        $request->attributes->set('input_format', 'format');
        $provider->provide($operation, ['id' => 1], ['request' => $request]);
    }

    public function testRequestWithEmptyContentType(): void
    {
        $expectedResult = new \stdClass();
        $decorated = $this->createMock(ProviderInterface::class);
        $decorated->method('provide')->willReturn($expectedResult);

        $serializer = $this->createStub(SerializerInterface::class);
        $serializerContextBuilder = $this->createStub(SerializerContextBuilderInterface::class);

        $provider = new DeserializeProvider($decorated, $serializer, $serializerContextBuilder);

        // in Symfony (at least up to 7.0.2, 6.4.2, 6.3.11, 5.4.34), a request
        // without a content-type and content-length header will result in the
        // variables set to an empty string, not null

        $request = new Request(
            server: [
                'REQUEST_METHOD' => 'POST',
                'REQUEST_URI' => '/',
                'CONTENT_TYPE' => '',
                'CONTENT_LENGTH' => '',
            ],
            content: ''
        );

        $operation = new Post(deserialize: true);
        $context = ['request' => $request];

        $this->expectException(UnsupportedMediaTypeHttpException::class);
        $provider->provide($operation, [], $context);
    }
}
