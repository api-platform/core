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
use Illuminate\Support\Facades\Gate;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;
use Symfony\AI\McpBundle\McpBundle;
use Workbench\App\ApiResource\McpSecuredTools;

class McpPolicyTest extends TestCase
{
    use RefreshDatabase;
    use WithWorkbench;

    protected function defineEnvironment($app): void
    {
        Gate::guessPolicyNamesUsing(static function (string $modelClass) {
            return McpSecuredTools::class === $modelClass ?
                McpSecuredToolsPolicy::class :
                null;
        });
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

    /**
     * @return list<string>
     */
    private function listToolNames(): array
    {
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

        $response->assertStatus(200);

        return array_column($response->json('result.tools'), 'name');
    }

    public function testToolDeniedByPolicyIsNotListed(): void
    {
        if (!class_exists(McpBundle::class)) {
            $this->markTestSkipped('MCP bundle is not installed');
        }

        if (!$this->isPsr17FactoryAvailable()) {
            $this->markTestSkipped('PSR-17 HTTP factory implementation not available (required for MCP)');
        }

        $this->assertNotContains('secured_denied_tool', $this->listToolNames());
    }

    public function testToolGrantedByPolicyIsListed(): void
    {
        if (!class_exists(McpBundle::class)) {
            $this->markTestSkipped('MCP bundle is not installed');
        }

        if (!$this->isPsr17FactoryAvailable()) {
            $this->markTestSkipped('PSR-17 HTTP factory implementation not available (required for MCP)');
        }

        $this->assertContains('secured_granted_tool', $this->listToolNames());
    }

    public function testToolWhosePolicyNeedsTheModelStaysListed(): void
    {
        if (!class_exists(McpBundle::class)) {
            $this->markTestSkipped('MCP bundle is not installed');
        }

        if (!$this->isPsr17FactoryAvailable()) {
            $this->markTestSkipped('PSR-17 HTTP factory implementation not available (required for MCP)');
        }

        // Gate::callPolicyMethod shifts off a string first argument ("this policy already knows
        // what type of models it can authorize") and then calls $policy->view($user), so a policy
        // method requiring a model instance throws instead of answering, see
        // vendor/laravel/framework/src/Illuminate/Auth/Access/Gate.php:825-839
        $this->assertContains('secured_model_tool', $this->listToolNames());
    }
}
