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

use ApiPlatform\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\McpExceptionTools;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\AI\McpBundle\McpBundle;

/**
 * A caller-facing HTTP exception thrown by a state provider or processor must reach the client as a
 * JSON-RPC error carrying its own message, whether it implements API Platform's
 * HttpExceptionInterface or Symfony's: without that, the SDK replaces the message with its generic
 * "Internal server error." and the caller cannot tell a missing resource from a server fault.
 */
final class McpExceptionTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [McpExceptionTools::class];
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function symfonyHttpExceptionProvider(): iterable
    {
        yield 'provider' => ['symfony_not_found_provider_tool', 'Provider says this resource does not exist.'];
        yield 'processor' => ['symfony_not_found_processor_tool', 'Processor says this resource does not exist.'];
    }

    #[DataProvider('symfonyHttpExceptionProvider')]
    public function testSymfonyHttpExceptionMessageReachesTheCaller(string $tool, string $expectedMessage): void
    {
        $this->skipUnlessMcpIsAvailable();

        $client = self::createClient();
        $result = $this->callTool($client, $this->initializeMcpSession($client), $tool, ['text' => 'hello'])->toArray(false);

        self::assertArrayNotHasKey('result', $result, \sprintf('Tool "%s" returned a result instead of an error.', $tool));
        self::assertSame($expectedMessage, $result['error']['message'] ?? null);
    }

    private function skipUnlessMcpIsAvailable(): void
    {
        if (!class_exists(McpBundle::class)) {
            $this->markTestSkipped('MCP bundle is not installed');
        }

        if ($this->isMongoDB()) {
            $this->markTestSkipped('MCP is not supported with MongoDB');
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
