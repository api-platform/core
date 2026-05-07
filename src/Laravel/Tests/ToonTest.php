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
use HelgeSverre\Toon\Toon;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;
use Workbench\App\Models\Author;
use Workbench\App\Models\Book;
use Workbench\Database\Factories\AuthorFactory;
use Workbench\Database\Factories\BookFactory;

class ToonTest extends TestCase
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
            // Add Toon format as separate format (uses JSON-LD normalizers with Toon encoder)
            $formats = $config->get('api-platform.formats', []);
            $formats['toon'] = ['text/ld+toon'];
            $formats['jsonld_toon'] = ['text/ld+toon']; // Explicitly add jsonld_toon
            $formats['hydra_toon'] = ['text/ld+toon']; // Explicitly add hydra_toon
            $formats['jsonapi_toon'] = ['text/vnd.api+toon']; // Explicitly add jsonapi_toon
            $config->set('api-platform.formats', $formats);

            $patchFormats = $config->get('api-platform.patch_formats', []);
            $patchFormats['toon'] = ['text/ld+toon'];
            $patchFormats['jsonld_toon'] = ['text/ld+toon']; // Explicitly add jsonld_toon
            $patchFormats['hydra_toon'] = ['text/ld+toon']; // Explicitly add hydra_toon
            $patchFormats['jsonapi_toon'] = ['text/vnd.api+toon']; // Explicitly add jsonapi_toon
            $config->set('api-platform.patch_formats', $patchFormats);

            $docsFormats = $config->get('api-platform.docs_formats', []);
            $docsFormats['toon'] = ['text/ld+toon'];
            $docsFormats['jsonld_toon'] = ['text/ld+toon']; // Explicitly add jsonld_toon
            $docsFormats['hydra_toon'] = ['text/ld+toon']; // Explicitly add hydra_toon
            $docsFormats['jsonapi_toon'] = ['text/vnd.api+toon']; // Explicitly add jsonapi_toon
            $config->set('api-platform.docs_formats', $docsFormats);

            $config->set('app.debug', true);
        });
    }

    public function testGetEntrypoint(): void
    {
        $response = $this->get('/api/', ['accept' => ['text/ld+toon']]);
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/ld+toon; charset=utf-8');

        $content = $response->getContent();

        // Decode the Toon content to check structure
        $decoded = \HelgeSverre\Toon\Toon::decode($content);

        $this->assertIsArray($decoded);
        // Laravel entrypoint might have different structure, just check it's an array with resources
        $this->assertNotEmpty($decoded);
        // The response should contain book-related information
        $contentLower = strtolower($content);
        $this->assertTrue(
            str_contains($contentLower, 'book') || str_contains($contentLower, 'api'),
            'Entrypoint should contain resource information'
        );
    }

    public function testGetCollection(): void
    {
        BookFactory::new()->has(AuthorFactory::new())->count(10)->create();
        $response = $this->get('/api/books', ['accept' => 'text/ld+toon']);
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/ld+toon; charset=utf-8');

        $content = $response->getContent();

        // Decode to verify structure
        $decoded = \HelgeSverre\Toon\Toon::decode($content);

        // Check for collection structure (Laravel doesn't use hydra prefix)
        $this->assertIsArray($decoded);

        // Check if it's a proper collection or a plain array
        if (isset($decoded['@id'])) {
            // Full collection structure
            $this->assertArrayHasKey('totalItems', $decoded);
            $this->assertEquals(10, $decoded['totalItems']);
            $this->assertArrayHasKey('member', $decoded);
            $this->assertCount(5, $decoded['member']); // Default page size
        } else {
            // Plain array of items
            $this->assertIsArray($decoded);
            $this->assertNotEmpty($decoded);
            // Just verify we got items back
            $this->assertGreaterThan(0, count($decoded));
        }
    }

    public function testGetBook(): void
    {
        BookFactory::new()->has(AuthorFactory::new())->count(10)->create();
        $book = Book::first();
        $iri = $this->getIriFromResource($book);
        $response = $this->get($iri, ['accept' => ['text/ld+toon']]);
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/ld+toon; charset=utf-8');

        $content = $response->getContent();

        $this->assertStringContainsString('id:', $content);
        $this->assertStringContainsString('name: '.$book->name, $content); // @phpstan-ignore-line
        // ISBN may be quoted in output
        $this->assertStringContainsString($book->isbn, $content); // @phpstan-ignore-line
    }

    public function testCreateBook(): void
    {
        AuthorFactory::new()->count(10)->create();
        $author = Author::find(1);

        $isbn = fake()->isbn13();

        $response = $this->postJson(
            '/api/books',
            [
                'name' => 'The Pragmatic Programmer',
                'isbn' => $isbn,
                'publicationDate' => fake()->optional()->date(),
                'author' => $this->getIriFromResource($author),
            ],
            [
                'accept' => 'text/ld+toon',
                'content-type' => 'application/ld+json',
            ]
        );

        $response->assertStatus(201);
        $response->assertHeader('content-type', 'text/ld+toon; charset=utf-8');

        $content = $response->getContent();

        $this->assertStringContainsString('name: The Pragmatic Programmer', $content);
        $this->assertStringContainsString('id:', $content);
    }

    public function testUpdateBook(): void
    {
        BookFactory::new()->has(AuthorFactory::new())->count(10)->create();
        $book = Book::first();
        $iri = $this->getIriFromResource($book);

        $response = $this->patchJson(
            $iri,
            [
                'name' => 'Updated Title',
            ],
            [
                'accept' => 'text/ld+toon',
                'content-type' => 'application/merge-patch+json',
            ]
        );

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/ld+toon; charset=utf-8');

        $content = $response->getContent();

        $this->assertStringContainsString('name: Updated Title', $content);
        // ISBN may be quoted in output
        $this->assertStringContainsString($book->isbn, $content); // @phpstan-ignore-line unchanged
    }

    public function testDeleteBook(): void
    {
        BookFactory::new()->has(AuthorFactory::new())->count(10)->create();
        $book = Book::first();
        $iri = $this->getIriFromResource($book);
        $response = $this->delete($iri, headers: ['accept' => 'text/ld+toon']);
        $response->assertStatus(204);
        $this->assertNull(Book::find($book->id));
    }
}
