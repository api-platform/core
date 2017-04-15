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

use ApiPlatform\Core\Bridge\Symfony\Routing\RouteNameResolver;
use ApiPlatform\Core\Bridge\Symfony\Routing\RouteNameResolverInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

/**
 * @author Teoh Han Hui <teohhanhui@gmail.com>
 */
class RouteNameResolverTest extends \PHPUnit_Framework_TestCase
{
    public function testConstruct()
    {
        $routerProphecy = $this->prophesize(RouterInterface::class);

        $routeNameResolver = new RouteNameResolver($routerProphecy->reveal());

        $this->assertInstanceOf(RouteNameResolverInterface::class, $routeNameResolver);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage No item route associated with the type "AppBundle\Entity\User".
     */
    public function testGetRouteNameForItemRouteWithNoMatchingRoute()
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add('some_collection_route', new Route('/some/collection/path', [
            '_api_resource_class' => 'AppBundle\Entity\User',
            '_api_collection_operation_name' => 'some_collection_op',
        ]));

        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->getRouteCollection()->willReturn($routeCollection);

        $routeNameResolver = new RouteNameResolver($routerProphecy->reveal());
        $routeNameResolver->getRouteName('AppBundle\Entity\User', false);
    }

    public function testGetRouteNameForItemRoute()
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add('some_collection_route', new Route('/some/collection/path', [
            '_api_resource_class' => 'AppBundle\Entity\User',
            '_api_collection_operation_name' => 'some_collection_op',
        ]));
        $routeCollection->add('some_item_route', new Route('/some/item/path/{id}', [
            '_api_resource_class' => 'AppBundle\Entity\User',
            '_api_item_operation_name' => 'some_item_op',
        ]));

        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->getRouteCollection()->willReturn($routeCollection);

        $routeNameResolver = new RouteNameResolver($routerProphecy->reveal());
        $actual = $routeNameResolver->getRouteName('AppBundle\Entity\User', false);

        $this->assertSame('some_item_route', $actual);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage No collection route associated with the type "AppBundle\Entity\User".
     */
    public function testGetRouteNameForCollectionRouteWithNoMatchingRoute()
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add('some_item_route', new Route('/some/item/path/{id}', [
            '_api_resource_class' => 'AppBundle\Entity\User',
            '_api_item_operation_name' => 'some_item_op',
        ]));

        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->getRouteCollection()->willReturn($routeCollection);

        $routeNameResolver = new RouteNameResolver($routerProphecy->reveal());
        $routeNameResolver->getRouteName('AppBundle\Entity\User', true);
    }

    public function testGetRouteNameForCollectionRoute()
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add('some_item_route', new Route('/some/item/path/{id}', [
            '_api_resource_class' => 'AppBundle\Entity\User',
            '_api_item_operation_name' => 'some_item_op',
        ]));
        $routeCollection->add('some_collection_route', new Route('/some/collection/path', [
            '_api_resource_class' => 'AppBundle\Entity\User',
            '_api_collection_operation_name' => 'some_collection_op',
        ]));

        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->getRouteCollection()->willReturn($routeCollection);

        $routeNameResolver = new RouteNameResolver($routerProphecy->reveal());
        $actual = $routeNameResolver->getRouteName('AppBundle\Entity\User', true);

        $this->assertSame('some_collection_route', $actual);
    }
}
