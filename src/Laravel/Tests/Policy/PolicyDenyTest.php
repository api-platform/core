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

namespace ApiPlatform\Laravel\Tests\Policy;

use ApiPlatform\Laravel\Test\ApiTestAssertionsTrait;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;
use Workbench\App\Models\Author;
use Workbench\App\Models\Book;
use Workbench\Database\Factories\AuthorFactory;
use Workbench\Database\Factories\BookFactory;

class PolicyDenyTest extends TestCase
{
    use ApiTestAssertionsTrait;
    use RefreshDatabase;
    use WithWorkbench;

    /**
     * @param Application $app
     */
    protected function defineEnvironment($app): void
    {
        Gate::guessPolicyNamesUsing(function (string $modelClass) {
            return Book::class === $modelClass ?
                BookDenyPolicy::class :
                null;
        });

        tap($app['config'], function (Repository $config): void {
            $config->set('api-platform.formats', ['jsonapi' => ['application/vnd.api+json']]);
            $config->set('api-platform.docs_formats', ['jsonapi' => ['application/vnd.api+json']]);
            $config->set('app.debug', true);
        });
    }

    public function testGetCollection(): void
    {
        $response = $this->get('/api/books', ['accept' => ['application/vnd.api+json']]);
        $response->assertStatus(403);
    }

    public function testGetBook(): void
    {
        BookFactory::new()->has(AuthorFactory::new())->create();
        $book = Book::first();
        $iri = $this->getIriFromResource($book);
        $response = $this->get($iri, ['accept' => ['application/vnd.api+json']]);
        $response->assertStatus(403);
    }

    public function testCreateBook(): void
    {
        BookFactory::new()->has(AuthorFactory::new())->create();
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

        $response->assertStatus(403);
    }

    public function testUpdateBook(): void
    {
        BookFactory::new()->has(AuthorFactory::new())->create();
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
        $response->assertStatus(403);
    }

    public function testPatchBook(): void
    {
        BookFactory::new()->has(AuthorFactory::new())->create();
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
        $response->assertStatus(403);
    }

    public function testDeleteBook(): void
    {
        BookFactory::new()->has(AuthorFactory::new())->create();
        $book = Book::first();
        $iri = $this->getIriFromResource($book);
        $response = $this->delete($iri, headers: ['accept' => 'application/vnd.api+json']);
        $response->assertStatus(403);
    }
}
