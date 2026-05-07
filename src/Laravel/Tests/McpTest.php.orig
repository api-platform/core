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

namespace ApiPlatform\Laravel\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;
use Symfony\AI\McpBundle\McpBundle;
use Symfony\Component\HttpFoundation\Response;

class McpTest extends TestCase
{
    use RefreshDatabase;
    use WithWorkbench;

    private function isPsr17FactoryAvailable(): bool
    {
        try {
            if (!class_exists('Http\Discovery\Psr17FactoryDiscovery')) {
                return false;
            }

            \Http\Discovery\Psr17FactoryDiscovery::findServerRequestFactory();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @param array<string, mixed> $arguments
     *
     * @return TestResponse<Response>
     */
    private function callTool(string $sessionId, string $toolName, array $arguments = []): TestResponse
    {
        return $this->postJson('/mcp', [
            'jsonrpc' => '2.0',
            'id' => 2,
            'method' => 'tools/call',
            'params' => [
                'name' => $toolName,
                'arguments' => $arguments,
            ],
        ], [
            'Accept' => 'application/json, text/event-stream',
            'Content-Type' => 'application/json',
            'mcp-session-id' => $sessionId,
        ]);
    }

    private function initializeMcpSession(): string
    {
        $response = $this->postJson('/mcp', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'initialize',
            'params' => [
                'protocolVersion' => '2024-11-05',
                'clientInfo' => [
                    'name' => 'ApiPlatform Test Suite',
                    'version' => '1.0',
                ],
                'capabilities' => [],
            ],
        ], [
            'Accept' => 'application/json, text/event-stream',
            'Content-Type' => 'application/json',
        ]);

        $response->assertStatus(200);

        return $response->headers->get('mcp-session-id');
    }

    public function testBasicProvider(): void
    {
        if (!class_exists(McpBundle::class)) {
            $this->markTestSkipped('MCP bundle is not installed');
        }

        if (!$this->isPsr17FactoryAvailable()) {
            $this->markTestSkipped('PSR-17 HTTP factory implementation not available (required for MCP)');
        }

        $sessionId = $this->initializeMcpSession();
        $response = $this->callTool($sessionId, 'get_book_info');

        $response->assertStatus(200);
        $result = $response->json();
        $this->assertArrayHasKey('result', $result);
        $content = $result['result']['content'][0]['text'] ?? null;
        $this->assertNotNull($content);
        $this->assertStringContainsString('API Platform Guide', $content);
        $this->assertStringContainsString('978-1234567890', $content);
    }

    public function testBasicProcessor(): void
    {
        if (!class_exists(McpBundle::class)) {
            $this->markTestSkipped('MCP bundle is not installed');
        }

        if (!$this->isPsr17FactoryAvailable()) {
            $this->markTestSkipped('PSR-17 HTTP factory implementation not available (required for MCP)');
        }

        $sessionId = $this->initializeMcpSession();
        $response = $this->callTool($sessionId, 'update_book_status', [
            'id' => null,
            'isbn' => '123',
            'title' => 'Test Book',
            'status' => 'pending',
        ]);

        $result = $response->json();
        if (isset($result['error'])) {
            $this->fail('MCP Error: '.json_encode($result['error']));
        }
        $response->assertStatus(200);
        $this->assertArrayHasKey('result', $result);
    }

    public function testCustomResultWithoutMetadata(): void
    {
        if (!class_exists(McpBundle::class)) {
            $this->markTestSkipped('MCP bundle is not installed');
        }

        if (!$this->isPsr17FactoryAvailable()) {
            $this->markTestSkipped('PSR-17 HTTP factory implementation not available (required for MCP)');
        }

        $sessionId = $this->initializeMcpSession();
        $response = $this->callTool($sessionId, 'custom_result', [
            'text' => 'Test content',
            'includeMetadata' => false,
            'name' => null,
            'email' => null,
            'age' => null,
        ]);

        $result = $response->json();
        if (isset($result['error'])) {
            $this->fail('MCP Error: '.json_encode($result['error']));
        }
        $response->assertStatus(200);
        $this->assertArrayHasKey('result', $result);
        $content = $result['result']['content'][0]['text'] ?? null;
        $this->assertEquals('Custom result: Test content', $content);
        $this->assertNull($result['result']['_meta'] ?? null);
    }

    public function testCustomResultWithMetadata(): void
    {
        if (!class_exists(McpBundle::class)) {
            $this->markTestSkipped('MCP bundle is not installed');
        }

        if (!$this->isPsr17FactoryAvailable()) {
            $this->markTestSkipped('PSR-17 HTTP factory implementation not available (required for MCP)');
        }

        $sessionId = $this->initializeMcpSession();
        $response = $this->callTool($sessionId, 'custom_result', [
            'text' => 'Test with metadata',
            'includeMetadata' => true,
            'name' => null,
            'email' => null,
            'age' => null,
        ]);

        $response->assertStatus(200);
        $result = $response->json();
        $this->assertArrayHasKey('result', $result);
        $content = $result['result']['content'][0]['text'] ?? null;
        $this->assertEquals('Custom result: Test with metadata', $content);
        $hasMeta = isset($result['result']['_meta']) || isset($result['result']['meta']) || isset($result['result']['structuredContent']);
        $this->assertTrue($hasMeta, 'No metadata found in: '.json_encode(array_keys($result['result'])));
    }

    public function testValidationFailure(): void
    {
        if (!class_exists(McpBundle::class)) {
            $this->markTestSkipped('MCP bundle is not installed');
        }

        if (!$this->isPsr17FactoryAvailable()) {
            $this->markTestSkipped('PSR-17 HTTP factory implementation not available (required for MCP)');
        }

        $sessionId = $this->initializeMcpSession();
        $response = $this->callTool($sessionId, 'validate_input', [
            'name' => 'ab',
            'email' => 'invalid-email',
            'age' => -5,
            'text' => null,
            'includeMetadata' => null,
        ]);

        $result = $response->json();
        if (422 === $response->getStatusCode()) {
            $this->assertArrayHasKey('error', $result);
        } else {
            $response->assertStatus(200);
        }
    }

    public function testValidationSuccess(): void
    {
        if (!class_exists(McpBundle::class)) {
            $this->markTestSkipped('MCP bundle is not installed');
        }

        if (!$this->isPsr17FactoryAvailable()) {
            $this->markTestSkipped('PSR-17 HTTP factory implementation not available (required for MCP)');
        }

        $sessionId = $this->initializeMcpSession();
        $response = $this->callTool($sessionId, 'validate_input', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'age' => 30,
            'text' => null,
            'includeMetadata' => null,
        ]);

        $response->assertStatus(200);
        $result = $response->json();
        $this->assertArrayHasKey('result', $result);
        $content = $result['result']['content'][0]['text'] ?? null;
        $this->assertNotNull($content);
        $this->assertStringContainsString('Valid: John Doe', $content);
    }

    public function testMarkdownWithoutCodeBlock(): void
    {
        if (!class_exists(McpBundle::class)) {
            $this->markTestSkipped('MCP bundle is not installed');
        }

        if (!$this->isPsr17FactoryAvailable()) {
            $this->markTestSkipped('PSR-17 HTTP factory implementation not available (required for MCP)');
        }

        $sessionId = $this->initializeMcpSession();
        $response = $this->callTool($sessionId, 'generate_markdown', [
            'title' => 'API Platform Guide',
            'content' => 'This is a comprehensive guide to using API Platform.',
            'includeCodeBlock' => false,
        ]);

        $response->assertStatus(200);
        $result = $response->json();
        $this->assertArrayHasKey('result', $result);
        $content = $result['result']['content'][0]['text'] ?? null;
        $this->assertNotNull($content, 'No text content in result');
        $this->assertStringContainsString('# API Platform Guide', $content);
        $this->assertStringContainsString('This is a comprehensive guide to using API Platform.', $content);
        $this->assertStringNotContainsString('```', $content);
        $this->assertNull($result['result']['_meta'] ?? null);
        $this->assertArrayNotHasKey('structuredContent', $result['result']);
    }

    public function testMarkdownWithCodeBlock(): void
    {
        if (!class_exists(McpBundle::class)) {
            $this->markTestSkipped('MCP bundle is not installed');
        }

        if (!$this->isPsr17FactoryAvailable()) {
            $this->markTestSkipped('PSR-17 HTTP factory implementation not available (required for MCP)');
        }

        $sessionId = $this->initializeMcpSession();
        $response = $this->callTool($sessionId, 'generate_markdown', [
            'title' => 'Code Example',
            'content' => 'Here is how to use the feature:',
            'includeCodeBlock' => true,
        ]);

        $response->assertStatus(200);
        $result = $response->json();
        $this->assertArrayHasKey('result', $result);
        $content = $result['result']['content'][0]['text'] ?? null;
        $this->assertNotNull($content);
        $this->assertStringContainsString('# Code Example', $content);
        $this->assertStringContainsString('Here is how to use the feature:', $content);
        $this->assertStringContainsString('```php', $content);
        $this->assertStringContainsString("echo 'Hello, World!';", $content);
        $this->assertStringContainsString('```', $content);
        $this->assertNull($result['result']['_meta'] ?? null);
        $this->assertArrayNotHasKey('structuredContent', $result['result']);
    }

    public function testToolsList(): void
    {
        if (!class_exists(McpBundle::class)) {
            $this->markTestSkipped('MCP bundle is not installed');
        }

        if (!$this->isPsr17FactoryAvailable()) {
            $this->markTestSkipped('PSR-17 HTTP factory implementation not available (required for MCP)');
        }

        $sessionId = $this->initializeMcpSession();
        $response = $this->postJson('/mcp', [
            'jsonrpc' => '2.0',
            'id' => 2,
            'method' => 'tools/list',
        ], [
            'Accept' => 'application/json, text/event-stream',
            'Content-Type' => 'application/json',
            'mcp-session-id' => $sessionId,
        ]);

        $data = $response->json();
        $this->assertArrayHasKey('result', $data);
        $this->assertArrayHasKey('tools', $data['result']);

        $tools = $data['result']['tools'];
        $toolNames = array_column($tools, 'name');

        $this->assertContains('get_book_info', $toolNames);
        $this->assertContains('update_book_status', $toolNames);
        $this->assertContains('custom_result', $toolNames);
        $this->assertContains('validate_input', $toolNames);
        $this->assertContains('generate_markdown', $toolNames);
        $this->assertContains('process_message', $toolNames);

        foreach ($tools as $tool) {
            $this->assertArrayHasKey('name', $tool);
            $this->assertArrayHasKey('inputSchema', $tool);
            $this->assertEquals('object', $tool['inputSchema']['type']);
        }

        $response->assertStatus(200);
    }

    public function testMcpToolAttribute(): void
    {
        if (!class_exists(McpBundle::class)) {
            $this->markTestSkipped('MCP bundle is not installed');
        }

        if (!$this->isPsr17FactoryAvailable()) {
            $this->markTestSkipped('PSR-17 HTTP factory implementation not available (required for MCP)');
        }

        $sessionId = $this->initializeMcpSession();
        $response = $this->postJson('/mcp', [
            'jsonrpc' => '2.0',
            'id' => 2,
            'method' => 'tools/list',
        ], [
            'Accept' => 'application/json, text/event-stream',
            'Content-Type' => 'application/json',
            'mcp-session-id' => $sessionId,
        ]);

        $data = $response->json();
        $tools = $data['result']['tools'];
        $processMessageTool = null;
        foreach ($tools as $tool) {
            if ('process_message' === $tool['name']) {
                $processMessageTool = $tool;
                break;
            }
        }

        $this->assertNotNull($processMessageTool);
        $this->assertEquals('process_message', $processMessageTool['name']);
        $this->assertEquals('Process a message with priority', $processMessageTool['description'] ?? null);
        $this->assertArrayHasKey('inputSchema', $processMessageTool);
        $this->assertEquals('object', $processMessageTool['inputSchema']['type']);

        $response = $this->postJson('/mcp', [
            'jsonrpc' => '2.0',
            'id' => 3,
            'method' => 'tools/call',
            'params' => [
                'name' => 'process_message',
                'arguments' => [
                    'message' => 'Hello World',
                    'priority' => 5,
                ],
            ],
        ], [
            'Accept' => 'application/json, text/event-stream',
            'Content-Type' => 'application/json',
            'mcp-session-id' => $sessionId,
        ]);

        $response->assertStatus(200);
        $result = $response->json();
        $this->assertArrayHasKey('result', $result);
    }
}
