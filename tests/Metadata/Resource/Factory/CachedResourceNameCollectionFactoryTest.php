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

use ApiPlatform\Core\Metadata\Resource\Factory\CachedResourceNameCollectionFactory;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceNameCollection;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use Psr\Cache\CacheException;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
class CachedResourceNameCollectionFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateWithItemHit()
    {
        $cacheItem = $this->prophesize(CacheItemInterface::class);
        $cacheItem->isHit()->willReturn(true)->shouldBeCalled();
        $cacheItem->get()->willReturn(new ResourceNameCollection([Dummy::class]))->shouldBeCalled();

        $cacheItemPool = $this->prophesize(CacheItemPoolInterface::class);
        $cacheItemPool->getItem(CachedResourceNameCollectionFactory::CACHE_KEY)->willReturn($cacheItem->reveal())->shouldBeCalled();

        $decoratedResourceNameCollectionFactory = $this->prophesize(ResourceNameCollectionFactoryInterface::class);

        $cachedResourceNameCollectionFactory = new CachedResourceNameCollectionFactory($cacheItemPool->reveal(), $decoratedResourceNameCollectionFactory->reveal());
        $resultedResourceNameCollection = $cachedResourceNameCollectionFactory->create();

        $this->assertInstanceOf(ResourceNameCollection::class, $resultedResourceNameCollection);
        $this->assertEquals(new ResourceNameCollection([Dummy::class]), $resultedResourceNameCollection);
    }

    public function testCreateWithItemNotHit()
    {
        $resourceNameCollection = new ResourceNameCollection([Dummy::class]);

        $decoratedResourceNameCollectionFactory = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $decoratedResourceNameCollectionFactory->create()->willReturn($resourceNameCollection)->shouldBeCalled();

        $cacheItem = $this->prophesize(CacheItemInterface::class);
        $cacheItem->isHit()->willReturn(false)->shouldBeCalled();
        $cacheItem->set($resourceNameCollection)->willReturn($cacheItem->reveal())->shouldBeCalled();

        $cacheItemPool = $this->prophesize(CacheItemPoolInterface::class);
        $cacheItemPool->getItem(CachedResourceNameCollectionFactory::CACHE_KEY)->willReturn($cacheItem->reveal())->shouldBeCalled();
        $cacheItemPool->save($cacheItem->reveal())->willReturn(true)->shouldBeCalled();

        $cachedResourceNameCollectionFactory = new CachedResourceNameCollectionFactory($cacheItemPool->reveal(), $decoratedResourceNameCollectionFactory->reveal());
        $resultedResourceNameCollection = $cachedResourceNameCollectionFactory->create();

        $this->assertInstanceOf(ResourceNameCollection::class, $resultedResourceNameCollection);
        $this->assertEquals(new ResourceNameCollection([Dummy::class]), $resultedResourceNameCollection);
    }

    public function testCreateWithGetCacheItemThrowsCacheException()
    {
        $decoratedResourceNameCollectionFactory = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $decoratedResourceNameCollectionFactory->create()->willReturn(new ResourceNameCollection([Dummy::class]))->shouldBeCalled();

        $cacheException = $this->prophesize(CacheException::class);
        $cacheException->willExtend(\Exception::class);

        $cacheItemPool = $this->prophesize(CacheItemPoolInterface::class);
        $cacheItemPool->getItem(CachedResourceNameCollectionFactory::CACHE_KEY)->willThrow($cacheException->reveal())->shouldBeCalled();

        $cachedResourceNameCollectionFactory = new CachedResourceNameCollectionFactory($cacheItemPool->reveal(), $decoratedResourceNameCollectionFactory->reveal());
        $resultedResourceNameCollection = $cachedResourceNameCollectionFactory->create();

        $this->assertInstanceOf(ResourceNameCollection::class, $resultedResourceNameCollection);
        $this->assertEquals(new ResourceNameCollection([Dummy::class]), $resultedResourceNameCollection);
    }
}
