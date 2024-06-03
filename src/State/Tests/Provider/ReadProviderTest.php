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
use ApiPlatform\State\Provider\ReadProvider;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\State\SerializerContextBuilderInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

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
}
