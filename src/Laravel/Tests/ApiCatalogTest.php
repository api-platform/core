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

/**
 * @see https://www.rfc-editor.org/rfc/rfc9727.html
 */
class ApiCatalogTest extends TestCase
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
            $config->set('app.debug', true);
            $config->set('api-platform.docs_formats', ['jsonld' => ['application/ld+json'], 'html' => ['text/html']]);
        });
    }

    public function testTheCatalogIsServedOutsideTheApiPrefix(): void
    {
        $response = $this->get('/.well-known/api-catalog');

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/linkset+json; profile="https://www.rfc-editor.org/info/rfc9727"');
        $response->assertHeader('link', '<http://localhost/.well-known/api-catalog>; rel="api-catalog"');

        $contexts = array_column($response->json('linkset'), null, 'anchor');

        $this->assertSame([['href' => 'http://localhost/api']], $contexts['http://localhost/.well-known/api-catalog']['item']);

        $api = $contexts['http://localhost/api'];
        $this->assertContains(['href' => 'http://localhost/api/docs.jsonld', 'type' => 'application/ld+json'], $api['service-meta']);
        $this->assertContains(['href' => 'http://localhost/api/docs', 'type' => 'text/html'], $api['service-doc']);
    }
}
