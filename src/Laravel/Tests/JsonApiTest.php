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

use ApiPlatform\JsonApi\Filter\SparseFieldset;
use ApiPlatform\Laravel\Eloquent\Filter\JsonApi\SortFilter;
use ApiPlatform\Laravel\Test\ApiTestAssertionsTrait;
use ApiPlatform\Metadata\QueryParameter;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;
use Workbench\App\Models\Author;
use Workbench\App\Models\Book;
use Workbench\Database\Factories\AuthorFactory;
use Workbench\Database\Factories\BookFactory;
use Workbench\Database\Factories\WithAccessorFactory;

class JsonApiTest extends TestCase
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
            $config->set('api-platform.formats', ['jsonapi' => ['application/vnd.api+json']]);
            $config->set('api-platform.docs_formats', ['jsonapi' => ['application/vnd.api+json']]);
            $config->set('api-platform.resources', [app_path('Models'), app_path('ApiResource')]);
            $config->set('api-platform.pagination.items_per_page_parameter_name', 'limit');
            $config->set('api-platform.defaults', [
                'route_prefix' => '/api',
                'parameters' => [
                    new QueryParameter(key: 'fields', filter: SparseFieldset::class),
                    new QueryParameter(key: 'sort', filter: SortFilter::class),
                ],
            ]);

            $config->set('app.debug', true);
        });
    }

    public function testGetEntrypoint(): void
    {
        $response = $this->get('/api/', ['accept' => ['application/vnd.api+json']]);
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/vnd.api+json; charset=utf-8');
        $this->assertJsonContains(
            [
                'links' => [
                    'self' => 'http://localhost/api',
                    'book' => 'http://localhost/api/books',
                ],
            ],
            $response->json()
        );
    }

    public function testGetCollection(): void
    {
        BookFactory::new()->has(AuthorFactory::new())->count(10)->create();
        $response = $this->get('/api/books', ['accept' => ['application/vnd.api+json']]);
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/vnd.api+json; charset=utf-8');
        $response->assertJsonFragment([
            'links' => [
                'self' => '/api/books?page=1',
                'first' => '/api/books?page=1',
                'last' => '/api/books?page=2',
                'next' => '/api/books?page=2',
            ],
            'meta' => ['totalItems' => 10, 'itemsPerPage' => 5, 'currentPage' => 1],
        ]);
        $response->assertJsonCount(5, 'data');
    }

    public function testGetBook(): void
    {
        BookFactory::new()->has(AuthorFactory::new())->count(10)->create();
        $book = Book::first();
        $iri = $this->getIriFromResource($book);
        $response = $this->get($iri, ['accept' => ['application/vnd.api+json']]);
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/vnd.api+json; charset=utf-8');

        $this->assertJsonContains([
            'data' => [
                'id' => $iri,
                'type' => 'Book',
                'attributes' => [
                    'name' => $book->name, // @phpstan-ignore-line
                ],
            ],
        ], $response->json());
    }

    public function testCreateBook(): void
    {
        BookFactory::new()->has(AuthorFactory::new())->count(10)->create();
        $author = Author::find(1);
        $response = $this->postJson(
            '/api/books',
            [
                'data' => [
                    'attributes' => [
                        'name' => 'Don Quichotte',
                        'isbn' => fake()->isbn13(),
                        'publicationDate' => fake()->optional()->date(),
                    ],
                    'relationships' => [
                        'author' => [
                            'data' => [
                                'id' => $this->getIriFromResource($author),
                                'type' => 'Author',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'accept' => 'application/vnd.api+json',
                'content_type' => 'application/vnd.api+json',
            ]
        );

        $response->assertStatus(201);
        $response->assertHeader('content-type', 'application/vnd.api+json; charset=utf-8');
        $this->assertJsonContains([
            'data' => [
                'type' => 'Book',
                'attributes' => [
                    'name' => 'Don Quichotte',
                ],
            ],
        ], $response->json());
        $this->assertMatchesRegularExpression('~^/api/books/~', $response->json('data.id'));
    }

    public function testUpdateBook(): void
    {
        BookFactory::new()->has(AuthorFactory::new())->count(10)->create();
        $book = Book::first();
        $iri = $this->getIriFromResource($book);
        $response = $this->putJson(
            $iri,
            [
                'data' => ['attributes' => ['name' => 'updated title']],
            ],
            [
                'accept' => 'application/vnd.api+json',
                'content_type' => 'application/vnd.api+json',
            ]
        );
        $response->assertStatus(200);
        $this->assertJsonContains([
            'data' => [
                'id' => $iri,
                'attributes' => [
                    'name' => 'updated title',
                ],
            ],
        ], $response->json());
    }

    public function testPatchBook(): void
    {
        BookFactory::new()->has(AuthorFactory::new())->count(10)->create();
        $book = Book::first();
        $iri = $this->getIriFromResource($book);
        $response = $this->patchJson(
            $iri,
            [
                'name' => 'updated title',
            ],
            [
                'accept' => 'application/vnd.api+json',
                'content_type' => 'application/merge-patch+json',
            ]
        );
        $response->assertStatus(200);
        $this->assertJsonContains([
            'data' => [
                'id' => $iri,
                'attributes' => [
                    'name' => 'updated title',
                ],
            ],
        ], $response->json());
    }

    public function testDeleteBook(): void
    {
        BookFactory::new()->has(AuthorFactory::new())->count(10)->create();
        $book = Book::first();
        $iri = $this->getIriFromResource($book);
        $response = $this->delete($iri, headers: ['accept' => 'application/vnd.api+json']);
        $response->assertStatus(204);
        $this->assertNull(Book::find($book->id));
    }

    public function testRelationWithGroups(): void
    {
        WithAccessorFactory::new()->create();
        $response = $this->get('/api/with_accessors/1', ['accept' => 'application/vnd.api+json']);
        $content = $response->json();
        $this->assertArrayHasKey('data', $content);
        $this->assertArrayHasKey('relationships', $content['data']);
        $this->assertArrayHasKey('relation', $content['data']['relationships']);
        $this->assertArrayHasKey('data', $content['data']['relationships']['relation']);
    }

    public function testValidateJsonApi(): void
    {
        $response = $this->postJson(
            '/api/issue6745/rule_validations',
            [
                'data' => [
                    'type' => 'string',
                    'attributes' => ['max' => 3],
                ],
            ],
            [
                'accept' => 'application/vnd.api+json',
                'content_type' => 'application/vnd.api+json',
            ]
        );

        $response->assertStatus(422);
        $response->assertHeader('content-type', 'application/vnd.api+json; charset=utf-8');
        $json = $response->json();
        $this->assertJsonContains([
            'errors' => [
                [
                    'detail' => 'The prop field is required.',
                    'title' => 'Validation Error',
                    'status' => 422,
                    'code' => '58350900e0fc6b8e/prop',
                ],
                [
                    'detail' => 'The max field must be less than 2.',
                    'title' => 'Validation Error',
                    'status' => 422,
                    'code' => '58350900e0fc6b8e/max',
                ],
            ],
        ], $json);

        $this->assertArrayHasKey('id', $json['errors'][0]);
        $this->assertArrayHasKey('links', $json['errors'][0]);
        $this->assertArrayHasKey('type', $json['errors'][0]['links']);

        $response = $this->postJson(
            '/api/issue6745/rule_validations',
            [
                'data' => [
                    'type' => 'string',
                    'attributes' => [
                        'prop' => 1,
                        'max' => 1,
                    ],
                ],
            ],
            [
                'accept' => 'application/vnd.api+json',
                'content_type' => 'application/vnd.api+json',
            ]
        );
        $response->assertStatus(201);
    }

    public function testNotFound(): void
    {
        $response = $this->get('/api/books/notfound', headers: ['accept' => 'application/vnd.api+json']);
        $response->assertStatus(404);
        $response->assertHeader('content-type', 'application/vnd.api+json; charset=utf-8');

        $this->assertJsonContains([
            'links' => ['type' => '/errors/404'],
            'title' => 'An error occurred',
            'status' => 404,
            'detail' => 'Not Found',
        ], $response->json()['errors'][0]);
    }

    public function testSortParameter(): void
    {
        BookFactory::new()->has(AuthorFactory::new())->count(10)->create();
        DB::enableQueryLog();
        $this->get('/api/books?sort=isbn,-name', headers: ['accept' => 'application/vnd.api+json']);
        ['query' => $q] = DB::getQueryLog()[1];
        $this->assertStringContainsString('order by "isbn" asc, "name" desc', $q);
    }

    public function testPageParameter(): void
    {
        BookFactory::new()->has(AuthorFactory::new())->count(10)->create();
        DB::enableQueryLog();
        $this->get('/api/books?page[limit]=1&page[offset]=2', headers: ['accept' => 'application/vnd.api+json']);
        ['query' => $q] = DB::getQueryLog()[1];
        $this->assertStringContainsString('select * from "books" limit 1 offset 1', $q);
    }

    public function testSparseFieldset(): void
    {
        BookFactory::new()->has(AuthorFactory::new())->count(10)->create();
        $r = $this->get('/api/books?fields[book]=name,isbn&fields[author]=name&include=author', headers: ['accept' => 'application/vnd.api+json']);
        $res = $r->json();
        $attributes = $res['data'][0]['attributes'];
        $this->assertArrayHasKey('name', $attributes);
        $this->assertArrayHasKey('isbn', $attributes);
        $this->assertArrayNotHasKey('isAvailable', $attributes);

        $included = $res['included'][0]['attributes'];
        $this->assertArrayNotHasKey('createdAt', $included);
        $this->assertArrayHasKey('name', $included);
    }
}
