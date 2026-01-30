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

use Illuminate\Contracts\Config\Repository;
use Illuminate\Foundation\Application;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;

class NoOperationResourceTest extends TestCase
{
    use WithWorkbench;

    /**
     * @param Application $app
     */
    protected function defineEnvironment($app): void
    {
        tap($app['config'], static function (Repository $config): void {
            $config->set('api-platform.resources', [app_path('ApiResource')]);
        });
    }

    public function testNoRoutesAreGeneratedForNoOperationResource(): void
    {
        $response = $this->get('/api/no_operations/notfound', headers: ['accept' => 'application/ld+json']);
        $response->assertNotFound();
        $response->assertJson(['detail' => 'This route does not aim to be called.']);
    }
}
