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

namespace ApiPlatform\Metadata\Tests\Resource\Factory;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Resource\Factory\CachedResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\Tests\Fixtures\CountingApiResource;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

final class CachedResourceMetadataCollectionFactoryTest extends TestCase
{
    public function testItCallsTheDecoratedFactoryOnce(): void
    {
        $factory = $this->factory($this->collection(), 1);

        $factory->create('class');
        $factory->create('class');
    }

    public function testItReturnsTheSameInstanceOnEveryCall(): void
    {
        $factory = $this->factory($this->collection());

        $this->assertSame($factory->create('class'), $factory->create('class'));
    }

    public function testTheOperationLookupIsNotRepeatedOnEveryCall(): void
    {
        $resource = (new CountingApiResource())->withOperations(new Operations(['name' => new Get()]));
        $factory = $this->factory(new ResourceMetadataCollection('class', [$resource]));

        $factory->create('class')->getOperation('name');
        $this->assertSame(1, $resource->scanCount);

        $factory->create('class')->getOperation('name');
        $this->assertSame(1, $resource->scanCount);
    }

    private function collection(?Get $operation = null): ResourceMetadataCollection
    {
        return new ResourceMetadataCollection('class', [
            (new ApiResource())->withOperations(new Operations(['name' => $operation ?? new Get(name: 'name')])),
        ]);
    }

    private function factory(ResourceMetadataCollection $collection, ?int $expectedCreateCalls = null): CachedResourceMetadataCollectionFactory
    {
        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->method('isHit')->willReturn(false);
        $cacheItem->expects($this->once())->method('set')->with((array) $collection)->willReturnSelf();

        $cacheItemPool = $this->createMock(CacheItemPoolInterface::class);
        $cacheItemPool->method('getItem')->with($this->isString())->willReturn($cacheItem);
        $cacheItemPool->expects($this->once())->method('save')->with($cacheItem)->willReturn(true);

        $decorated = $this->createMock(ResourceMetadataCollectionFactoryInterface::class);
        $decorated->expects(null !== $expectedCreateCalls ? $this->exactly($expectedCreateCalls) : $this->any())
            ->method('create')
            ->with('class')
            ->willReturn($collection);

        return new CachedResourceMetadataCollectionFactory($cacheItemPool, $decorated);
    }
}
