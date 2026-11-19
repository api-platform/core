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

use ApiPlatform\Laravel\Metadata\CachePropertyMetadataFactory;
use ApiPlatform\Laravel\Metadata\CachePropertyNameCollectionMetadataFactory;
use ApiPlatform\Laravel\Metadata\CacheResourceCollectionMetadataFactory;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use Illuminate\Support\Facades\Cache;
use Orchestra\Testbench\TestCase;

/**
 * Tests for issue #8413.
 *
 * Ensures the metadata cache factories serve repeated lookups from an in-memory cache
 * instead of consulting the Laravel cache store on every call. Wiping the persistent
 * store between two identical lookups proves the second one is answered from memory:
 * without the in-memory cache, it would fall through to the (now empty) store and hit
 * the decorated factory again.
 */
class CacheMetadataFactoriesTest extends TestCase
{
    public function testPropertyMetadataIsMemoizedInMemory(): void
    {
        $property = new ApiProperty();
        $decorated = $this->createMock(PropertyMetadataFactoryInterface::class);
        $decorated->expects($this->once())
            ->method('create')
            ->with('App\Models\Book', 'title', [])
            ->willReturn($property);

        $factory = new CachePropertyMetadataFactory($decorated, 'array');

        $this->assertSame($property, $factory->create('App\Models\Book', 'title'));

        Cache::store('array')->clear();

        $this->assertSame($property, $factory->create('App\Models\Book', 'title'));
    }

    public function testPropertyNameCollectionIsMemoizedInMemory(): void
    {
        $collection = new PropertyNameCollection(['title']);
        $decorated = $this->createMock(PropertyNameCollectionFactoryInterface::class);
        $decorated->expects($this->once())
            ->method('create')
            ->with('App\Models\Book', [])
            ->willReturn($collection);

        $factory = new CachePropertyNameCollectionMetadataFactory($decorated, 'array');

        $this->assertSame($collection, $factory->create('App\Models\Book'));

        Cache::store('array')->clear();

        $this->assertSame($collection, $factory->create('App\Models\Book'));
    }

    public function testResourceMetadataCollectionIsMemoizedInMemory(): void
    {
        $collection = new ResourceMetadataCollection('App\Models\Book');
        $decorated = $this->createMock(ResourceMetadataCollectionFactoryInterface::class);
        $decorated->expects($this->once())
            ->method('create')
            ->with('App\Models\Book')
            ->willReturn($collection);

        $factory = new CacheResourceCollectionMetadataFactory($decorated, 'array');

        $this->assertSame($collection, $factory->create('App\Models\Book'));

        Cache::store('array')->clear();

        $this->assertSame($collection, $factory->create('App\Models\Book'));
    }

    public function testDifferentKeysAreCachedIndependently(): void
    {
        $title = new ApiProperty(description: 'title');
        $isbn = new ApiProperty(description: 'isbn');
        $decorated = $this->createMock(PropertyMetadataFactoryInterface::class);
        $decorated->expects($this->exactly(2))
            ->method('create')
            ->willReturnCallback(static fn (string $resourceClass, string $property) => match ($property) {
                'title' => $title,
                'isbn' => $isbn,
                default => throw new \LogicException(\sprintf('Unexpected property "%s".', $property)),
            });

        $factory = new CachePropertyMetadataFactory($decorated, 'array');

        $this->assertSame($title, $factory->create('App\Models\Book', 'title'));
        $this->assertSame($isbn, $factory->create('App\Models\Book', 'isbn'));
        $this->assertSame($title, $factory->create('App\Models\Book', 'title'));
        $this->assertSame($isbn, $factory->create('App\Models\Book', 'isbn'));
    }

    public function testMetadataIsStoredInThePersistentCacheStore(): void
    {
        $property = new ApiProperty();
        $decorated = $this->createMock(PropertyMetadataFactoryInterface::class);
        $decorated->expects($this->once())
            ->method('create')
            ->with('App\Models\Book', 'title', [])
            ->willReturn($property);

        $factory = new CachePropertyMetadataFactory($decorated, 'array');
        $factory->create('App\Models\Book', 'title');

        $key = hash('xxh3', serialize(['resource_class' => 'App\Models\Book', 'property' => 'title']));
        $this->assertSame($property, Cache::store('array')->get($key));
    }
}
