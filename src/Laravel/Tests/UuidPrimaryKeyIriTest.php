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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;
use Workbench\Database\Factories\BuildingFactory;

class UuidPrimaryKeyIriTest extends TestCase
{
    use RefreshDatabase;
    use WithWorkbench;

    /**
     * @param Application $app
     */
    protected function defineEnvironment($app): void
    {
        tap($app['config'], static function (Repository $config): void {
            $config->set('api-platform.formats', [
                'jsonapi' => ['application/vnd.api+json'],
            ]);
        });
    }

    public function testCollectionItemsHaveWellFormedIriWithUuidPrimaryKeyInJsonApi(): void
    {
        $buildings = BuildingFactory::new()->count(3)->create();

        $response = $this->get('/api/buildings', ['accept' => 'application/vnd.api+json']);
        $response->assertStatus(200);

        $ids = array_column($response->json('data'), 'id');

        foreach ($buildings as $building) {
            $this->assertContains('/api/buildings/'.$building->uuid, $ids);
        }
    }
}
