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

namespace ApiPlatform\Core\Tests\Metadata\Resource\Factory;

use ApiPlatform\Core\Metadata\Resource\Factory\CachedResourceMetadataFactory;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use Psr\Cache\CacheException;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
class CachedResourceMetadataFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateWithItemHit()
    {
        $cacheItem = $this->prophesize(CacheItemInterface::class);
        $cacheItem->isHit()->willReturn(true)->shouldBeCalled();
        $cacheItem->get()->willReturn(new ResourceMetadata(null, 'Dummy.'))->shouldBeCalled();

        $cacheItemPool = $this->prophesize(CacheItemPoolInterface::class);
        $cacheItemPool->getItem($this->generateCacheKey())->willReturn($cacheItem->reveal())->shouldBeCalled();

        $decoratedResourceMetadataFactory = $this->prophesize(ResourceMetadataFactoryInterface::class);

        $cachedResourceMetadataFactory = new CachedResourceMetadataFactory($cacheItemPool->reveal(), $decoratedResourceMetadataFactory->reveal());
        $resultedResourceMetadata = $cachedResourceMetadataFactory->create(Dummy::class);

        $this->assertInstanceOf(ResourceMetadata::class, $resultedResourceMetadata);
        $this->assertEquals(new ResourceMetadata(null, 'Dummy.'), $resultedResourceMetadata);
    }

    public function testCreateWithItemNotHit()
    {
        $propertyMetadata = new ResourceMetadata(null, 'Dummy.');

        $decoratedResourceMetadataFactory = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $decoratedResourceMetadataFactory->create(Dummy::class)->willReturn($propertyMetadata)->shouldBeCalled();

        $cacheItem = $this->prophesize(CacheItemInterface::class);
        $cacheItem->isHit()->willReturn(false)->shouldBeCalled();
        $cacheItem->set($propertyMetadata)->willReturn($cacheItem->reveal())->shouldBeCalled();

        $cacheItemPool = $this->prophesize(CacheItemPoolInterface::class);
        $cacheItemPool->getItem($this->generateCacheKey())->willReturn($cacheItem->reveal())->shouldBeCalled();
        $cacheItemPool->save($cacheItem->reveal())->willReturn(true)->shouldBeCalled();

        $cachedResourceMetadataFactory = new CachedResourceMetadataFactory($cacheItemPool->reveal(), $decoratedResourceMetadataFactory->reveal());
        $resultedResourceMetadata = $cachedResourceMetadataFactory->create(Dummy::class);

        $this->assertInstanceOf(ResourceMetadata::class, $resultedResourceMetadata);
        $this->assertEquals(new ResourceMetadata(null, 'Dummy.'), $resultedResourceMetadata);
    }

    public function testCreateWithGetCacheItemThrowsCacheException()
    {
        $decoratedResourceMetadataFactory = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $decoratedResourceMetadataFactory->create(Dummy::class)->willReturn(new ResourceMetadata(null, 'Dummy.'))->shouldBeCalled();

        $cacheException = $this->prophesize(CacheException::class);
        $cacheException->willExtend(\Exception::class);

        $cacheItemPool = $this->prophesize(CacheItemPoolInterface::class);
        $cacheItemPool->getItem($this->generateCacheKey())->willThrow($cacheException->reveal())->shouldBeCalled();

        $cachedResourceMetadataFactory = new CachedResourceMetadataFactory($cacheItemPool->reveal(), $decoratedResourceMetadataFactory->reveal());
        $resultedResourceMetadata = $cachedResourceMetadataFactory->create(Dummy::class);

        $this->assertInstanceOf(ResourceMetadata::class, $resultedResourceMetadata);
        $this->assertEquals(new ResourceMetadata(null, 'Dummy.'), $resultedResourceMetadata);
    }

    private function generateCacheKey(string $resourceClass = Dummy::class)
    {
        return CachedResourceMetadataFactory::CACHE_KEY_PREFIX.md5(serialize([$resourceClass]));
    }
}
