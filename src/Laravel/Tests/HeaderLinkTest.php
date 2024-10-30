<?php

declare(strict_types=1);

use ApiPlatform\Laravel\Test\ApiTestAssertionsTrait;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;
use Workbench\Database\Factories\AuthorFactory;
use Workbench\Database\Factories\BookFactory;

class HeaderLinkTest extends TestCase
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
            $config->set('api-platform.formats', ['jsonapi' => ['application/vnd.api+json']]);
            $config->set('api-platform.docs_formats', ['jsonapi' => ['application/vnd.api+json']]);
        });
    }

    public function testHeaderLinkDoesNotExistWithoutJsonld(): void {
        BookFactory::new()->has(AuthorFactory::new())->count(10)->create();
        $response = $this->get('/api/books', ['accept' => 'application/vnd.api+json']);
        $response->assertStatus(200);
        $response->assertHeaderMissing('link');
        //$response->assertHeader('link', '<http://localhost/api/docs.jsonld>; rel="http://www.w3.org/ns/hydra/core#apiDocumentation"');
    }
}
