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
use Workbench\App\Models\Author;
use Workbench\App\Purger\MockPurger;
use Workbench\Database\Factories\AuthorFactory;

class GraphQlPurgerTest extends TestCase
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
            $config->set('api-platform.graphql.enabled', true);
            $config->set('api-platform.http_cache.invalidation.purger', MockPurger::class);
        });
    }

    protected function setUp(): void
    {
        parent::setUp();
        MockPurger::reset();
    }

    public function testPurgeOnMutation(): void
    {
        /** @var Author $author */
        $author = AuthorFactory::new()->create();

        $response = $this->postJson('/api/graphql', [
            'query' => '
                mutation createBook($book: createBookInput!){
                    createBook(input: $book){
                        book{
                            id
                        }
                    }
                }
            ',
            'variables' => [
                'book' => [
                    'name' => 'The Test Book',
                    'author' => '/api/authors/'.$author->id,
                    'isbn' => '9783161484100',
                    'status' => BookStatus::PUBLISHED,
                    'isAvailable' => true,
                ],
            ],
        ], ['accept' => ['application/json']]);

        $response->assertOk();
        $this->assertArrayNotHasKey('errors', $response->json());
        $this->assertContains($response->json('data.createBook.book.id'), MockPurger::getPurgedTags());
        $this->assertContains('/api/books', MockPurger::getPurgedTags());
    }
}
