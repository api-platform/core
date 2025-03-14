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
use Workbench\Database\Factories\CommentFactory;
use Workbench\Database\Factories\PostFactory;
use Workbench\Database\Factories\SluggableFactory;
use Workbench\Database\Factories\WithAccessorFactory;

class JsonLdTest extends TestCase
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
            $config->set('app.debug', true);
            $config->set('api-platform.resources', [app_path('Models'), app_path('ApiResource')]);
        });
    }

    public function testGetCollection(): void
    {
        BookFactory::new()->has(AuthorFactory::new())->count(10)->create();
        $response = $this->get('/api/books', ['accept' => 'application/ld+json']);
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/ld+json; charset=utf-8');
        $response->assertJsonFragment([
            '@context' => '/api/contexts/Book',
            '@id' => '/api/books',
            '@type' => 'Collection',
            'totalItems' => 10,
        ]);
        $response->assertJsonCount(5, 'member');
    }

    public function testGetBook(): void
    {
        BookFactory::new()->has(AuthorFactory::new())->count(10)->create();
        $book = Book::first();
        $response = $this->get($this->getIriFromResource($book), ['accept' => 'application/ld+json']);
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/ld+json; charset=utf-8');
        $response->assertJsonFragment([
            '@context' => '/api/contexts/Book',
            '@id' => $this->getIriFromResource($book),
            '@type' => 'Book',
            'name' => $book->name, // @phpstan-ignore-line
        ]);
    }

    public function testCreateBook(): void
    {
        AuthorFactory::new()->create();
        $author = Author::find(1);
        $response = $this->postJson(
            '/api/books',
            [
                'name' => 'Don Quichotte',
                'author' => $this->getIriFromResource($author),
                'isbn' => fake()->isbn13(),
                'publicationDate' => fake()->optional()->date(),
            ],
            [
                'accept' => 'application/ld+json',
                'content_type' => 'application/ld+json',
            ]
        );

        $response->assertStatus(201);
        $response->assertHeader('content-type', 'application/ld+json; charset=utf-8');
        $response->assertJsonFragment([
            '@context' => '/api/contexts/Book',
            '@type' => 'Book',
            'name' => 'Don Quichotte',
        ]);
        $this->assertMatchesRegularExpression('~^/api/books/~', $response->json('@id'));
    }

    public function testUpdateBook(): void
    {
        BookFactory::new()->has(AuthorFactory::new())->count(10)->create();
        $book = Book::first();
        $iri = $this->getIriFromResource($book);
        $response = $this->putJson(
            $iri,
            [
                'name' => 'updated title',
            ],
            [
                'accept' => 'application/ld+json',
                'content_type' => 'application/ld+json',
            ]
        );
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'name' => 'updated title',
        ]);
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
                'accept' => 'application/ld+json',
                'content_type' => 'application/merge-patch+json',
            ]
        );
        $response->assertStatus(200);
        $response->assertJsonFragment([
            '@id' => $iri,
            'name' => 'updated title',
        ]);
    }

    public function testDeleteBook(): void
    {
        BookFactory::new()->has(AuthorFactory::new())->count(10)->create();
        $book = Book::first();
        $iri = $this->getIriFromResource($book);
        $response = $this->delete($iri, headers: ['accept' => 'application/ld+json']);
        $response->assertStatus(204);
        $this->assertNull(Book::find($book->id));
    }

    public function testPatchBookAuthor(): void
    {
        BookFactory::new()->has(AuthorFactory::new())->count(10)->create();
        $book = Book::first();
        $iri = $this->getIriFromResource($book);
        $author = Author::find(2);
        $authorIri = $this->getIriFromResource($author);
        $response = $this->patchJson(
            $iri,
            [
                'author' => $authorIri,
            ],
            [
                'accept' => 'application/ld+json',
                'content_type' => 'application/merge-patch+json',
            ]
        );
        $response->assertStatus(200);
        $response->assertJsonFragment([
            '@id' => $iri,
            'author' => $authorIri,
        ]);
    }

    public function testSkolemIris(): void
    {
        $response = $this->get('/api/outputs', ['accept' => 'application/ld+json']);
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/ld+json; charset=utf-8');
        $response->assertJsonFragment([
            '@type' => 'NotAResource',
            'name' => 'test',
        ]);

        $this->assertMatchesRegularExpression('~^/api/.well-known/genid/~', $response->json('@id'));
    }

    public function testSubresourceCollection(): void
    {
        PostFactory::new()->has(CommentFactory::new()->count(10))->count(10)->create();
        $response = $this->get('/api/posts', ['accept' => 'application/ld+json']);
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/ld+json; charset=utf-8');

        $response->assertJsonFragment([
            '@context' => '/api/contexts/Post',
            '@id' => '/api/posts',
            '@type' => 'Collection',
            'totalItems' => 10,
        ]);
        $response->assertJsonCount(10, 'member');
        $postIri = $response->json('member.0.@id');
        $commentsIri = $response->json('member.0.comments');
        $this->assertMatchesRegularExpression('~^/api/posts/\d+/comments$~', $commentsIri);
        $response = $this->get($commentsIri, ['accept' => 'application/ld+json']);
        $response->assertJsonFragment([
            '@context' => '/api/contexts/Comment',
            '@id' => $commentsIri,
            '@type' => 'Collection',
            'totalItems' => 10,
        ]);

        $commentIri = $response->json('member.0.@id');
        $response = $this->get($commentIri, ['accept' => 'application/ld+json']);
        $response->assertJsonFragment([
            '@id' => $commentIri,
            'post' => $postIri,
        ]);
    }

    public function testCreateNotValid(): void
    {
        BookFactory::new()->has(AuthorFactory::new())->count(10)->create();
        $author = Author::find(1);
        $response = $this->postJson(
            '/api/books',
            [
                'name' => 'Don Quichotte',
                'author' => $this->getIriFromResource($author),
                'isbn' => 'test@foo',
            ],
            [
                'accept' => 'application/ld+json',
                'content_type' => 'application/ld+json',
            ]
        );

        $response->assertStatus(422);
        $response->assertHeader('content-type', 'application/problem+json; charset=utf-8');
        $response->assertJsonFragment([
            '@context' => '/api/contexts/ValidationError',
            '@type' => 'ValidationError',
            'description' => 'The isbn field must only contain letters and numbers.',
        ]);

        $violations = $response->json('violations');
        $this->assertCount(1, $violations);
        $this->assertEquals($violations[0], ['propertyPath' => 'isbn', 'message' => 'The isbn field must only contain letters and numbers.']);
    }

    public function testCreateNotValidPost(): void
    {
        $response = $this->postJson(
            '/api/posts',
            [
            ],
            [
                'accept' => 'application/ld+json',
                'content_type' => 'application/ld+json',
            ]
        );

        $response->assertStatus(422);
        $response->assertHeader('content-type', 'application/problem+json; charset=utf-8');
        $response->assertJsonFragment([
            '@context' => '/api/contexts/ValidationError',
            '@type' => 'ValidationError',
            'description' => 'The title field is required.',
        ]);

        $violations = $response->json('violations');
        $this->assertCount(1, $violations);
        $this->assertEquals($violations[0], ['propertyPath' => 'title', 'message' => 'The title field is required.']);
    }

    public function testSluggable(): void
    {
        SluggableFactory::new()->count(10)->create();
        $response = $this->get('/api/sluggables', ['accept' => 'application/ld+json']);
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/ld+json; charset=utf-8');
        $response->assertJsonFragment([
            '@context' => '/api/contexts/Sluggable',
            '@id' => '/api/sluggables',
            '@type' => 'Collection',
            'totalItems' => 10,
        ]);
        $iri = $response->json('member.0.@id');
        $response = $this->get($iri, ['accept' => 'application/ld+json']);
        $response->assertStatus(200);
    }

    public function testApiDocsRegex(): void
    {
        $response = $this->get('/api/notexists', ['accept' => 'application/ld+json']);
        $response->assertNotFound();
    }

    public function testHidden(): void
    {
        PostFactory::new()->has(CommentFactory::new()->count(10))->count(10)->create();
        $response = $this->get('/api/posts/1/comments/1', ['accept' => 'application/ld+json']);
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/ld+json; charset=utf-8');
        $response->assertJsonMissingPath('internalNote');
    }

    public function testVisible(): void
    {
        BookFactory::new()->has(AuthorFactory::new())->count(10)->create();
        $response = $this->get('/api/books', ['accept' => 'application/ld+json']);
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/ld+json; charset=utf-8');
        $this->assertStringNotContainsString('internalNote', (string) $response->getContent());
    }

    public function testError(): void
    {
        $response = $this->post('/api/books', ['content-type' => 'application/vnd.api+json']);
        $response->assertStatus(415);
        $content = $response->json();
        $this->assertArrayHasKey('trace', $content);
    }

    public function testErrorNotFound(): void
    {
        $response = $this->get('/api/books/asd', ['accept' => 'application/ld+json']);
        $response->assertStatus(404);
        $content = $response->json();
        $this->assertArrayHasKey('status', $content);
        $this->assertEquals(404, $content['status']);
    }

    public function testRelationWithGroups(): void
    {
        WithAccessorFactory::new()->create();
        $response = $this->get('/api/with_accessors/1', ['accept' => 'application/ld+json']);
        $content = $response->json();
        $this->assertArrayHasKey('relation', $content);
        $this->assertArrayHasKey('name', $content['relation']);
    }

    /**
     * @see https://github.com/api-platform/core/issues/6779
     */
    public function testSimilarRoutesWithFormat(): void
    {
        $response = $this->get('/api/staff_position_histories?page=1', ['accept' => 'application/ld+json']);
        $response->assertStatus(200);
        $this->assertSame('/api/staff_position_histories', $response->json()['@id']);
    }

    public function testResourceWithOptionModel(): void
    {
        $response = $this->get('/api/resource_with_models?page=1', ['accept' => 'application/ld+json']);
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/ld+json; charset=utf-8');
        $response->assertJsonFragment([
            '@context' => '/api/contexts/ResourceWithModel',
            '@id' => '/api/resource_with_models',
            '@type' => 'Collection',
        ]);
    }
}
