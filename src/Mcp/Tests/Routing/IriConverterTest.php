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

namespace ApiPlatform\Mcp\Tests\Routing;

use ApiPlatform\Mcp\Routing\IriConverter;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\McpResource;
use ApiPlatform\Metadata\McpTool;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class IriConverterTest extends TestCase
{
    public function testGetResourceFromIriIsDelegated(): void
    {
        $expected = new \stdClass();
        $operation = new Get();

        $inner = $this->createMock(IriConverterInterface::class);
        $inner->expects($this->once())
            ->method('getResourceFromIri')
            ->with('/dummies/1', ['foo' => 'bar'], $operation)
            ->willReturn($expected);

        $iriConverter = new IriConverter($inner);

        $this->assertSame($expected, $iriConverter->getResourceFromIri('/dummies/1', ['foo' => 'bar'], $operation));
    }

    /**
     * MCP operations are not routed, so they have no IRI to expose: the decorated
     * converter must not be asked for one.
     */
    #[DataProvider('provideMcpOperations')]
    public function testGetIriFromResourceReturnsNullForMcpOperations(HttpOperation $operation): void
    {
        $inner = $this->createMock(IriConverterInterface::class);
        $inner->expects($this->never())->method('getIriFromResource');

        $iriConverter = new IriConverter($inner);

        $this->assertNull($iriConverter->getIriFromResource(new \stdClass(), UrlGeneratorInterface::ABS_PATH, $operation));
    }

    /**
     * @return iterable<string, array{HttpOperation}>
     */
    public static function provideMcpOperations(): iterable
    {
        yield 'tool' => [new McpTool(name: 'createDummy', class: \stdClass::class)];
        yield 'resource' => [new McpResource(uri: 'dummy://docs', name: 'docs', class: \stdClass::class)];
    }

    /**
     * `item_uri_template` means an IRI was explicitly requested for a nested item,
     * so the decorated converter takes over even on an MCP operation.
     */
    #[DataProvider('provideMcpOperations')]
    public function testGetIriFromResourceIsDelegatedWhenAnItemUriTemplateIsGiven(HttpOperation $operation): void
    {
        $resource = new \stdClass();
        $context = ['item_uri_template' => '/dummies/{id}'];

        $inner = $this->createMock(IriConverterInterface::class);
        $inner->expects($this->once())
            ->method('getIriFromResource')
            ->with($resource, UrlGeneratorInterface::ABS_URL, $operation, $context)
            ->willReturn('/dummies/1');

        $iriConverter = new IriConverter($inner);

        $this->assertSame('/dummies/1', $iriConverter->getIriFromResource($resource, UrlGeneratorInterface::ABS_URL, $operation, $context));
    }

    /**
     * @return iterable<string, array{Operation|null}>
     */
    public static function provideNonMcpOperations(): iterable
    {
        yield 'http operation' => [new Get()];
        yield 'no operation' => [null];
    }

    #[DataProvider('provideNonMcpOperations')]
    public function testGetIriFromResourceIsDelegatedForNonMcpOperations(?Operation $operation): void
    {
        $resource = new \stdClass();

        $inner = $this->createMock(IriConverterInterface::class);
        $inner->expects($this->once())
            ->method('getIriFromResource')
            ->with($resource, UrlGeneratorInterface::ABS_PATH, $operation, [])
            ->willReturn('/dummies/1');

        $iriConverter = new IriConverter($inner);

        $this->assertSame('/dummies/1', $iriConverter->getIriFromResource($resource, UrlGeneratorInterface::ABS_PATH, $operation));
    }

    public function testGetIriFromResourceReturnsTheDecoratedNullResult(): void
    {
        $inner = $this->createMock(IriConverterInterface::class);
        $inner->expects($this->once())->method('getIriFromResource')->willReturn(null);

        $iriConverter = new IriConverter($inner);

        $this->assertNull($iriConverter->getIriFromResource(new \stdClass(), UrlGeneratorInterface::ABS_PATH, new Get()));
    }
}
