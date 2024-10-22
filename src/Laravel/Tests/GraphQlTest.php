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
use Workbench\Database\Factories\AuthorFactory;
use Workbench\Database\Factories\BookFactory;

class GraphQlTest extends TestCase
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
        });
    }

    public function testGetBooks(): void
    {
        BookFactory::new()->has(AuthorFactory::new())->count(10)->create();
        $response = $this->postJson('/api/graphql', ['query' => '{books { edges { node {id, name, publicationDate, author {id, name }}}}}'], ['accept' => ['application/json']]);
        $response->assertStatus(200);
        $data = $response->json();
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayNotHasKey('errors', $data);
    }

    public function testGetBooksWithPaginationAndOrder(): void
    {
        BookFactory::new()->has(AuthorFactory::new())->count(10)->create();
        $response = $this->postJson('/api/graphql', ['query' => '{
  books(first: 3, order: {name: "desc"}) {
    edges {
      node {
        id, name, publicationDate, author { id, name }
      }
    }
  }
}'], ['accept' => ['application/json']]);
        $response->assertStatus(200);
        $data = $response->json();
        $this->assertArrayHasKey('data', $data);
        $this->assertCount(3, $data['data']['books']['edges']);
        $this->assertArrayNotHasKey('errors', $data);
    }
}
