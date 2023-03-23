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

namespace ApiPlatform\Metadata\Tests\Property\Factory;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Property\Factory\CachedPropertyMetadataFactory;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Tests\Fixtures\ApiResource\Dummy;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Cache\CacheException;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
class CachedPropertyMetadataFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testCreateWithItemHit(): void
    {
        $propertyMetadata = (new ApiProperty())->withDescription('a dummy')->withReadable(true)->withWritable(true);
        $cacheItem = $this->prophesize(CacheItemInterface::class);
        $cacheItem->isHit()->willReturn(true)->shouldBeCalled();
        $cacheItem->get()->willReturn($propertyMetadata)->shouldBeCalled();

        $cacheItemPool = $this->prophesize(CacheItemPoolInterface::class);
        $cacheItemPool->getItem($this->generateCacheKey())->willReturn($cacheItem->reveal())->shouldBeCalled();

        $decoratedPropertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);

        $cachedPropertyMetadataFactory = new CachedPropertyMetadataFactory($cacheItemPool->reveal(), $decoratedPropertyMetadataFactory->reveal());
        $resultedPropertyMetadata = $cachedPropertyMetadataFactory->create(Dummy::class, 'dummy');

        $this->assertEquals($propertyMetadata, $resultedPropertyMetadata);
        $this->assertEquals($propertyMetadata, $cachedPropertyMetadataFactory->create(Dummy::class, 'dummy'), 'Trigger the local cache');
    }

    public function testCreateWithItemNotHit(): void
    {
        $propertyMetadata = (new ApiProperty())->withDescription('a dummy')->withReadable(true)->withWritable(true);

        $decoratedPropertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $decoratedPropertyMetadataFactory->create(Dummy::class, 'dummy', [])->willReturn($propertyMetadata)->shouldBeCalled();

        $cacheItem = $this->prophesize(CacheItemInterface::class);
        $cacheItem->isHit()->willReturn(false)->shouldBeCalled();
        $cacheItem->set($propertyMetadata)->willReturn($cacheItem->reveal())->shouldBeCalled();

        $cacheItemPool = $this->prophesize(CacheItemPoolInterface::class);
        $cacheItemPool->getItem($this->generateCacheKey())->willReturn($cacheItem->reveal())->shouldBeCalled();
        $cacheItemPool->save($cacheItem->reveal())->willReturn(true)->shouldBeCalled();

        $cachedPropertyMetadataFactory = new CachedPropertyMetadataFactory($cacheItemPool->reveal(), $decoratedPropertyMetadataFactory->reveal());
        $resultedPropertyMetadata = $cachedPropertyMetadataFactory->create(Dummy::class, 'dummy');

        $this->assertEquals($propertyMetadata, $resultedPropertyMetadata);
        $this->assertEquals($propertyMetadata, $cachedPropertyMetadataFactory->create(Dummy::class, 'dummy'), 'Trigger the local cache');
    }

    public function testCreateWithGetCacheItemThrowsCacheException(): void
    {
        $propertyMetadata = (new ApiProperty())->withDescription('a dummy')->withReadable(true)->withWritable(true);

        $decoratedPropertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $decoratedPropertyMetadataFactory->create(Dummy::class, 'dummy', [])->willReturn($propertyMetadata)->shouldBeCalled();

        $cacheException = new class() extends \Exception implements CacheException {};

        $cacheItemPool = $this->prophesize(CacheItemPoolInterface::class);
        $cacheItemPool->getItem($this->generateCacheKey())->willThrow($cacheException)->shouldBeCalled();

        $cachedPropertyMetadataFactory = new CachedPropertyMetadataFactory($cacheItemPool->reveal(), $decoratedPropertyMetadataFactory->reveal());
        $resultedPropertyMetadata = $cachedPropertyMetadataFactory->create(Dummy::class, 'dummy');

        $this->assertEquals($propertyMetadata, $resultedPropertyMetadata);
        $this->assertEquals($propertyMetadata, $cachedPropertyMetadataFactory->create(Dummy::class, 'dummy'), 'Trigger the local cache');
    }

    private function generateCacheKey(string $resourceClass = Dummy::class, string $property = 'dummy', array $options = []): string
    {
        return CachedPropertyMetadataFactory::CACHE_KEY_PREFIX.md5(serialize([$resourceClass, $property, $options]));
    }
}
