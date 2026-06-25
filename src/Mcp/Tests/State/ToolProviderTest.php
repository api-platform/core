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

namespace ApiPlatform\Mcp\Tests\State;

use ApiPlatform\Mcp\State\ToolProvider;
use ApiPlatform\Metadata\Post;
use PHPUnit\Framework\TestCase;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

class ToolProviderTest extends TestCase
{
    public function testProvideReturnsNullWithoutMcpRequest(): void
    {
        $objectMapper = $this->createMock(ObjectMapperInterface::class);
        $objectMapper->expects($this->never())->method('map');
        $operation = new Post(class: \stdClass::class);

        $provider = new ToolProvider($objectMapper);
        $this->assertNull($provider->provide($operation, [], []));
    }

    public function testProvideMapsMcpDataToInputClass(): void
    {
        $mapped = new ToolProviderInputDummy();
        $operation = new Post(class: ToolProviderInputDummy::class);

        $objectMapper = $this->createMock(ObjectMapperInterface::class);
        $objectMapper->expects($this->once())
            ->method('map')
            ->with($this->isInstanceOf(\stdClass::class), ToolProviderInputDummy::class)
            ->willReturn($mapped);

        $provider = new ToolProvider($objectMapper);
        $result = $provider->provide($operation, [], ['mcp_request' => true, 'mcp_data' => ['name' => 'foo']]);

        $this->assertSame($mapped, $result);
    }

    /**
     * Regression test: input explicitly disabled (`input: false`) must not crash calling
     * `map($data, null)` — there's no target class to map into, so the raw data is returned as-is.
     */
    public function testProvideReturnsRawDataWhenInputDisabled(): void
    {
        $operation = new Post(class: \stdClass::class, input: false);

        $objectMapper = $this->createMock(ObjectMapperInterface::class);
        $objectMapper->expects($this->never())->method('map');

        $provider = new ToolProvider($objectMapper);
        $result = $provider->provide($operation, [], ['mcp_request' => true, 'mcp_data' => ['name' => 'foo']]);

        $this->assertInstanceOf(\stdClass::class, $result);
        $this->assertSame('foo', $result->name);
    }
}

class ToolProviderInputDummy
{
    public string $name = '';
}
