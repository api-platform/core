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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\McpDummy;
use ApiPlatform\Tests\SetupClassResourcesTrait;

class McpTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [McpDummy::class];
    }

    public function testGetMcpOperation(): void
    {
        $client = self::createClient();
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

        $sessionId = $res->getHeaders()['mcp-session-id'][0];
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

        self::assertEquals(
            [
                'jsonrpc' => '2.0',
                'id' => 2,
                'result' => [
                    'tools' => [
                        [
                            'name' => 'mcp_dummy_tool',
                            'inputSchema' => [
                                'type' => 'object',
                                'properties' => [
                                    'id' => [
                                        'type' => 'integer',
                                    ],
                                    'name' => [
                                        'type' => 'string',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            $res->toArray()
        );

        self::assertResponseIsSuccessful();

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
                    'name' => 'mcp_dummy_tool',
                    'arguments' => [
                        'id' => 1,
                        'name' => 'test',
                    ],
                ],
            ],
        ]);

        dd($res->toArray());
        self::assertResponseIsSuccessful();
    }
}
