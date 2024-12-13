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

use ApiPlatform\Laravel\Test\ApiTestAssertionsTrait;
use Illuminate\Config\Repository;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;
use Workbench\Database\Factories\UserFactory;

class AuthTest extends TestCase
{
    use ApiTestAssertionsTrait;
    use RefreshDatabase;
    use WithWorkbench;

    /**
     * @param Application $app
     */
    protected function defineEnvironment($app): void
    {
        tap($app['config'], function (Repository $config): void {
            $config->set('api-platform.graphql.enabled', true);
            $config->set('app.key', 'AckfSECXIvnK5r28GVIWUAxmbBSjTsmF');
        });
    }

    protected function afterRefreshingDatabase(): void
    {
        UserFactory::new()->create();
    }

    public function testGetCollection(): void
    {
        $response = $this->get('/api/vaults', ['accept' => ['application/ld+json']]);
        $this->assertArraySubset(['detail' => 'Unauthenticated.'], $response->json());
        $response->assertHeader('content-type', 'application/problem+json; charset=utf-8');
        $response->assertStatus(401);
    }

    public function testAuthenticated(): void
    {
        $response = $this->post('/tokens/create');
        $token = $response->json()['token'];
        $response = $this->get('/api/vaults', ['accept' => ['application/ld+json'], 'authorization' => 'Bearer '.$token]);
        $response->assertStatus(200);
    }

    public function testAuthenticatedPolicy(): void
    {
        $response = $this->post('/tokens/create');
        $token = $response->json()['token'];
        $response = $this->postJson('/api/vaults', [], ['accept' => ['application/ld+json'], 'content-type' => ['application/ld+json'], 'authorization' => 'Bearer '.$token]);
        $response->assertStatus(403);
    }

    public function testAuthenticatedDeleteWithPolicy(): void
    {
        $response = $this->post('/tokens/create');
        $token = $response->json()['token'];
        $response = $this->delete('/api/vaults/1', [], ['accept' => ['application/ld+json'], 'authorization' => 'Bearer '.$token]);
        $response->assertStatus(403);
    }
}
