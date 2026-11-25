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

use ApiPlatform\HttpCache\PurgeTagProviderInterface;
use ApiPlatform\Laravel\Test\ApiTestAssertionsTrait;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;
use Workbench\App\Models\Author;
use Workbench\App\Models\Book;
use Workbench\App\Purger\MockPurger;
use Workbench\Database\Factories\AuthorFactory;
use Workbench\Database\Factories\BookFactory;

class PurgeTagProviderTest extends TestCase
{
    use ApiTestAssertionsTrait;
    use RefreshDatabase;
    use WithWorkbench;

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('api-platform.http_cache.invalidation.purger', MockPurger::class);
        $app->tag([MockPurgeTagProvider::class], PurgeTagProviderInterface::class);
    }

    protected function setUp(): void
    {
        parent::setUp();
        MockPurger::reset();
    }

    public function testProviderTagsOnCreate(): void
    {
        AuthorFactory::new()->create();
        $author = Author::first();

        $r = $this->postJson('/api/books', [
            'isbn' => '9783161484100',
            'name' => 'The Test Book',
            'author' => '/api/authors/'.$author->id,
        ], ['Accept' => 'application/ld+json', 'content-type' => 'application/ld+json']);

        $this->assertTagsWerePurged([
            $r->json()['@id'],
            '/api/books',
            'provider_insert',
        ]);
    }

    public function testProviderTagsOnUpdate(): void
    {
        BookFactory::new()->has(AuthorFactory::new())->create();
        $book = Book::first();

        $this->patchJson('/api/books/'.$book->id, [
            'name' => 'An Updated Name',
        ], [
            'Accept' => 'application/ld+json',
            'Content-Type' => 'application/merge-patch+json',
        ]);

        $this->assertTagsWerePurged([
            '/api/books',
            '/api/books/'.$book->id,
            'provider_update',
        ]);
    }

    public function testProviderTagsOnDelete(): void
    {
        BookFactory::new()->has(AuthorFactory::new())->create();
        $book = Book::first();

        $this->delete('/api/books/'.$book->id, headers: ['accept' => 'application/ld+json']);

        $this->assertTagsWerePurged([
            '/api/books',
            '/api/books/'.$book->id,
            'provider_delete',
        ]);
    }

    /**
     * @param string[] $expectedTags
     */
    private function assertTagsWerePurged(array $expectedTags): void
    {
        sort($expectedTags);
        $this->assertEquals($expectedTags, MockPurger::getPurgedTags());
    }
}
