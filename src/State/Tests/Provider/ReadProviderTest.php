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
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\State\Provider\ReadProvider;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\State\SerializerContextBuilderInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ReadProviderTest extends TestCase
{
    public function testSetsSerializerContext(): void
    {
        $data = new \stdClass();
        $operation = new Get(read: true);
        $decorated = $this->createStub(ProviderInterface::class);
        $decorated->method('provide')->willReturn($data);
        $serializerContextBuilder = $this->createMock(SerializerContextBuilderInterface::class);
        $serializerContextBuilder->expects($this->once())->method('createFromRequest')->willReturn(['a']);
        $provider = new ReadProvider($decorated, $serializerContextBuilder);
        $request = new Request();
        $provider->provide($operation, ['id' => 1], ['request' => $request]);
        $this->assertEquals(['a'], $request->attributes->get('_api_normalization_context'));
    }

    public function testShouldReadWithOutputFalse(): void
    {
        $data = new \stdClass();
        $operation = new Get(read: true, output: false);
        $decorated = $this->createStub(ProviderInterface::class);
        $decorated->method('provide')->willReturn($data);
        $serializerContextBuilder = $this->createMock(SerializerContextBuilderInterface::class);
        $serializerContextBuilder->expects($this->once())->method('createFromRequest')->willReturn(['a']);
        $provider = new ReadProvider($decorated, $serializerContextBuilder);
        $request = new Request();
        $provider->provide($operation, ['id' => 1], ['request' => $request]);
        $this->assertEquals($data, $request->attributes->get('data'));
    }

    public function testWithoutRequest(): void
    {
        $operation = new GetCollection(read: true);
        $provider = $this->createMock(ProviderInterface::class);
        $provider->method('provide')->willReturn(['ok']);
        $serializerContextBuilder = $this->createMock(SerializerContextBuilderInterface::class);

        $readProvider = new ReadProvider($provider, $serializerContextBuilder);
        $this->assertEquals($readProvider->provide($operation), ['ok']);
    }

    public function testThrowOnNotFoundExplicitTrueThrowsForPost(): void
    {
        $operation = new Post(read: true, throwOnNotFound: true);
        $decorated = $this->createStub(ProviderInterface::class);
        $decorated->method('provide')->willReturn(null);

        $provider = new ReadProvider($decorated);
        $this->expectException(NotFoundHttpException::class);
        $provider->provide($operation, ['id' => 1], ['request' => new Request()]);
    }

    public function testThrowOnNotFoundExplicitFalseSkipsThrowForGet(): void
    {
        $operation = new Get(read: true, throwOnNotFound: false);
        $decorated = $this->createStub(ProviderInterface::class);
        $decorated->method('provide')->willReturn(null);

        $provider = new ReadProvider($decorated);
        $request = new Request();
        $this->assertNull($provider->provide($operation, ['id' => 1], ['request' => $request]));
        $this->assertNull($request->attributes->get('data'));
    }

    public function testThrowOnNotFoundDefaultThrowsForGet(): void
    {
        $operation = new Get(read: true);
        $decorated = $this->createStub(ProviderInterface::class);
        $decorated->method('provide')->willReturn(null);

        $provider = new ReadProvider($decorated);
        $this->expectException(NotFoundHttpException::class);
        $provider->provide($operation, ['id' => 1], ['request' => new Request()]);
    }

    public function testThrowOnNotFoundDefaultSkipsThrowForPost(): void
    {
        $operation = new Post(read: true);
        $decorated = $this->createStub(ProviderInterface::class);
        $decorated->method('provide')->willReturn(null);

        $provider = new ReadProvider($decorated);
        $request = new Request();
        $this->assertNull($provider->provide($operation, [], ['request' => $request]));
    }

    public function testThrowOnNotFoundDefaultThrowsForPutWithoutAllowCreate(): void
    {
        $operation = new Put(read: true);
        $decorated = $this->createStub(ProviderInterface::class);
        $decorated->method('provide')->willReturn(null);

        $provider = new ReadProvider($decorated);
        $this->expectException(NotFoundHttpException::class);
        $provider->provide($operation, ['id' => 1], ['request' => new Request()]);
    }

    public function testThrowOnNotFoundDefaultSkipsThrowForPutWithAllowCreate(): void
    {
        $operation = new Put(read: true, allowCreate: true);
        $decorated = $this->createStub(ProviderInterface::class);
        $decorated->method('provide')->willReturn(null);

        $provider = new ReadProvider($decorated);
        $request = new Request();
        $this->assertNull($provider->provide($operation, ['id' => 1], ['request' => $request]));
    }
}
