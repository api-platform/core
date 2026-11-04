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

use Illuminate\Config\Repository;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;
use Symfony\AI\McpBundle\McpBundle;

class McpMiddlewareTest extends TestCase
{
    use RefreshDatabase;
    use WithWorkbench;

    /**
     * @param Application $app
     */
    protected function defineEnvironment($app): void
    {
        tap($app['config'], static function (Repository $config): void {
            $config->set('app.key', 'AckfSECXIvnK5r28GVIWUAxmbBSjTsmF');
            $config->set('api-platform.mcp.middleware', ['auth:sanctum']);
        });
    }

    public function testMcpEndpointAppliesConfiguredMiddleware(): void
    {
        if (!class_exists(McpBundle::class)) {
            $this->markTestSkipped('MCP bundle is not installed');
        }

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

        $response->assertUnauthorized();
    }
}
