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

namespace ApiPlatform\Core\Tests\Bridge\Symfony\Routing;

use ApiPlatform\Core\Api\OperationType;
use ApiPlatform\Core\Bridge\Symfony\Routing\CachedRouteNameResolver;
use ApiPlatform\Core\Bridge\Symfony\Routing\RouteNameResolverInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Cache\CacheException;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * @author Teoh Han Hui <teohhanhui@gmail.com>
 */
class CachedRouteNameResolverTest extends TestCase
{
    public function testConstruct()
    {
        $cacheItemPoolProphecy = $this->prophesize(CacheItemPoolInterface::class);

        $decoratedProphecy = $this->prophesize(RouteNameResolverInterface::class);

        $cachedRouteNameResolver = new CachedRouteNameResolver($cacheItemPoolProphecy->reveal(), $decoratedProphecy->reveal());

        $this->assertInstanceOf(RouteNameResolverInterface::class, $cachedRouteNameResolver);
    }

    public function testGetRouteNameForItemRouteWithNoMatchingRoute()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No item route associated with the type "AppBundle\\Entity\\User".');

        $cacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $cacheItemProphecy->isHit()->willReturn(false)->shouldBeCalled();

        $cacheItemPoolProphecy = $this->prophesize(CacheItemPoolInterface::class);
        $cacheItemPoolProphecy->getItem(Argument::type('string'))->willReturn($cacheItemProphecy);
        $cacheItemPoolProphecy->save($cacheItemProphecy)->shouldNotBeCalled();

        $decoratedProphecy = $this->prophesize(RouteNameResolverInterface::class);
        $decoratedProphecy->getRouteName('AppBundle\Entity\User', OperationType::ITEM, [])
            ->willThrow(new InvalidArgumentException('No item route associated with the type "AppBundle\Entity\User".'))
            ->shouldBeCalled();

        $cachedRouteNameResolver = new CachedRouteNameResolver($cacheItemPoolProphecy->reveal(), $decoratedProphecy->reveal());
        $cachedRouteNameResolver->getRouteName('AppBundle\Entity\User', OperationType::ITEM);
    }

    public function testGetRouteNameForItemRouteOnCacheMiss()
    {
        $cacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $cacheItemProphecy->isHit()->willReturn(false)->shouldBeCalledTimes(1);
        $cacheItemProphecy->set('some_item_route')->shouldBeCalledTimes(1);

        $cacheItemPoolProphecy = $this->prophesize(CacheItemPoolInterface::class);
        $cacheItemPoolProphecy->getItem(Argument::type('string'))->shouldBeCalledTimes(1)->willReturn($cacheItemProphecy);
        $cacheItemPoolProphecy->save($cacheItemProphecy)->shouldBeCalledTimes(1)->willReturn(true);

        $decoratedProphecy = $this->prophesize(RouteNameResolverInterface::class);
        $decoratedProphecy->getRouteName('AppBundle\Entity\User', false, [])->willReturn('some_item_route')->shouldBeCalledTimes(1);

        $cachedRouteNameResolver = new CachedRouteNameResolver($cacheItemPoolProphecy->reveal(), $decoratedProphecy->reveal());

        $this->assertSame('some_item_route', $cachedRouteNameResolver->getRouteName('AppBundle\Entity\User', false));
        $this->assertSame('some_item_route', $cachedRouteNameResolver->getRouteName('AppBundle\Entity\User', false), 'Trigger the local cache');
    }

    public function testGetRouteNameForItemRouteOnCacheHit()
    {
        $cacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $cacheItemProphecy->isHit()->shouldBeCalledTimes(1)->willReturn(true);
        $cacheItemProphecy->get()->shouldBeCalledTimes(1)->willReturn('some_item_route');

        $cacheItemPoolProphecy = $this->prophesize(CacheItemPoolInterface::class);
        $cacheItemPoolProphecy->getItem(Argument::type('string'))->shouldBeCalledTimes(1)->willReturn($cacheItemProphecy);
        $cacheItemPoolProphecy->save($cacheItemProphecy)->shouldNotBeCalled();

        $decoratedProphecy = $this->prophesize(RouteNameResolverInterface::class);
        $decoratedProphecy->getRouteName(Argument::cetera())->shouldNotBeCalled();

        $cachedRouteNameResolver = new CachedRouteNameResolver($cacheItemPoolProphecy->reveal(), $decoratedProphecy->reveal());

        $this->assertSame('some_item_route', $cachedRouteNameResolver->getRouteName('AppBundle\Entity\User', OperationType::ITEM));
        $this->assertSame('some_item_route', $cachedRouteNameResolver->getRouteName('AppBundle\Entity\User', OperationType::ITEM), 'Trigger the local cache');
    }

    public function testGetRouteNameForCollectionRouteWithNoMatchingRoute()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No collection route associated with the type "AppBundle\\Entity\\User".');

        $cacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $cacheItemProphecy->isHit()->willReturn(false)->shouldBeCalled();

        $cacheItemPoolProphecy = $this->prophesize(CacheItemPoolInterface::class);
        $cacheItemPoolProphecy->getItem(Argument::type('string'))->willReturn($cacheItemProphecy);
        $cacheItemPoolProphecy->save($cacheItemProphecy)->shouldNotBeCalled();

        $decoratedProphecy = $this->prophesize(RouteNameResolverInterface::class);
        $decoratedProphecy->getRouteName('AppBundle\Entity\User', OperationType::COLLECTION, [])
            ->willThrow(new InvalidArgumentException('No collection route associated with the type "AppBundle\Entity\User".'))
            ->shouldBeCalled();

        $cachedRouteNameResolver = new CachedRouteNameResolver($cacheItemPoolProphecy->reveal(), $decoratedProphecy->reveal());
        $cachedRouteNameResolver->getRouteName('AppBundle\Entity\User', OperationType::COLLECTION);
    }

    public function testGetRouteNameForCollectionRouteOnCacheMiss()
    {
        $cacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $cacheItemProphecy->isHit()->shouldBeCalledTimes(1)->willReturn(false);
        $cacheItemProphecy->set('some_collection_route')->shouldBeCalledTimes(1);

        $cacheItemPoolProphecy = $this->prophesize(CacheItemPoolInterface::class);
        $cacheItemPoolProphecy->getItem(Argument::type('string'))->shouldBeCalledTimes(1)->willReturn($cacheItemProphecy);
        $cacheItemPoolProphecy->save($cacheItemProphecy)->shouldBeCalledTimes(1)->willReturn(true);

        $decoratedProphecy = $this->prophesize(RouteNameResolverInterface::class);
        $decoratedProphecy->getRouteName('AppBundle\Entity\User', true, [])->willReturn('some_collection_route')->shouldBeCalledTimes(1);

        $cachedRouteNameResolver = new CachedRouteNameResolver($cacheItemPoolProphecy->reveal(), $decoratedProphecy->reveal());

        $this->assertSame('some_collection_route', $cachedRouteNameResolver->getRouteName('AppBundle\Entity\User', true));
        $this->assertSame('some_collection_route', $cachedRouteNameResolver->getRouteName('AppBundle\Entity\User', true), 'Trigger the local cache');
    }

    public function testGetRouteNameForCollectionRouteOnCacheHit()
    {
        $cacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $cacheItemProphecy->isHit()->willReturn(true)->shouldBeCalledTimes(1);
        $cacheItemProphecy->get()->willReturn('some_collection_route')->shouldBeCalledTimes(1);

        $cacheItemPoolProphecy = $this->prophesize(CacheItemPoolInterface::class);
        $cacheItemPoolProphecy->getItem(Argument::type('string'))->shouldBeCalledTimes(1)->willReturn($cacheItemProphecy);
        $cacheItemPoolProphecy->save($cacheItemProphecy)->shouldNotBeCalled();

        $decoratedProphecy = $this->prophesize(RouteNameResolverInterface::class);
        $decoratedProphecy->getRouteName(Argument::cetera())->shouldNotBeCalled();

        $cachedRouteNameResolver = new CachedRouteNameResolver($cacheItemPoolProphecy->reveal(), $decoratedProphecy->reveal());

        $this->assertSame('some_collection_route', $cachedRouteNameResolver->getRouteName('AppBundle\Entity\User', OperationType::COLLECTION));
        $this->assertSame('some_collection_route', $cachedRouteNameResolver->getRouteName('AppBundle\Entity\User', OperationType::COLLECTION), 'Trigger the local cache');
    }

    public function testGetRouteNameWithCacheItemThrowsCacheException()
    {
        $cacheException = $this->prophesize(CacheException::class);
        $cacheException->willExtend(\Exception::class);

        $cacheItemPool = $this->prophesize(CacheItemPoolInterface::class);
        $cacheItemPool->getItem(Argument::type('string'))->shouldBeCalledTimes(1)->willThrow($cacheException->reveal());

        $decoratedProphecy = $this->prophesize(RouteNameResolverInterface::class);
        $decoratedProphecy->getRouteName('AppBundle\Entity\User', OperationType::ITEM, [])->willReturn('some_item_route')->shouldBeCalledTimes(1);

        $cachedRouteNameResolver = new CachedRouteNameResolver($cacheItemPool->reveal(), $decoratedProphecy->reveal());

        $this->assertSame('some_item_route', $cachedRouteNameResolver->getRouteName('AppBundle\Entity\User', OperationType::ITEM));
        $this->assertSame('some_item_route', $cachedRouteNameResolver->getRouteName('AppBundle\Entity\User', OperationType::ITEM), 'Trigger the local cache');
    }
}
