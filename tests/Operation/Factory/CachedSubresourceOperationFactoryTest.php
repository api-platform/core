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

namespace ApiPlatform\Core\Tests\Operation\Factory;

use ApiPlatform\Core\Operation\Factory\CachedSubresourceOperationFactory;
use ApiPlatform\Core\Operation\Factory\SubresourceOperationFactoryInterface;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use Psr\Cache\CacheException;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
class CachedSubresourceOperationFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateWithItemHit()
    {
        $cacheItem = $this->prophesize(CacheItemInterface::class);
        $cacheItem->isHit()->willReturn(true)->shouldBeCalled();
        $cacheItem->get()->willReturn(['foo' => 'bar'])->shouldBeCalled();

        $cacheItemPool = $this->prophesize(CacheItemPoolInterface::class);
        $cacheItemPool->getItem($this->generateCacheKey())->willReturn($cacheItem->reveal())->shouldBeCalled();

        $decoratedSubresourceOperationFactory = $this->prophesize(SubresourceOperationFactoryInterface::class);
        $decoratedSubresourceOperationFactory->create()->shouldNotBeCalled();

        $cachedSubresourceOperationFactory = new CachedSubresourceOperationFactory($cacheItemPool->reveal(), $decoratedSubresourceOperationFactory->reveal());
        $resultedSubresourceOperation = $cachedSubresourceOperationFactory->create(Dummy::class);

        $this->assertEquals(['foo' => 'bar'], $resultedSubresourceOperation);
    }

    public function testCreateWithItemNotHit()
    {
        $cacheItem = $this->prophesize(CacheItemInterface::class);
        $cacheItem->isHit()->willReturn(false)->shouldBeCalled();
        $cacheItem->set(['foo' => 'bar'])->willReturn($cacheItem->reveal())->shouldBeCalled();

        $cacheItemPool = $this->prophesize(CacheItemPoolInterface::class);
        $cacheItemPool->getItem($this->generateCacheKey())->willReturn($cacheItem->reveal())->shouldBeCalled();
        $cacheItemPool->save($cacheItem->reveal())->willReturn(true)->shouldBeCalled();

        $decoratedSubresourceOperationFactory = $this->prophesize(SubresourceOperationFactoryInterface::class);
        $decoratedSubresourceOperationFactory->create(Dummy::class)->shouldBeCalled()->willReturn(['foo' => 'bar']);

        $cachedSubresourceOperationFactory = new CachedSubresourceOperationFactory($cacheItemPool->reveal(), $decoratedSubresourceOperationFactory->reveal());
        $resultedSubresourceOperation = $cachedSubresourceOperationFactory->create(Dummy::class);

        $this->assertEquals(['foo' => 'bar'], $resultedSubresourceOperation);
    }

    public function testCreateWithGetCacheItemThrowsCacheException()
    {
        $cacheException = $this->prophesize(CacheException::class);
        $cacheException->willExtend(\Exception::class);

        $cacheItemPool = $this->prophesize(CacheItemPoolInterface::class);
        $cacheItemPool->getItem($this->generateCacheKey())->willThrow($cacheException->reveal())->shouldBeCalled();

        $decoratedSubresourceOperationFactory = $this->prophesize(SubresourceOperationFactoryInterface::class);
        $decoratedSubresourceOperationFactory->create(Dummy::class)->shouldBeCalled()->willReturn(['foo' => 'bar']);

        $cachedSubresourceOperationFactory = new CachedSubresourceOperationFactory($cacheItemPool->reveal(), $decoratedSubresourceOperationFactory->reveal());
        $resultedSubresourceOperation = $cachedSubresourceOperationFactory->create(Dummy::class);

        $this->assertEquals(['foo' => 'bar'], $resultedSubresourceOperation);
    }

    private function generateCacheKey(string $resourceClass = Dummy::class)
    {
        return CachedSubresourceOperationFactory::CACHE_KEY_PREFIX.md5($resourceClass);
    }
}
