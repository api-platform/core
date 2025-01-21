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

use ApiPlatform\Laravel\Test\ApiTestAssertionsTrait;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;
use Workbench\App\Models\Book;
use Workbench\Database\Factories\BookFactory;

class LinkedDataPlatformTest extends TestCase
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
            $config->set('api-platform.formats', ['jsonld' => ['application/ld+json'], 'turtle' => ['text/turtle']]);
            $config->set('api-platform.resources', [app_path('Models'), app_path('ApiResource')]);
            $config->set('app.debug', true);
        });
    }

    public function testHeadersAcceptPostIsReturnWhenPostAllowed(): void
    {
        $response = $this->get('/api/books', ['accept' => ['application/ld+json']]);
        $response->assertStatus(200);
        $response->assertHeader('accept-post', 'application/ld+json, text/turtle, text/html');
    }

    public function testHeadersAcceptPostIsNotSetWhenPostIsNotAllowed(): void
    {
        BookFactory::new()->createOne();
        $book = Book::first();
        $response = $this->get($this->getIriFromResource($book), ['accept' => ['application/ld+json']]);
        $response->assertStatus(200);
        $response->assertHeaderMissing('accept-post');
    }

    public function testHeaderAllowReflectsResourceAllowedMethods(): void
    {
        $response = $this->get('/api/books', ['accept' => ['application/ld+json']]);
        $response->assertHeader('allow', 'OPTIONS, HEAD, POST, GET');

        BookFactory::new()->createOne();
        $book = Book::first();
        $response = $this->get($this->getIriFromResource($book), ['accept' => ['application/ld+json']]);
        $response->assertStatus(200);
        $response->assertHeader('allow', 'OPTIONS, HEAD, PUT, PATCH, GET, DELETE');
    }
}
