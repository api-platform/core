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

namespace ApiPlatform\Tests\Functional;

use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\McpFormatListTool;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\McpFormatTool;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Symfony\AI\McpBundle\McpBundle;

/**
 * Reproducer: the format configured for an MCP operation has no effect on its output.
 *
 * `ApiPlatform\Mcp\State\StructuredContentProcessor::process()` serializes with
 *
 *     $format = $request->getRequestFormat('') ?: 'jsonld';
 *
 * and never reads `$operation->getOutputFormats()`, although `$operation` is in scope and
 * `FormatsResourceMetadataCollectionFactory` has populated it. The MCP route carries no
 * `_format` placeholder or default either, so `getRequestFormat()` is always empty and
 * the expression is a constant `'jsonld'` on every call.
 *
 * This also covers `api_platform.mcp.format`: that option is implemented by writing the
 * operation's own formats, so it travels through the very same getter.
 */
class McpFormatTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [McpFormatTool::class, McpFormatListTool::class];
    }

    public function testAFormatDeclaredAsAListIsResolved(): void
    {
        $this->skipUnlessMcpIsUsable();

        $client = self::createClient();

        /** @var ResourceMetadataCollectionFactoryInterface $factory */
        $factory = self::getContainer()->get('api_platform.metadata.resource.metadata_collection_factory');

        $outputFormats = null;
        foreach ($factory->create(McpFormatListTool::class) as $resource) {
            foreach ($resource->getMcp() ?? [] as $name => $operation) {
                if ('format_message_list' === $name) {
                    $outputFormats = $operation->getOutputFormats();
                }
            }
        }

        self::assertSame(
            ['json' => ['application/json']],
            $outputFormats,
            'A list form such as ["json"] must be resolved against api_platform.formats, '.
            'exactly as normalizeFormats() does for every HTTP operation.',
        );

        $sessionId = $this->initializeMcpSession($client);
        $res = $this->callTool($client, $sessionId, 'format_message_list', ['message' => 'hello']);

        self::assertResponseIsSuccessful();
        $result = $res->toArray(false);
        self::assertArrayNotHasKey('error', $result, 'MCP error: '.json_encode($result['error'] ?? null));
        self::assertStringNotContainsString('@context', (string) ($result['result']['content'][0]['text'] ?? ''));
    }

    public function testTheOperationMetadataCarriesTheDeclaredFormat(): void
    {
        $this->skipUnlessMcpIsUsable();

        self::createClient();

        /** @var ResourceMetadataCollectionFactoryInterface $factory */
        $factory = self::getContainer()->get('api_platform.metadata.resource.metadata_collection_factory');

        $outputFormats = null;
        foreach ($factory->create(McpFormatTool::class) as $resource) {
            foreach ($resource->getMcp() ?? [] as $name => $operation) {
                if ('format_message' === $name) {
                    $outputFormats = $operation->getOutputFormats();
                }
            }
        }

        self::assertSame(
            ['json' => ['application/json']],
            $outputFormats,
            'Precondition: the metadata layer honours the declared format.',
        );
    }

    public function testTheOutputHonoursTheDeclaredFormat(): void
    {
        $this->skipUnlessMcpIsUsable();

        $client = self::createClient();
        $sessionId = $this->initializeMcpSession($client);

        $res = $this->callTool($client, $sessionId, 'format_message', ['message' => 'hello']);

        self::assertResponseIsSuccessful();
        $result = $res->toArray(false);
        self::assertArrayNotHasKey('error', $result, 'MCP error: '.json_encode($result['error'] ?? null));

        $text = $result['result']['content'][0]['text'] ?? null;
        self::assertNotNull($text);

        self::assertStringNotContainsString(
            '@context',
            $text,
            'The tool declares the "json" output format but the payload is JSON-LD: '.
            'StructuredContentProcessor ignores $operation->getOutputFormats().',
        );
    }

    public function testStructuredContentHonoursTheDeclaredFormatToo(): void
    {
        $this->skipUnlessMcpIsUsable();

        $client = self::createClient();
        $sessionId = $this->initializeMcpSession($client);

        $res = $this->callTool($client, $sessionId, 'format_message', ['message' => 'hello']);

        $structured = $res->toArray(false)['result']['structuredContent'] ?? [];

        self::assertArrayNotHasKey(
            '@context',
            $structured,
            'structuredContent carries the JSON-LD envelope as well: it is normalized with the same format.',
        );
    }

    private function skipUnlessMcpIsUsable(): void
    {
        if (!class_exists(McpBundle::class)) {
            $this->markTestSkipped('MCP bundle is not installed');
        }

        try {
            if (!class_exists('Http\Discovery\Psr17FactoryDiscovery')) {
                $this->markTestSkipped('PSR-17 HTTP factory implementation not available (required for MCP)');
            }

            \Http\Discovery\Psr17FactoryDiscovery::findServerRequestFactory();
        } catch (\Throwable) {
            $this->markTestSkipped('PSR-17 HTTP factory implementation not available (required for MCP)');
        }
    }

    private function initializeMcpSession($client): string
    {
        $res = $client->request('POST', '/mcp', [
            'headers' => [
                'Accept' => 'application/json, text/event-stream',
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'jsonrpc' => '2.0',
                'id' => 1,
                'method' => 'initialize',
                'params' => [
                    'protocolVersion' => '2024-11-05',
                    'clientInfo' => ['name' => 'ApiPlatform Test Suite', 'version' => '1.0'],
                    'capabilities' => [],
                ],
            ],
        ]);
        self::assertResponseIsSuccessful();

        return $res->getHeaders()['mcp-session-id'][0];
    }

    /**
     * @param array<string, mixed> $arguments
     */
    private function callTool($client, string $sessionId, string $toolName, array $arguments = [])
    {
        return $client->request('POST', '/mcp', [
            'headers' => [
                'Accept' => 'application/json, text/event-stream',
                'Content-Type' => 'application/json',
                'mcp-session-id' => $sessionId,
            ],
            'json' => [
                'jsonrpc' => '2.0',
                'id' => 2,
                'method' => 'tools/call',
                'params' => [
                    'name' => $toolName,
                    'arguments' => $arguments,
                ],
            ],
        ]);
    }
}
