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

namespace ApiPlatform\Core\Tests\Metadata\Property\Factory;

use ApiPlatform\Core\Metadata\Property\Factory\CachedPropertyMetadataFactory;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheException;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
class CachedPropertyMetadataFactoryTest extends TestCase
{
    public function testCreateWithItemHit()
    {
        $cacheItem = $this->prophesize(CacheItemInterface::class);
        $cacheItem->isHit()->willReturn(true)->shouldBeCalled();
        $cacheItem->get()->willReturn(new PropertyMetadata(null, 'A dummy', true, true, null, null, false, false))->shouldBeCalled();

        $cacheItemPool = $this->prophesize(CacheItemPoolInterface::class);
        $cacheItemPool->getItem($this->generateCacheKey())->willReturn($cacheItem->reveal())->shouldBeCalled();

        $decoratedPropertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);

        $cachedPropertyMetadataFactory = new CachedPropertyMetadataFactory($cacheItemPool->reveal(), $decoratedPropertyMetadataFactory->reveal());
        $resultedPropertyMetadata = $cachedPropertyMetadataFactory->create(Dummy::class, 'dummy');

        $expectedResult = new PropertyMetadata(null, 'A dummy', true, true, null, null, false, false);
        $this->assertEquals($expectedResult, $resultedPropertyMetadata);
        $this->assertEquals($expectedResult, $cachedPropertyMetadataFactory->create(Dummy::class, 'dummy'), 'Trigger the local cache');
    }

    public function testCreateWithItemNotHit()
    {
        $propertyMetadata = new PropertyMetadata(null, 'A dummy', true, true, null, null, false, false);

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

        $expectedResult = new PropertyMetadata(null, 'A dummy', true, true, null, null, false, false);
        $this->assertEquals($expectedResult, $resultedPropertyMetadata);
        $this->assertEquals($expectedResult, $cachedPropertyMetadataFactory->create(Dummy::class, 'dummy'), 'Trigger the local cache');
    }

    public function testCreateWithGetCacheItemThrowsCacheException()
    {
        $decoratedPropertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $decoratedPropertyMetadataFactory->create(Dummy::class, 'dummy', [])->willReturn(new PropertyMetadata(null, 'A dummy', true, true, null, null, false, false))->shouldBeCalled();

        $cacheException = $this->prophesize(CacheException::class);
        $cacheException->willExtend(\Exception::class);

        $cacheItemPool = $this->prophesize(CacheItemPoolInterface::class);
        $cacheItemPool->getItem($this->generateCacheKey())->willThrow($cacheException->reveal())->shouldBeCalled();

        $cachedPropertyMetadataFactory = new CachedPropertyMetadataFactory($cacheItemPool->reveal(), $decoratedPropertyMetadataFactory->reveal());
        $resultedPropertyMetadata = $cachedPropertyMetadataFactory->create(Dummy::class, 'dummy');

        $expectedResult = new PropertyMetadata(null, 'A dummy', true, true, null, null, false, false);
        $this->assertEquals($expectedResult, $resultedPropertyMetadata);
        $this->assertEquals($expectedResult, $cachedPropertyMetadataFactory->create(Dummy::class, 'dummy'), 'Trigger the local cache');
    }

    private function generateCacheKey(string $resourceClass = Dummy::class, string $property = 'dummy', array $options = [])
    {
        return CachedPropertyMetadataFactory::CACHE_KEY_PREFIX.md5(serialize([$resourceClass, $property, $options]));
    }
}
