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

class ApiTest extends TestCase
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
            $config->set('api-platform.routes.domain', 'http://test.com');
            $config->set('app.debug', true);
            $config->set('api-platform.formats', ['jsonld' => ['application/ld+json']]);
            $config->set('api-platform.docs_formats', ['jsonld' => ['application/ld+json']]);
        });
    }

    public function testDomainCanBeSet(): void
    {
        $response = $this->get('http://foobar.com/api/', ['accept' => ['application/ld+json']]);
        $response->assertNotFound();

        $response = $this->get('http://test.com/api/', ['accept' => ['application/ld+json']]);
        $response->assertSuccessful();
    }
}
