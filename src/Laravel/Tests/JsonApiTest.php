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
            $config->set('app.debug', true);
        });
    }

    public function testGetEntrypoint(): void
    {
        $response = $this->get('/api/', ['accept' => ['application/vnd.api+json']]);
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/vnd.api+json; charset=utf-8');
        $this->assertJsonContains([
            'links' => [
                'self' => 'http://localhost/api',
                'book' => 'http://localhost/api/books',
            ],
        ],
            $response->json());
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
}
