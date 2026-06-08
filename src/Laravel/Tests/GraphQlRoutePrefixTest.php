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

class GraphQlRoutePrefixTest extends TestCase
{
    use ApiTestAssertionsTrait;
    use RefreshDatabase;
    use WithWorkbench;

    /**
     * @param Application $app
     */
    protected function defineEnvironment($app): void
    {
        tap($app['config'], static function (Repository $config): void {
            $config->set('app.debug', true);
            $config->set('api-platform.graphql.enabled', true);
            $config->set('api-platform.defaults.route_prefix', '');
        });
    }

    public function testPostGraphQlWithEmptyRoutePrefix(): void
    {
        $response = $this->postJson('/graphql', ['query' => '{books { edges { node { id }}}}'], ['accept' => ['application/json']]);
        $response->assertStatus(200);
        $data = $response->json();
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayNotHasKey('errors', $data);
    }

    public function testGetGraphQlWithEmptyRoutePrefix(): void
    {
        $response = $this->get('/graphql?query='.urlencode('{books { edges { node { id }}}}'), ['accept' => ['application/json']]);
        $response->assertStatus(200);
        $data = $response->json();
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayNotHasKey('errors', $data);
    }

    public function testGetGraphiQlWithEmptyRoutePrefix(): void
    {
        $response = $this->get('/graphiql');
        $response->assertStatus(200);
    }
}
