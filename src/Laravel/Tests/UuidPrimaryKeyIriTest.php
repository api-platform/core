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

use ApiPlatform\Laravel\Eloquent\Metadata\Factory\Property\EloquentPropertyMetadataFactory;
use ApiPlatform\Laravel\Eloquent\Metadata\Factory\Property\EloquentPropertyNameCollectionMetadataFactory;
use ApiPlatform\Laravel\Eloquent\Metadata\ModelMetadata;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Resource\Factory\LinkFactory;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;
use Workbench\App\Models\Building;
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
        $buildings = BuildingFactory::new()->createMany(3);

        $response = $this->get('/api/buildings', ['accept' => 'application/vnd.api+json']);
        $response->assertStatus(200);

        $ids = array_column($response->json('data'), 'id');

        foreach ($buildings as $building) {
            $this->assertContains('/api/buildings/'.$building->uuid, $ids);
        }
    }

    /**
     * Reproduces #8571: on a long-lived worker (e.g. Octane), a resource's identifiers can be
     * resolved once while its table doesn't exist yet (migrations still pending). LinkFactory is
     * a container singleton, so that empty result must not be cached forever — it has to recover
     * once the table becomes available. We build LinkFactory's own collaborators directly (not the
     * app's shared, `rememberForever`-cached services) so this test observes LinkFactory's cache
     * guard in isolation, against the real Building model and a real schema change, instead of the
     * app-wide resource metadata cache that permanently freezes whatever route registration sees at
     * boot.
     */
    public function testLinkFactoryRecoversUuidIdentifierAfterTableBecomesAvailable(): void
    {
        Schema::drop('buildings');

        $resourceClassResolver = $this->app->make(ResourceClassResolverInterface::class);
        $modelMetadata = new ModelMetadata();
        $linkFactory = new LinkFactory(
            new EloquentPropertyNameCollectionMetadataFactory($modelMetadata, null, $resourceClassResolver),
            new EloquentPropertyMetadataFactory($modelMetadata),
            $resourceClassResolver,
        );

        $emptyLink = $linkFactory->completeLink((new Link())->withFromClass(Building::class));
        $this->assertSame([], $emptyLink->getIdentifiers());

        Schema::create('buildings', static function (Blueprint $table): void {
            $table->uuid('uuid')->primary();
            $table->string('name');
            $table->uuid('user_uuid');
            $table->timestamps();
        });

        $resolvedLink = $linkFactory->completeLink((new Link())->withFromClass(Building::class));
        $this->assertSame(['uuid'], $resolvedLink->getIdentifiers());
    }
}
