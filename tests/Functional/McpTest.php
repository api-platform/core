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

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\McpResourceExample;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\McpToolAttribute;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\McpTools;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\McpWithMarkdown;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\McpBook;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Symfony\AI\McpBundle\McpBundle;

class McpTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [
            McpToolAttribute::class,
            McpBook::class,
            McpTools::class,
            McpWithMarkdown::class,
            McpResourceExample::class,
        ];
    }

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

    private function callTool($client, string $sessionId, string $toolName, array $arguments = []): mixed
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

    public function testBasicProvider(): void
    {
        if (!class_exists(McpBundle::class)) {
            $this->markTestSkipped('MCP bundle is not installed');
        }

        if ($this->isMongoDB()) {
            $this->markTestSkipped('MCP is not supported with MongoDB');
        }

        if (!$this->isPsr17FactoryAvailable()) {
            $this->markTestSkipped('PSR-17 HTTP factory implementation not available (required for MCP)');
        }

        $client = self::createClient();
        $sessionId = $this->initializeMcpSession($client);

        $res = $this->callTool($client, $sessionId, 'get_book_info');

        self::assertResponseIsSuccessful();
        $result = $res->toArray();
        self::assertArrayHasKey('result', $result);
        $content = $result['result']['content'][0]['text'] ?? null;
        self::assertNotNull($content);
        self::assertStringContainsString('API Platform Guide', $content);
        self::assertStringContainsString('978-1234567890', $content);
    }

    public function testBasicProcessor(): void
    {
        if (!class_exists(McpBundle::class)) {
            $this->markTestSkipped('MCP bundle is not installed');
        }

        if ($this->isMongoDB()) {
            $this->markTestSkipped('MCP is not supported with MongoDB');
        }

        if (!$this->isPsr17FactoryAvailable()) {
            $this->markTestSkipped('PSR-17 HTTP factory implementation not available (required for MCP)');
        }

        $client = self::createClient();
        $sessionId = $this->initializeMcpSession($client);

        $res = $this->callTool($client, $sessionId, 'update_book_status', [
            'id' => null,
            'isbn' => '123',
            'title' => 'Test Book',
            'status' => 'pending',
        ]);

        $result = $res->toArray(false);
        if (isset($result['error'])) {
            $this->fail('MCP Error: '.json_encode($result['error']));
        }
        self::assertResponseIsSuccessful();
        self::assertArrayHasKey('result', $result);
    }

    public function testCustomResultWithoutMetadata(): void
    {
        if (!class_exists(McpBundle::class)) {
            $this->markTestSkipped('MCP bundle is not installed');
        }

        if ($this->isMongoDB()) {
            $this->markTestSkipped('MCP is not supported with MongoDB');
        }

        if (!$this->isPsr17FactoryAvailable()) {
            $this->markTestSkipped('PSR-17 HTTP factory implementation not available (required for MCP)');
        }

        $client = self::createClient();
        $sessionId = $this->initializeMcpSession($client);

        $res = $this->callTool($client, $sessionId, 'custom_result', [
            'text' => 'Test content',
            'includeMetadata' => false,
            'name' => null,
            'email' => null,
            'age' => null,
        ]);

        $result = $res->toArray(false);
        if (isset($result['error'])) {
            $this->fail('MCP Error: '.json_encode($result['error']));
        }
        self::assertResponseIsSuccessful();
        self::assertArrayHasKey('result', $result);
        $content = $result['result']['content'][0]['text'] ?? null;
        self::assertEquals('Custom result: Test content', $content);
        self::assertNull($result['result']['_meta'] ?? null);
    }

    public function testCustomResultWithMetadata(): void
    {
        if (!class_exists(McpBundle::class)) {
            $this->markTestSkipped('MCP bundle is not installed');
        }

        if ($this->isMongoDB()) {
            $this->markTestSkipped('MCP is not supported with MongoDB');
        }

        if (!$this->isPsr17FactoryAvailable()) {
            $this->markTestSkipped('PSR-17 HTTP factory implementation not available (required for MCP)');
        }

        $client = self::createClient();
        $sessionId = $this->initializeMcpSession($client);

        $res = $this->callTool($client, $sessionId, 'custom_result', [
            'text' => 'Test with metadata',
            'includeMetadata' => true,
            'name' => null,
            'email' => null,
            'age' => null,
        ]);

        self::assertResponseIsSuccessful();
        $result = $res->toArray();
        self::assertArrayHasKey('result', $result);
        $content = $result['result']['content'][0]['text'] ?? null;
        self::assertEquals('Custom result: Test with metadata', $content);
        $hasMeta = isset($result['result']['_meta']) || isset($result['result']['meta']) || isset($result['result']['structuredContent']);
        self::assertTrue($hasMeta, 'No metadata found in: '.json_encode(array_keys($result['result'])));
    }

    public function testValidationFailure(): void
    {
        if (!class_exists(McpBundle::class)) {
            $this->markTestSkipped('MCP bundle is not installed');
        }

        if ($this->isMongoDB()) {
            $this->markTestSkipped('MCP is not supported with MongoDB');
        }

        if (!$this->isPsr17FactoryAvailable()) {
            $this->markTestSkipped('PSR-17 HTTP factory implementation not available (required for MCP)');
        }

        $client = self::createClient();
        $sessionId = $this->initializeMcpSession($client);

        $res = $this->callTool($client, $sessionId, 'validate_input', [
            'name' => 'ab',
            'email' => 'invalid-email',
            'age' => -5,
            'text' => null,
            'includeMetadata' => null,
        ]);

        $result = $res->toArray(false);
        if (422 === $res->getStatusCode()) {
            self::assertArrayHasKey('error', $result);
        } else {
            self::assertResponseIsSuccessful();
        }
    }

    public function testValidationSuccess(): void
    {
        if (!class_exists(McpBundle::class)) {
            $this->markTestSkipped('MCP bundle is not installed');
        }

        if ($this->isMongoDB()) {
            $this->markTestSkipped('MCP is not supported with MongoDB');
        }

        if (!$this->isPsr17FactoryAvailable()) {
            $this->markTestSkipped('PSR-17 HTTP factory implementation not available (required for MCP)');
        }

        $client = self::createClient();
        $sessionId = $this->initializeMcpSession($client);

        $res = $this->callTool($client, $sessionId, 'validate_input', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'age' => 30,
            'text' => null,
            'includeMetadata' => null,
        ]);

        self::assertResponseIsSuccessful();
        $result = $res->toArray();
        self::assertArrayHasKey('result', $result);
        $content = $result['result']['content'][0]['text'] ?? null;
        self::assertNotNull($content);
        self::assertStringContainsString('Valid: John Doe', $content);
    }

    public function testMarkdownWithoutCodeBlock(): void
    {
        if (!class_exists(McpBundle::class)) {
            $this->markTestSkipped('MCP bundle is not installed');
        }

        if ($this->isMongoDB()) {
            $this->markTestSkipped('MCP is not supported with MongoDB');
        }

        if (!$this->isPsr17FactoryAvailable()) {
            $this->markTestSkipped('PSR-17 HTTP factory implementation not available (required for MCP)');
        }

        $client = self::createClient();
        $sessionId = $this->initializeMcpSession($client);

        $res = $this->callTool($client, $sessionId, 'generate_markdown', [
            'title' => 'API Platform Guide',
            'content' => 'This is a comprehensive guide to using API Platform.',
            'includeCodeBlock' => false,
        ]);

        self::assertResponseIsSuccessful();
        $result = $res->toArray();
        self::assertArrayHasKey('result', $result);
        $content = $result['result']['content'][0]['text'] ?? null;
        self::assertNotNull($content, 'No text content in result');
        self::assertStringContainsString('# API Platform Guide', $content);
        self::assertStringContainsString('This is a comprehensive guide to using API Platform.', $content);
        self::assertStringNotContainsString('```', $content);
        // Verify that structuredContent is null when structuredContent: false
        self::assertNull($result['result']['_meta'] ?? null);
        self::assertArrayNotHasKey('structuredContent', $result['result']);
    }

    public function testMarkdownWithCodeBlock(): void
    {
        if (!class_exists(McpBundle::class)) {
            $this->markTestSkipped('MCP bundle is not installed');
        }

        if ($this->isMongoDB()) {
            $this->markTestSkipped('MCP is not supported with MongoDB');
        }

        if (!$this->isPsr17FactoryAvailable()) {
            $this->markTestSkipped('PSR-17 HTTP factory implementation not available (required for MCP)');
        }

        $client = self::createClient();
        $sessionId = $this->initializeMcpSession($client);

        $res = $this->callTool($client, $sessionId, 'generate_markdown', [
            'title' => 'Code Example',
            'content' => 'Here is how to use the feature:',
            'includeCodeBlock' => true,
        ]);

        self::assertResponseIsSuccessful();
        $result = $res->toArray();
        self::assertArrayHasKey('result', $result);
        $content = $result['result']['content'][0]['text'] ?? null;
        self::assertNotNull($content);
        self::assertStringContainsString('# Code Example', $content);
        self::assertStringContainsString('Here is how to use the feature:', $content);
        self::assertStringContainsString('```php', $content);
        self::assertStringContainsString("echo 'Hello, World!';", $content);
        self::assertStringContainsString('```', $content);
        // Verify that structuredContent is null when structuredContent: false
        self::assertNull($result['result']['_meta'] ?? null);
        self::assertArrayNotHasKey('structuredContent', $result['result']);
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
                    'clientInfo' => [
                        'name' => 'ApiPlatform Test Suite',
                        'version' => '1.0',
                    ],
                    'capabilities' => [],
                ],
            ],
        ]);
        self::assertResponseIsSuccessful();

        return $res->getHeaders()['mcp-session-id'][0];
    }

    public function testToolsList(): void
    {
        if (!class_exists(McpBundle::class)) {
            $this->markTestSkipped('MCP bundle is not installed');
        }

        if ($this->isMongoDB()) {
            $this->markTestSkipped('MCP is not supported with MongoDB');
        }

        if (!$this->isPsr17FactoryAvailable()) {
            $this->markTestSkipped('PSR-17 HTTP factory implementation not available (required for MCP)');
        }

        $client = self::createClient();
        $sessionId = $this->initializeMcpSession($client);

        $res = $client->request('POST', '/mcp', [
            'headers' => [
                'Accept' => 'application/json, text/event-stream',
                'Content-Type' => 'application/json',
                'mcp-session-id' => $sessionId,
            ],
            'json' => [
                'jsonrpc' => '2.0',
                'id' => 2,
                'method' => 'tools/list',
            ],
        ]);

        self::assertResponseIsSuccessful();
        $data = $res->toArray();
        self::assertArrayHasKey('result', $data);
        self::assertArrayHasKey('tools', $data['result']);

        $tools = $data['result']['tools'];
        $toolNames = array_column($tools, 'name');

        self::assertContains('get_book_info', $toolNames);
        self::assertContains('update_book_status', $toolNames);
        self::assertContains('custom_result', $toolNames);
        self::assertContains('validate_input', $toolNames);
        self::assertContains('generate_markdown', $toolNames);
        self::assertContains('process_message', $toolNames);
        self::assertContains('list_books', $toolNames);
        self::assertContains('list_books_dto', $toolNames);

        foreach ($tools as $tool) {
            self::assertArrayHasKey('name', $tool);
            self::assertArrayHasKey('inputSchema', $tool);
            self::assertEquals('object', $tool['inputSchema']['type']);
        }

        $listBooks = array_filter($tools, static function (array $input) {
            return 'list_books' === $input['name'];
        });

        self::assertCount(1, $listBooks);

        $listBooks = array_first($listBooks);

        self::assertArrayHasKeyAndValue('inputSchema', [
            'type' => 'object',
            'properties' => [
                'search' => ['type' => 'string'],
            ],
        ], $listBooks);
        self::assertArrayHasKeyAndValue('description', 'List Books', $listBooks);

        $outputSchema = $listBooks['outputSchema'];
        self::assertArrayHasKeyAndValue('$schema', 'http://json-schema.org/draft-07/schema#', $outputSchema);
        self::assertArrayHasKeyAndValue('type', 'object', $outputSchema);

        self::assertArrayHasKey('definitions', $outputSchema);
        $definitions = $outputSchema['definitions'];
        self::assertArrayHasKey('McpBook.jsonld', $definitions);
        $McpBookJsonLd = $definitions['McpBook.jsonld'];
        self::assertArrayHasKeyAndValue('allOf', [
            [
                '$ref' => '#/definitions/HydraItemBaseSchema',
            ],
            [
                'type' => 'object',
                'properties' => [
                    'id' => ['readOnly' => true, 'type' => 'integer'],
                    'title' => ['type' => 'string'],
                    'isbn' => ['type' => 'string'],
                    'status' => ['type' => ['string', 'null']],
                ],
            ],
        ], $McpBookJsonLd);

        self::assertArrayHasKeyAndValue('allOf', [
            ['$ref' => '#/definitions/HydraCollectionBaseSchema'],
            [
                'type' => 'object',
                'required' => ['hydra:member'],
                'properties' => [
                    'hydra:member' => [
                        'type' => 'array',
                        'items' => [
                            '$ref' => '#/definitions/McpBook.jsonld',
                        ],
                    ],
                ],
            ],
        ], $outputSchema);
    }

    public function testMcpToolAttribute(): void
    {
        if (!class_exists(McpBundle::class)) {
            $this->markTestSkipped('MCP bundle is not installed');
        }

        if ($this->isMongoDB()) {
            $this->markTestSkipped('MCP is not supported with MongoDB');
        }

        if (!$this->isPsr17FactoryAvailable()) {
            $this->markTestSkipped('PSR-17 HTTP factory implementation not available (required for MCP)');
        }

        $client = self::createClient();
        $sessionId = $this->initializeMcpSession($client);

        $res = $client->request('POST', '/mcp', [
            'headers' => [
                'Accept' => 'application/json, text/event-stream',
                'Content-Type' => 'application/json',
                'mcp-session-id' => $sessionId,
            ],
            'json' => [
                'jsonrpc' => '2.0',
                'id' => 2,
                'method' => 'tools/list',
            ],
        ]);

        $data = $res->toArray();
        $tools = $data['result']['tools'];
        $processMessageTool = null;
        foreach ($tools as $tool) {
            if ('process_message' === $tool['name']) {
                $processMessageTool = $tool;
                break;
            }
        }

        self::assertNotNull($processMessageTool);
        self::assertEquals('process_message', $processMessageTool['name']);
        self::assertEquals('Process a message with priority', $processMessageTool['description'] ?? null);
        self::assertArrayHasKey('inputSchema', $processMessageTool);
        self::assertEquals('object', $processMessageTool['inputSchema']['type']);

        $res = $client->request('POST', '/mcp', [
            'headers' => [
                'Accept' => 'application/json, text/event-stream',
                'Content-Type' => 'application/json',
                'mcp-session-id' => $sessionId,
            ],
            'json' => [
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
            ],
        ]);

        self::assertResponseIsSuccessful();
        $result = $res->toArray();
        self::assertArrayHasKey('result', $result);
    }

    public function testMcpResource(): void
    {
        if (!class_exists(McpBundle::class)) {
            $this->markTestSkipped('MCP bundle is not installed');
        }

        if ($this->isMongoDB()) {
            $this->markTestSkipped('MCP is not supported with MongoDB');
        }

        if (!$this->isPsr17FactoryAvailable()) {
            $this->markTestSkipped('PSR-17 HTTP factory implementation not available (required for MCP)');
        }

        $client = self::createClient();
        $sessionId = $this->initializeMcpSession($client);

        $res = $client->request('POST', '/mcp', [
            'headers' => [
                'Accept' => 'application/json, text/event-stream',
                'Content-Type' => 'application/json',
                'mcp-session-id' => $sessionId,
            ],
            'json' => [
                'jsonrpc' => '2.0',
                'id' => 2,
                'method' => 'resources/list',
            ],
        ]);

        self::assertResponseIsSuccessful();
        $data = $res->toArray();
        $resources = $data['result']['resources'] ?? [];
        $docResource = null;
        foreach ($resources as $resource) {
            if (str_contains($resource['name'] ?? '', 'Documentation') || 'resource_doc' === ($resource['name'] ?? '')) {
                $docResource = $resource;
                break;
            }
        }

        self::assertNotNull($docResource, 'Could not find documentation resource in: '.json_encode(array_column($resources, 'name')));
        self::assertEquals('resource://api-platform/documentation', $docResource['uri']);
        self::assertStringContainsString('Documentation', $docResource['name'] ?? '');
        self::assertNotEmpty($docResource['description'] ?? '');
        self::assertEquals('text/markdown', $docResource['mimeType'] ?? null);

        $res = $client->request('POST', '/mcp', [
            'headers' => [
                'Accept' => 'application/json, text/event-stream',
                'Content-Type' => 'application/json',
                'mcp-session-id' => $sessionId,
            ],
            'json' => [
                'jsonrpc' => '2.0',
                'id' => 3,
                'method' => 'resources/read',
                'params' => [
                    'uri' => 'resource://api-platform/documentation',
                ],
            ],
        ]);

        self::assertResponseIsSuccessful();
        $result = $res->toArray(false);
        self::assertArrayHasKey('result', $result);
        $contents = $result['result']['contents'] ?? $result['result']['content'] ?? [];
        self::assertNotEmpty($contents, 'No contents in result');
        $content = $contents[0]['text'] ?? null;
        self::assertNotNull($content);
        self::assertStringContainsString('API Platform', $content);
    }

    public function testMcpMarkdownContent(): void
    {
        if (!class_exists(McpBundle::class)) {
            $this->markTestSkipped('MCP bundle is not installed');
        }

        if ($this->isMongoDB()) {
            $this->markTestSkipped('MCP is not supported with MongoDB');
        }

        if (!$this->isPsr17FactoryAvailable()) {
            $this->markTestSkipped('PSR-17 HTTP factory implementation not available (required for MCP)');
        }

        $client = self::createClient();
        $sessionId = $this->initializeMcpSession($client);

        $res = $client->request('POST', '/mcp', [
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
                    'name' => 'generate_markdown',
                    'arguments' => [
                        'title' => 'API Platform Guide',
                        'content' => 'This is a comprehensive guide to using API Platform.',
                        'includeCodeBlock' => false,
                    ],
                ],
            ],
        ]);

        self::assertResponseIsSuccessful();
        $result = $res->toArray();
        self::assertArrayHasKey('result', $result);
        $content = $result['result']['content'][0]['text'] ?? null;
        self::assertNotNull($content, 'No text content in result');
        self::assertStringContainsString('# API Platform Guide', $content);
        self::assertStringContainsString('This is a comprehensive guide to using API Platform.', $content);
        self::assertStringNotContainsString('```', $content);

        $res = $client->request('POST', '/mcp', [
            'headers' => [
                'Accept' => 'application/json, text/event-stream',
                'Content-Type' => 'application/json',
                'mcp-session-id' => $sessionId,
            ],
            'json' => [
                'jsonrpc' => '2.0',
                'id' => 3,
                'method' => 'tools/call',
                'params' => [
                    'name' => 'generate_markdown',
                    'arguments' => [
                        'title' => 'Code Example',
                        'content' => 'Here is how to use the feature:',
                        'includeCodeBlock' => true,
                    ],
                ],
            ],
        ]);

        self::assertResponseIsSuccessful();
        $result = $res->toArray();
        self::assertArrayHasKey('result', $result);
        $content = $result['result']['content'][0]['text'] ?? null;
        self::assertNotNull($content);
        self::assertStringContainsString('# Code Example', $content);
        self::assertStringContainsString('Here is how to use the feature:', $content);
        self::assertStringContainsString('```php', $content);
        self::assertStringContainsString("echo 'Hello, World!';", $content);
        self::assertStringContainsString('```', $content);
    }

    public function testMcpListBooks(): void
    {
        if (!class_exists(McpBundle::class)) {
            $this->markTestSkipped('MCP bundle is not installed');
        }

        if ($this->isMongoDB()) {
            $this->markTestSkipped('MCP is not supported with MongoDB');
        }

        if (!$this->isPsr17FactoryAvailable()) {
            $this->markTestSkipped('PSR-17 HTTP factory implementation not available (required for MCP)');
        }

        $this->recreateSchema([
            McpBook::class,
        ]);

        $book = new McpBook();
        $book->setTitle('API Platform Guide for MCP');
        $book->setIsbn('1-528491');
        $book->setStatus('available');
        $manager = $this->getContainer()->get('doctrine.orm.entity_manager');
        $manager->persist($book);
        $manager->flush();

        $client = self::createClient();
        $sessionId = $this->initializeMcpSession($client);

        $res = $client->request('POST', '/mcp', [
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
                    'name' => 'list_books',
                    'arguments' => [
                        'search' => '',
                    ],
                ],
            ],
        ]);

        self::assertResponseIsSuccessful();
        $result = $res->toArray()['result'] ?? null;
        self::assertIsArray($result);
        self::assertArrayHasKey('content', $result);
        $content = $result['content'][0]['text'] ?? null;
        self::assertNotNull($content, 'No text content in result');
        self::assertStringContainsString('API Platform Guide for MCP', $content);
        self::assertStringContainsString('1-528491', $content);

        $structuredContent = $result['structuredContent'] ?? null;
        $this->assertIsArray($structuredContent);

        // when api_platform.use_symfony_listeners is true, the result is formatted as JSON-LD
        if (true === $this->getContainer()->getParameter('api_platform.use_symfony_listeners')) {
            self::assertArrayHasKeyAndValue('@context', '/contexts/McpBook', $structuredContent);
            self::assertArrayHasKeyAndValue('hydra:totalItems', 1, $structuredContent);
            $members = $structuredContent['hydra:member'];
        } else {
            $members = $structuredContent;
        }

        $this->assertCount(1, $members, json_encode($members, \JSON_PRETTY_PRINT));
        $actualBook = array_first($members);

        self::assertArrayHasKeyAndValue('id', 1, $actualBook);
        self::assertArrayHasKeyAndValue('title', 'API Platform Guide for MCP', $actualBook);
        self::assertArrayHasKeyAndValue('isbn', '1-528491', $actualBook);
        self::assertArrayHasKeyAndValue('status', 'available', $actualBook);
    }

    public function testMcpListBooksDto(): void
    {
        if (!class_exists(McpBundle::class)) {
            $this->markTestSkipped('MCP bundle is not installed');
        }

        if ($this->isMongoDB()) {
            $this->markTestSkipped('MCP is not supported with MongoDB');
        }

        if (!$this->isPsr17FactoryAvailable()) {
            $this->markTestSkipped('PSR-17 HTTP factory implementation not available (required for MCP)');
        }

        $this->recreateSchema([
            McpBook::class,
        ]);

        $book = new McpBook();
        $book->setTitle('API Platform Guide for MCP');
        $book->setIsbn('1-528491');
        $book->setStatus('available');
        $manager = $this->getContainer()->get('doctrine.orm.entity_manager');
        $manager->persist($book);
        $manager->flush();

        $client = self::createClient();
        $sessionId = $this->initializeMcpSession($client);

        $res = $client->request('POST', '/mcp', [
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
                    'name' => 'list_books_dto',
                    'arguments' => [
                        'search' => '',
                    ],
                ],
            ],
        ]);

        self::assertResponseIsSuccessful();
        $result = $res->toArray()['result'] ?? null;
        self::assertIsArray($result);
        self::assertArrayHasKey('content', $result);
        $content = $result['content'][0]['text'] ?? null;
        self::assertNotNull($content, 'No text content in result');
        self::assertStringContainsString('Raiders of the Lost Ark', $content);
        self::assertStringContainsString('1-528491', $content);

        $structuredContent = $result['structuredContent'] ?? null;
        $this->assertIsArray($structuredContent);

        $actualBook = $structuredContent;

        // when api_platform.use_symfony_listeners is true, the result is formatted as JSON-LD
        if (true === $this->getContainer()->getParameter('api_platform.use_symfony_listeners')) {
            self::assertArrayHasKey('@context', $structuredContent);
            $context = $structuredContent['@context'];
            self::assertArrayHasKeyAndValue('@vocab', 'http://localhost/docs.jsonld#', $context);
            self::assertArrayHasKeyAndValue('hydra', 'http://www.w3.org/ns/hydra/core#', $context);
        }

        self::assertArrayHasKeyAndValue('id', 528491, $actualBook);
        self::assertArrayHasKeyAndValue('name', 'Raiders of the Lost Ark', $actualBook);
        self::assertArrayHasKeyAndValue('isbn', '1-528491', $actualBook);
        self::assertArrayNotHasKey('status', $actualBook);
    }

    private static function assertArrayHasKeyAndValue(string $key, mixed $value, array $data): void
    {
        self::assertArrayHasKey($key, $data);
        self::assertSame($value, $data[$key]);
    }
}
