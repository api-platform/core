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

use ApiPlatform\Core\Bridge\Symfony\Routing\CachedRouteNameResolver;
use ApiPlatform\Core\Bridge\Symfony\Routing\RouteNameResolverInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use Prophecy\Argument;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * @author Teoh Han Hui <teohhanhui@gmail.com>
 */
class CachedRouteNameResolverTest extends \PHPUnit_Framework_TestCase
{
    public function testConstruct()
    {
        $cacheItemPoolProphecy = $this->prophesize(CacheItemPoolInterface::class);

        $decoratedProphecy = $this->prophesize(RouteNameResolverInterface::class);

        $cachedRouteNameResolver = new CachedRouteNameResolver($cacheItemPoolProphecy->reveal(), $decoratedProphecy->reveal());

        $this->assertInstanceOf(RouteNameResolverInterface::class, $cachedRouteNameResolver);
    }

    /**
     * @expectedException \ApiPlatform\Core\Exception\InvalidArgumentException
     * @expectedExceptionMessage No item route associated with the type "AppBundle\Entity\User".
     */
    public function testGetRouteNameForItemRouteWithNoMatchingRoute()
    {
        $cacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $cacheItemProphecy->isHit()->willReturn(false)->shouldBeCalled();

        $cacheItemPoolProphecy = $this->prophesize(CacheItemPoolInterface::class);
        $cacheItemPoolProphecy->getItem(Argument::type('string'))->willReturn($cacheItemProphecy);
        $cacheItemPoolProphecy->save($cacheItemProphecy)->shouldNotBeCalled();

        $decoratedProphecy = $this->prophesize(RouteNameResolverInterface::class);
        $decoratedProphecy->getRouteName('AppBundle\Entity\User', false)
            ->willThrow(new InvalidArgumentException('No item route associated with the type "AppBundle\Entity\User".'))
            ->shouldBeCalled();

        $cachedRouteNameResolver = new CachedRouteNameResolver($cacheItemPoolProphecy->reveal(), $decoratedProphecy->reveal());
        $cachedRouteNameResolver->getRouteName('AppBundle\Entity\User', false);
    }

    public function testGetRouteNameForItemRouteOnCacheMiss()
    {
        $cacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $cacheItemProphecy->isHit()->willReturn(false)->shouldBeCalled();
        $cacheItemProphecy->set('some_item_route')->shouldBeCalled();

        $cacheItemPoolProphecy = $this->prophesize(CacheItemPoolInterface::class);
        $cacheItemPoolProphecy->getItem(Argument::type('string'))->willReturn($cacheItemProphecy);
        $cacheItemPoolProphecy->save($cacheItemProphecy)->willReturn(true)->shouldBeCalled();

        $decoratedProphecy = $this->prophesize(RouteNameResolverInterface::class);
        $decoratedProphecy->getRouteName('AppBundle\Entity\User', false)->willReturn('some_item_route')->shouldBeCalled();

        $cachedRouteNameResolver = new CachedRouteNameResolver($cacheItemPoolProphecy->reveal(), $decoratedProphecy->reveal());
        $actual = $cachedRouteNameResolver->getRouteName('AppBundle\Entity\User', false);

        $this->assertSame('some_item_route', $actual);
    }

    public function testGetRouteNameForItemRouteOnCacheHit()
    {
        $cacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $cacheItemProphecy->isHit()->willReturn(true)->shouldBeCalled();
        $cacheItemProphecy->get()->willReturn('some_item_route')->shouldBeCalled();

        $cacheItemPoolProphecy = $this->prophesize(CacheItemPoolInterface::class);
        $cacheItemPoolProphecy->getItem(Argument::type('string'))->willReturn($cacheItemProphecy);
        $cacheItemPoolProphecy->save($cacheItemProphecy)->shouldNotBeCalled();

        $decoratedProphecy = $this->prophesize(RouteNameResolverInterface::class);
        $decoratedProphecy->getRouteName(Argument::cetera())->shouldNotBeCalled();

        $cachedRouteNameResolver = new CachedRouteNameResolver($cacheItemPoolProphecy->reveal(), $decoratedProphecy->reveal());
        $actual = $cachedRouteNameResolver->getRouteName('AppBundle\Entity\User', false);

        $this->assertSame('some_item_route', $actual);
    }

    /**
     * @expectedException \ApiPlatform\Core\Exception\InvalidArgumentException
     * @expectedExceptionMessage No collection route associated with the type "AppBundle\Entity\User".
     */
    public function testGetRouteNameForCollectionRouteWithNoMatchingRoute()
    {
        $cacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $cacheItemProphecy->isHit()->willReturn(false)->shouldBeCalled();

        $cacheItemPoolProphecy = $this->prophesize(CacheItemPoolInterface::class);
        $cacheItemPoolProphecy->getItem(Argument::type('string'))->willReturn($cacheItemProphecy);
        $cacheItemPoolProphecy->save($cacheItemProphecy)->shouldNotBeCalled();

        $decoratedProphecy = $this->prophesize(RouteNameResolverInterface::class);
        $decoratedProphecy->getRouteName('AppBundle\Entity\User', true)
            ->willThrow(new InvalidArgumentException('No collection route associated with the type "AppBundle\Entity\User".'))
            ->shouldBeCalled();

        $cachedRouteNameResolver = new CachedRouteNameResolver($cacheItemPoolProphecy->reveal(), $decoratedProphecy->reveal());
        $cachedRouteNameResolver->getRouteName('AppBundle\Entity\User', true);
    }

    public function testGetRouteNameForCollectionRouteOnCacheMiss()
    {
        $cacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $cacheItemProphecy->isHit()->willReturn(false)->shouldBeCalled();
        $cacheItemProphecy->set('some_collection_route')->shouldBeCalled();

        $cacheItemPoolProphecy = $this->prophesize(CacheItemPoolInterface::class);
        $cacheItemPoolProphecy->getItem(Argument::type('string'))->willReturn($cacheItemProphecy);
        $cacheItemPoolProphecy->save($cacheItemProphecy)->willReturn(true)->shouldBeCalled();

        $decoratedProphecy = $this->prophesize(RouteNameResolverInterface::class);
        $decoratedProphecy->getRouteName('AppBundle\Entity\User', true)->willReturn('some_collection_route')->shouldBeCalled();

        $cachedRouteNameResolver = new CachedRouteNameResolver($cacheItemPoolProphecy->reveal(), $decoratedProphecy->reveal());
        $actual = $cachedRouteNameResolver->getRouteName('AppBundle\Entity\User', true);

        $this->assertSame('some_collection_route', $actual);
    }

    public function testGetRouteNameForCollectionRouteOnCacheHit()
    {
        $cacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $cacheItemProphecy->isHit()->willReturn(true)->shouldBeCalled();
        $cacheItemProphecy->get()->willReturn('some_collection_route')->shouldBeCalled();

        $cacheItemPoolProphecy = $this->prophesize(CacheItemPoolInterface::class);
        $cacheItemPoolProphecy->getItem(Argument::type('string'))->willReturn($cacheItemProphecy);
        $cacheItemPoolProphecy->save($cacheItemProphecy)->shouldNotBeCalled();

        $decoratedProphecy = $this->prophesize(RouteNameResolverInterface::class);
        $decoratedProphecy->getRouteName(Argument::cetera())->shouldNotBeCalled();

        $cachedRouteNameResolver = new CachedRouteNameResolver($cacheItemPoolProphecy->reveal(), $decoratedProphecy->reveal());
        $actual = $cachedRouteNameResolver->getRouteName('AppBundle\Entity\User', true);

        $this->assertSame('some_collection_route', $actual);
    }
}
