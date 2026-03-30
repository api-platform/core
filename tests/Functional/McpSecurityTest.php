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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\McpSecuredReference;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\McpSecuredTools;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\AI\McpBundle\McpBundle;

/**
 * The security attributes of an McpTool must be enforced whatever the value of
 * "use_symfony_listeners": the kernel listeners never run for a JSON-RPC tool call, so the MCP
 * handler drives the state pipeline itself and has to carry the access checkers.
 */
final class McpSecurityTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    private const ADMIN_AUTH = 'Basic YWRtaW46a2l0dGVu';

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [McpSecuredTools::class, McpSecuredReference::class];
    }

    /**
     * @return iterable<string, array{string, array<string, mixed>}>
     */
    public static function securedToolProvider(): iterable
    {
        yield 'security' => ['secured_tool', ['text' => 'hello', 'reference' => null]];
        yield 'securityPostDenormalize' => ['secured_post_denormalize_tool', ['text' => 'hello', 'reference' => null]];
        yield 'securityPostValidation' => ['secured_post_validation_tool', ['text' => 'hello', 'reference' => null]];
        yield 'uriVariable security' => ['secured_uri_variable_tool', ['text' => 'hello', 'reference' => 'abc']];
    }

    /**
     * @param array<string, mixed> $arguments
     */
    #[DataProvider('securedToolProvider')]
    public function testAnonymousCannotCallSecuredTool(string $tool, array $arguments): void
    {
        $this->skipUnlessMcpIsAvailable();

        $client = self::createClient();
        $result = $this->callTool($client, $this->initializeMcpSession($client), $tool, $arguments)->toArray(false);

        self::assertArrayNotHasKey('result', $result, \sprintf('Tool "%s" ran for an anonymous caller.', $tool));
        self::assertSame('Access Denied.', $result['error']['message'] ?? null);
    }

    /**
     * @param array<string, mixed> $arguments
     */
    #[DataProvider('securedToolProvider')]
    public function testAdminCanCallSecuredTool(string $tool, array $arguments): void
    {
        $this->skipUnlessMcpIsAvailable();

        $client = self::createClient();
        $sessionId = $this->initializeMcpSession($client);
        $result = $this->callTool($client, $sessionId, $tool, $arguments, ['Authorization' => self::ADMIN_AUTH])->toArray(false);

        self::assertArrayNotHasKey('error', $result, 'MCP error: '.json_encode($result['error'] ?? null));
        self::assertStringContainsString('Secured: hello', $result['result']['content'][0]['text'] ?? '');
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
     * @param array<string, mixed>  $arguments
     * @param array<string, string> $headers
     */
    private function callTool($client, string $sessionId, string $toolName, array $arguments = [], array $headers = [])
    {
        return $client->request('POST', '/mcp', [
            'headers' => $headers + [
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
