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

namespace ApiPlatform\Laravel\Tests\Metadata;

use ApiPlatform\Laravel\Eloquent\Metadata\Factory\Property\EloquentPropertyMetadataFactory;
use ApiPlatform\Laravel\Eloquent\Metadata\Factory\Property\EloquentPropertyNameCollectionMetadataFactory;
use ApiPlatform\Laravel\Eloquent\Metadata\ModelMetadata;
use ApiPlatform\Laravel\Metadata\CachePropertyMetadataFactory;
use ApiPlatform\Laravel\Metadata\CachePropertyNameCollectionMetadataFactory;
use ApiPlatform\Laravel\Metadata\CacheResourceCollectionMetadataFactory;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;

/**
 * Tests for issue #8585.
 *
 * A worker booting while a table is missing (a deploy running its migrations, for instance)
 * must not store the metadata it read from that missing table in the persistent cache store:
 * the next worker would otherwise keep serving it after the table is created.
 * Each "worker" below is a new factory chain with a new ModelMetadata, sharing the store.
 */
final class CacheMetadataFactoriesMissingTableTest extends TestCase
{
    use RefreshDatabase;
    use WithWorkbench;

    private const TABLE = 'issue8585_books';

    public function testPropertyNamesReadFromAMissingTableAreNotCached(): void
    {
        $model = $this->createModel();

        $this->assertSame([], iterator_to_array($this->createPropertyNameCollectionFactory()->create($model::class)));

        $this->createTable();

        $this->assertSame(['id', 'title'], iterator_to_array($this->createPropertyNameCollectionFactory()->create($model::class)));
    }

    public function testPropertyMetadataReadFromAMissingTableIsNotCached(): void
    {
        $model = $this->createModel();

        $this->assertNull($this->createPropertyMetadataFactory()->create($model::class, 'title')->getNativeType());

        $this->createTable();

        $this->assertNotNull($this->createPropertyMetadataFactory()->create($model::class, 'title')->getNativeType());
    }

    public function testResourceMetadataBuiltFromAMissingTableIsNotCached(): void
    {
        $model = $this->createModel();

        $this->assertNull($this->createResourceMetadataCollectionFactory($model)->create($model::class)[0]->getDescription());

        $this->createTable();

        $this->assertSame('id, title', $this->createResourceMetadataCollectionFactory($model)->create($model::class)[0]->getDescription());
    }

    public function testMetadataIsCachedOnceTheTableExists(): void
    {
        $model = $this->createModel();
        $this->createTable();

        $this->createPropertyNameCollectionFactory()->create($model::class);
        Schema::drop(self::TABLE);

        // served from the store: the table is not read again
        $this->assertSame(['id', 'title'], iterator_to_array($this->createPropertyNameCollectionFactory()->create($model::class)));
    }

    private function createModel(): Model
    {
        Cache::store('array')->clear();

        return new class extends Model {
            protected $table = 'issue8585_books';
        };
    }

    private function createTable(): void
    {
        Schema::create(self::TABLE, static function (Blueprint $table): void {
            $table->id();
            $table->string('title');
        });
    }

    private function createPropertyNameCollectionFactory(): CachePropertyNameCollectionMetadataFactory
    {
        $modelMetadata = new ModelMetadata();

        return new CachePropertyNameCollectionMetadataFactory(
            new EloquentPropertyNameCollectionMetadataFactory($modelMetadata, null, $this->app->make(ResourceClassResolverInterface::class)),
            'array',
            $modelMetadata,
        );
    }

    private function createPropertyMetadataFactory(): CachePropertyMetadataFactory
    {
        $modelMetadata = new ModelMetadata();

        return new CachePropertyMetadataFactory(new EloquentPropertyMetadataFactory($modelMetadata), 'array', $modelMetadata);
    }

    /**
     * The resource metadata depends on the table through the property factories: this one
     * describes the resource with the columns it reads.
     */
    private function createResourceMetadataCollectionFactory(Model $model): CacheResourceCollectionMetadataFactory
    {
        $modelMetadata = new ModelMetadata();
        $decorated = new class($modelMetadata, $model) implements ResourceMetadataCollectionFactoryInterface {
            public function __construct(private readonly ModelMetadata $modelMetadata, private readonly Model $model)
            {
            }

            public function create(string $resourceClass): ResourceMetadataCollection
            {
                $columns = array_keys($this->modelMetadata->getAttributes($this->model));

                return new ResourceMetadataCollection($resourceClass, [new ApiResource(description: $columns ? implode(', ', $columns) : null)]);
            }
        };

        return new CacheResourceCollectionMetadataFactory($decorated, 'array', $modelMetadata);
    }
}
