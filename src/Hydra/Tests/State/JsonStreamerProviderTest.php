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

namespace ApiPlatform\Hydra\Tests\State;

use ApiPlatform\Hydra\State\JsonStreamerProvider;
use ApiPlatform\Metadata\Get;
use ApiPlatform\State\ProviderInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\JsonStreamer\StreamReaderInterface;
use Symfony\Component\TypeInfo\Type;

class JsonStreamerProviderTest extends TestCase
{
    public function testProvideReadsJsonLdContentIntoInputClass(): void
    {
        $data = new \stdClass();
        $operation = new Get(class: \stdClass::class, jsonStream: true, deserialize: true);
        $request = new Request(content: '{}');
        $request->attributes->set('input_format', 'jsonld');

        $jsonStreamReader = $this->createMock(StreamReaderInterface::class);
        $jsonStreamReader->expects($this->once())
            ->method('read')
            ->with($this->isType('resource'), $this->equalTo(Type::object(\stdClass::class)))
            ->willReturn($data);

        $decorated = $this->createMock(ProviderInterface::class);
        $decorated->method('provide')->willReturn(null);

        $provider = new JsonStreamerProvider($decorated, $jsonStreamReader);
        $result = $provider->provide($operation, [], ['request' => $request]);

        $this->assertSame($data, $result);
        $this->assertTrue($request->attributes->get('deserialized'));
    }

    public function testProvideBypassesWhenNotJsonLdFormat(): void
    {
        $data = new \stdClass();
        $operation = new Get(class: \stdClass::class, jsonStream: true, deserialize: true);
        $request = new Request();
        $request->attributes->set('input_format', 'json');
        $request->attributes->set('data', $data);

        $jsonStreamReader = $this->createMock(StreamReaderInterface::class);
        $jsonStreamReader->expects($this->never())->method('read');

        $decorated = $this->createMock(ProviderInterface::class);
        $decorated->method('provide')->willReturn($data);

        $provider = new JsonStreamerProvider($decorated, $jsonStreamReader);
        $result = $provider->provide($operation, [], ['request' => $request]);

        $this->assertSame($data, $result);
    }

    /**
     * Regression test: input explicitly disabled (`input: false`) combined with an explicit
     * `deserialize: true` (a contradictory config) must not crash `Type::object(null)` — it should
     * bypass instead.
     */
    public function testProvideBypassesWhenInputDisabledDespiteDeserializeTrue(): void
    {
        $data = new \stdClass();
        $operation = new Get(class: \stdClass::class, jsonStream: true, deserialize: true, input: false);
        $request = new Request(content: '{}');
        $request->attributes->set('input_format', 'jsonld');

        $jsonStreamReader = $this->createMock(StreamReaderInterface::class);
        $jsonStreamReader->expects($this->never())->method('read');

        $decorated = $this->createMock(ProviderInterface::class);
        $decorated->method('provide')->willReturn($data);

        $provider = new JsonStreamerProvider($decorated, $jsonStreamReader);
        $result = $provider->provide($operation, [], ['request' => $request]);

        $this->assertSame($data, $result);
    }
}
