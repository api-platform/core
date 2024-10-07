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
use Illuminate\Contracts\Config\Repository;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\Attributes\DefineEnvironment;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;
use Workbench\Database\Factories\AuthorFactory;
use Workbench\Database\Factories\BookFactory;
use Workbench\Database\Factories\UserFactory;
use Workbench\Database\Factories\VaultFactory;

class GraphQlAuthTest extends TestCase
{
    use ApiTestAssertionsTrait;
    use RefreshDatabase;
    use WithWorkbench;

    protected function afterRefreshingDatabase(): void
    {
        UserFactory::new()->create();
    }

    /**
     * @param Application $app
     */
    protected function defineEnvironment($app): void
    {
        tap($app['config'], function (Repository $config): void {
            $config->set('api-platform.routes.middleware', ['auth:sanctum']);
            $config->set('app.key', 'AckfSECXIvnK5r28GVIWUAxmbBSjTsmF');
            $config->set('api-platform.graphql.enabled', true);
        });
    }

    public function testUnauthenticated(): void
    {
        $response = $this->postJson('/api/graphql', [], []);
        $response->assertStatus(401);
    }

    public function testAuthenticated(): void
    {
        BookFactory::new()->has(AuthorFactory::new())->count(10)->create();
        $response = $this->post('/tokens/create');
        $token = $response->json()['token'];
        $response = $this->get('/api/graphql', ['accept' => ['text/html'], 'authorization' => 'Bearer '.$token]);
        $response->assertStatus(200);
        $response = $this->postJson('/api/graphql', [
            'query' => '{books { edges { node {id, name, publicationDate, author {id, name }}}}}',
        ], [
            'content-type' => 'application/json',
            'authorization' => 'Bearer '.$token,
        ]);
        $response->assertStatus(200);
        $data = $response->json();
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayNotHasKey('errors', $data);
    }

    public function testPolicy(): void
    {
        VaultFactory::new()->count(10)->create();
        $response = $this->post('/tokens/create');
        $token = $response->json()['token'];
        $response = $this->postJson('/api/graphql', ['query' => 'mutation {
    updateVault(input: {secret: "secret", id: "/api/vaults/1"}) {
      vault {id}
    }
  }
'], ['accept' => ['application/json'], 'authorization' => 'Bearer '.$token]);
        $response->assertStatus(200);
        $data = $response->json();
        $this->assertArrayHasKey('errors', $data);
        $this->assertEquals('Access Denied.', $data['errors'][0]['message']);
    }

    /**
     * @param Application $app
     */
    protected function useProductionMode($app): void
    {
        tap($app['config'], function (Repository $config): void {
            $config->set('api-platform.routes.middleware', ['auth:sanctum']);
            $config->set('api-platform.graphql.enabled', true);
            $config->set('app.key', 'AckfSECXIvnK5r28GVIWUAxmbBSjTsmF');
            $config->set('app.debug', false);
        });
    }

    #[DefineEnvironment('useProductionMode')]
    public function testProductionError(): void
    {
        VaultFactory::new()->count(10)->create();
        $response = $this->post('/tokens/create');
        $token = $response->json()['token'];
        $response = $this->postJson('/api/graphql', ['query' => 'mutation {
    updateVault(input: {secret: "secret", id: "/api/vaults/1"}) {
      vault {id}
    }
  }
'], ['accept' => ['application/json'], 'authorization' => 'Bearer '.$token]);
        $response->assertStatus(200);
        $data = $response->json();
        $this->assertArrayHasKey('errors', $data);
        $this->assertArrayNotHasKey('trace', $data['errors'][0]);
    }
}
