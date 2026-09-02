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

use ApiPlatform\Metadata\IriConverterInterface;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;
use Workbench\App\Models\Author;
use Workbench\App\Models\BookVariant;

class IriConverterRelationTest extends TestCase
{
    use WithWorkbench;

    public function testRelationKeepsItsSkolemIriAfterTheRelatedModelWasResolved(): void
    {
        $iriConverter = $this->app->make(IriConverterInterface::class);

        $model = new BookVariant();
        $model->id = 1;

        $this->assertSame('/api/books/1', $iriConverter->getIriFromResource($model));
        $this->assertStringStartsWith('/api/.well-known/genid/', $iriConverter->getIriFromResource($this->relation()));
    }

    public function testRelationKeepsItsSkolemIriWhenResolvedFirst(): void
    {
        $iriConverter = $this->app->make(IriConverterInterface::class);

        $model = new BookVariant();
        $model->id = 1;

        $this->assertStringStartsWith('/api/.well-known/genid/', $iriConverter->getIriFromResource($this->relation()));
        $this->assertSame('/api/books/1', $iriConverter->getIriFromResource($model));
    }

    /**
     * @return HasMany<BookVariant, Author>
     */
    private function relation(): HasMany
    {
        $related = new BookVariant();
        $related->id = 1;

        return new HasMany($related->newQuery(), new Author(), 'author_id', 'id');
    }
}
