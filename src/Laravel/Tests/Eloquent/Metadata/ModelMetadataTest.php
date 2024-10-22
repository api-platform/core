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

namespace ApiPlatform\Laravel\Tests\Eloquent\Metadata;

use ApiPlatform\Laravel\Eloquent\Metadata\ModelMetadata;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;
use Workbench\App\Models\Book;

/**
 * @author Tobias Oitzinger <tobiasoitzinger@gmail.com>
 */
final class ModelMetadataTest extends TestCase
{
    use RefreshDatabase;
    use WithWorkbench;

    public function testHiddenAttributesAreCorrectlyIdentified(): void
    {
        $model = new class extends Model {
            protected $hidden = ['secret'];

            /**
             * @return HasMany<Book>
             */
            public function secret(): HasMany // @phpstan-ignore-line
            {
                return $this->hasMany(Book::class); // @phpstan-ignore-line
            }
        };

        $metadata = new ModelMetadata();
        $this->assertCount(0, $metadata->getRelations($model));
    }

    public function testVisibleAttributesAreCorrectlyIdentified(): void
    {
        $model = new class extends Model {
            protected $visible = ['secret'];

            /**
             * @return HasMany<Book>
             */
            public function secret(): HasMany // @phpstan-ignore-line
            {
                return $this->hasMany(Book::class); // @phpstan-ignore-line
            }
        };

        $metadata = new ModelMetadata();
        $this->assertCount(1, $metadata->getRelations($model));
    }

    public function testAllAttributesVisibleByDefault(): void
    {
        $model = new class extends Model {
            /**
             * @return HasMany<Book>
             */
            public function secret(): HasMany // @phpstan-ignore-line
            {
                return $this->hasMany(Book::class); // @phpstan-ignore-line
            }
        };

        $metadata = new ModelMetadata();
        $this->assertCount(1, $metadata->getRelations($model));
    }
}
