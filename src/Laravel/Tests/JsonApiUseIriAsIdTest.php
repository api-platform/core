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
use Workbench\App\Models\Book;
use Workbench\Database\Factories\AuthorFactory;
use Workbench\Database\Factories\BookFactory;

class JsonApiUseIriAsIdTest extends TestCase
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
            $config->set('api-platform.formats', ['jsonapi' => ['application/vnd.api+json']]);
            $config->set('api-platform.docs_formats', ['jsonapi' => ['application/vnd.api+json']]);
            $config->set('api-platform.resources', [app_path('Models'), app_path('ApiResource')]);
            $config->set('api-platform.jsonapi', ['use_iri_as_id' => false]);
            $config->set('api-platform.defaults', [
                'route_prefix' => '/api',
            ]);

            $config->set('app.debug', true);
        });
    }

    public function testGetBookUsesScalarIdAndLinksSelf(): void
    {
        BookFactory::new()->has(AuthorFactory::new())->create();
        $book = Book::first();
        $iri = $this->getIriFromResource($book);
        $response = $this->get($iri, ['accept' => ['application/vnd.api+json']]);
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/vnd.api+json; charset=utf-8');

        $this->assertJsonContains([
            'data' => [
                'id' => $book->getKey(),
                'type' => 'Book',
                'links' => ['self' => $iri],
                'attributes' => [
                    'name' => $book->name, // @phpstan-ignore-line
                ],
            ],
        ], $response->json());
    }
}
