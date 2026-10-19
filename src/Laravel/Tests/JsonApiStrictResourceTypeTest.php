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
use Illuminate\Testing\TestResponse;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Workbench\App\Models\Author;
use Workbench\Database\Factories\AuthorFactory;

class JsonApiStrictResourceTypeTest extends TestCase
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
            $config->set('api-platform.jsonapi', ['use_iri_as_id' => false, 'strict_resource_type' => true]);
            $config->set('api-platform.defaults', ['route_prefix' => '/api']);
            $config->set('app.debug', true);
        });
    }

    public function testMissingTypeIsABadRequest(): void
    {
        $this->postBook(['attributes' => ['name' => 'Don Quichotte', 'isbn' => '9781234567897']])->assertStatus(400);
    }

    public function testWrongTypeIsAConflict(): void
    {
        $this->postBook(['type' => 'Author', 'attributes' => ['name' => 'Don Quichotte', 'isbn' => '9781234567897']])->assertStatus(409);
    }

    public function testCorrectTypeIsAccepted(): void
    {
        AuthorFactory::new()->create();

        $this->postBook([
            'type' => 'Book',
            'attributes' => ['name' => 'Don Quichotte', 'isbn' => '9781234567897'],
            'relationships' => ['author' => ['data' => ['type' => 'Author', 'id' => $this->getIriFromResource(Author::find(1))]]],
        ])->assertStatus(201);
    }

    /**
     * @param array<string, mixed> $resource
     *
     * @return TestResponse<Response>
     */
    private function postBook(array $resource): TestResponse
    {
        return $this->postJson('/api/books', ['data' => $resource], [
            'accept' => 'application/vnd.api+json',
            'content_type' => 'application/vnd.api+json',
        ]);
    }
}
