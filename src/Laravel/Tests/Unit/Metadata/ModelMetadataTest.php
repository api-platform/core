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

namespace ApiPlatform\Laravel\Tests\Unit\Metadata;

use ApiPlatform\Laravel\Eloquent\Metadata\ModelMetadata;
use PHPUnit\Framework\TestCase;
use Workbench\App\Models\Book;

class ModelMetadataTest extends TestCase
{
    public function testSeededAttributesAreReturnedWithoutDatabaseIntrospection(): void
    {
        $attributes = ['name' => ['name' => 'name', 'type' => 'string']];

        $modelMetadata = new ModelMetadata(attributes: [Book::class => $attributes]);

        $book = (new \ReflectionClass(Book::class))->newInstanceWithoutConstructor();

        $this->assertSame($attributes, $modelMetadata->getAttributes($book));
    }

    public function testSeededRelationsAreReturnedWithoutDatabaseIntrospection(): void
    {
        $relations = ['author' => ['name' => 'author', 'method_name' => 'author', 'related' => 'Workbench\\App\\Models\\Author']];

        $modelMetadata = new ModelMetadata(relations: [Book::class => $relations]);

        $book = (new \ReflectionClass(Book::class))->newInstanceWithoutConstructor();

        $this->assertSame($relations, $modelMetadata->getRelations($book));
    }
}
