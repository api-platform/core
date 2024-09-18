<?php

namespace ApiPlatform\Laravel\Tests;

use ApiPlatform\Laravel\Test\ApiTestAssertionsTrait;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;
use Workbench\App\Models\Book;

class HalTest extends TestCase
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
            $config->set('api-platform.formats', ['jsonhal' => ['application/hal+json']]);
            $config->set('api-platform.docs_formats', ['jsonhal' => ['application/hal+json']]);
        });
    }

    public function testGetEntrypoint(): void
    {
        $response = $this->get('/api/', ['accept' => ['application/hal+json']]);
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/hal+json; charset=utf-8');

        $this->assertJsonContains(
            [
                '_links' => [
                    'self' => ['href' => '/api'],
                    'book' => ['href' => '/api/books'],
                    'post' => ['href' => '/api/posts'],
                    'sluggable' => ['href' => '/api/sluggables'],
                    'vault' => ['href' => '/api/vaults'],
                    'author' => ['href' => '/api/authors'],
                ],
            ],
            $response->json()
        );
    }

    public function testGetCollection(): void
    {
        $response = $this->get('/api/books', ['accept' => 'application/hal+json']);
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/hal+json; charset=utf-8');
        $this->assertJsonContains(
            [
                '_links' => [
                    'first' => ['href' => '/api/books?page=1'],
                    'self' => ['href' => '/api/books?page=1'],
                    'last' => ['href' => '/api/books?page=2'],
                ],
                'totalItems' => 10,
            ],
            $response->json()
        );
    }

    public function testGetBook(): void
    {
        $book = Book::first();
        $iri = $this->getIriFromResource($book);
        $response = $this->get($iri, ['accept' => ['application/hal+json']]);
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/hal+json; charset=utf-8');
        $this->assertJsonContains(
            [
                'name' => $book->name, // @phpstan-ignore-line
                'isbn' => $book->isbn, // @phpstan-ignore-line
                'author' => '/api/authors/1',
            ],
            $response->json()
        );
    }

    public function testCreateBook(): void
    {
        $response = $this->postJson(
            '/api/books',
            [
                'name' => 'Don Quichotte',
                'isbn' => fake()->isbn13(),
                'author' => '/api/authors/1',
            ],
            [
                'accept' => 'application/hal+json',
                'content_type' => 'application/hal+json',
            ]
        );

        $response->assertStatus(201);
        $response->assertHeader('content-type', 'application/hal+json; charset=utf-8');
        $this->assertJsonContains(
            [
                'name' => 'Don Quichotte',
                'author' => '/api/authors/1',
            ],
            $response->json()
        );
    }

    public function testUpdateBook(): void
    {
        $book = Book::first();
        $iri = $this->getIriFromResource($book);
        $response = $this->putJson(
            $iri,
            [
                'name' => 'New Title',
            ],
            [
                'content_type' => 'application/hal+json',
                'accept' => 'application/hal+json',
            ]
        );
        $response->assertStatus(200);
        var_dump($response->json());
        $this->assertJsonContains(
            [
                'name' => 'New Title',
            ],
            $response->json()
        );
    }

    public function testDeleteBook(): void
    {
        $book = Book::first();
        $iri = $this->getIriFromResource($book);
        $response = $this->delete($iri, headers: ['accept' => 'application/hal+json']);
        $response->assertStatus(204);
        $this->assertNull(Book::find($book->id)); // @phpstan-ignore-line
    }
}
