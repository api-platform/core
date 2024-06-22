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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;
use Workbench\App\Models\Author;
use Workbench\App\Models\Book;

class JsonLdTest extends TestCase
{
    use ApiTestAssertionsTrait;
    use RefreshDatabase;
    use WithWorkbench;

    public function testGetCollection(): void
    {
        $response = $this->get('/api/books', ['accept' => 'application/ld+json']);
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/ld+json; charset=utf-8');
        $response->assertJsonFragment([
            '@context' => '/api/contexts/Book',
            '@id' => '/api/books',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 10,
        ]);
        $response->assertJsonCount(5, 'hydra:member');
    }

    public function testGetBook(): void
    {
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
        $author = Author::find(1);
        $response = $this->postJson(
            '/api/books',
            [
                'name' => 'Don Quichotte',
                'author' => $this->getIriFromResource($author),
                'isbn' => fake()->isbn13(),
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
            '@id' => $iri,
            'name' => 'updated title',
        ]);
    }

    public function testPatchBook(): void
    {
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
        $book = Book::first();
        $iri = $this->getIriFromResource($book);
        $response = $this->delete($iri, ['accept' => 'application/ld+json']);
        $response->assertStatus(204);
        $this->assertNull(Book::find($book->id));
    }

    public function testPatchBookAuthor(): void
    {
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
        $response = $this->get('/api/posts', ['accept' => 'application/ld+json']);
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/ld+json; charset=utf-8');

        $response->assertJsonFragment([
            '@context' => '/api/contexts/Post',
            '@id' => '/api/posts',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 10,
        ]);
        $response->assertJsonCount(10, 'hydra:member');
        $postIri = $response->json('hydra:member.0.@id');
        $commentsIri = $response->json('hydra:member.0.comments');
        $this->assertMatchesRegularExpression('~^/api/posts/\d+/comments$~', $commentsIri);
        $response = $this->get($commentsIri, ['accept' => 'application/ld+json']);
        $response->assertJsonFragment([
            '@context' => '/api/contexts/Comment',
            '@id' => $commentsIri,
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 10,
        ]);

        $commentIri = $response->json('hydra:member.0.@id');
        $response = $this->get($commentIri, ['accept' => 'application/ld+json']);
        $response->assertJsonFragment([
            '@id' => $commentIri,
            'post' => $postIri,
        ]);
    }

    public function testCreateNotValid(): void
    {
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
}
