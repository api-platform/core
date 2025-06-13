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
use ApiPlatform\Laravel\workbench\app\Enums\BookStatus;
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

    public function testGetBooksWithSimplePagination(): void
    {
        BookFactory::new()->has(AuthorFactory::new())->count(9)->create();
        $response = $this->postJson('/api/graphql', ['query' => '{
  simplePaginationBooks(page: 1) {
    collection {
        id
     },
    paginationInfo {
        itemsPerPage,
        currentPage,
        lastPage,
        totalCount,
        hasNextPage
     }
  }
}'], ['accept' => ['application/json']]);
        $response->assertStatus(200);
        $data = $response->json();
        $this->assertArrayHasKey('data', $data);
        $this->assertCount(3, $data['data']['simplePaginationBooks']['collection']);
        $this->assertEquals(3, $data['data']['simplePaginationBooks']['paginationInfo']['itemsPerPage']);
        $this->assertEquals(1, $data['data']['simplePaginationBooks']['paginationInfo']['currentPage']);
        $this->assertEquals(3, $data['data']['simplePaginationBooks']['paginationInfo']['lastPage']);
        $this->assertEquals(9, $data['data']['simplePaginationBooks']['paginationInfo']['totalCount']);
        $this->assertTrue($data['data']['simplePaginationBooks']['paginationInfo']['hasNextPage']);
        $this->assertArrayNotHasKey('errors', $data);
    }

    public function testGetBooksWithPaginationAndOrder(): void
    {
        // Create books in reverse alphabetical order to test the 'asc' order
        BookFactory::new()
            ->count(10)
            ->sequence(fn ($sequence) => ['name' => \chr(122 - $sequence->index)]) // ASCII codes starting from 'z'
            ->has(AuthorFactory::new())
            ->create();

        $response = $this->postJson('/api/graphql', [
            'query' => '
              query getBooks($first: Int!, $order: orderBookcollection_query!) {
                books(first: $first, order: $order) {
                  edges {
                    node {
                      id, name, publicationDate, author { id, name }
                    }
                  }
                }
              }
            ',
            'variables' => [
                'first' => 3,
                'order' => ['name' => 'asc'],
            ],
        ], ['accept' => ['application/json']]);
        $response->assertStatus(200);
        $data = $response->json();
        $this->assertArrayHasKey('data', $data);
        $this->assertCount(3, $data['data']['books']['edges']);
        $this->assertEquals('q', $data['data']['books']['edges'][0]['node']['name']);
        $this->assertEquals('r', $data['data']['books']['edges'][1]['node']['name']);
        $this->assertEquals('s', $data['data']['books']['edges'][2]['node']['name']);
        $this->assertArrayNotHasKey('errors', $data);
    }

    public function testCreateBook(): void
    {
        /** @var \Workbench\App\Models\Author $author */
        $author = AuthorFactory::new()->create();
        $response = $this->postJson('/api/graphql', [
            'query' => '
                mutation createBook($book: createBookInput!){
                    createBook(input: $book){
                        book{
                            name
                            isAvailable
                        }
                    }
                }
            ',
            'variables' => [
                'book' => [
                    'name' => fake()->name(),
                    'author' => 'api/authors/'.$author->id,
                    'isbn' => fake()->isbn13(),
                    'status' => BookStatus::PUBLISHED,
                    'isAvailable' => 1 === random_int(0, 1),
                ],
            ],
        ], ['accept' => ['application/json']]);
        $response->assertStatus(200);
        $data = $response->json();
        $this->assertArrayNotHasKey('errors', $data);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('createBook', $data['data']);
        $this->assertArrayHasKey('book', $data['data']['createBook']);
        $this->assertArrayHasKey('isAvailable', $data['data']['createBook']['book']);
        $this->assertIsBool($data['data']['createBook']['book']['isAvailable']);
    }
}
