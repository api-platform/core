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
use ApiPlatform\Core\Bridge\Symfony\Routing\RouteNameGenerator;
use ApiPlatform\Core\Bridge\Symfony\Routing\RouterOperationPathResolver;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\PathResolver\OperationPathResolverInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

/**
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
class RouterOperationPathResolverTest extends TestCase
{
    public function testResolveOperationPath()
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add('foos', new Route('/foos'));

        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->getRouteCollection()->willReturn($routeCollection)->shouldBeCalled();

        $operationPathResolver = new RouterOperationPathResolver($routerProphecy->reveal(), $this->prophesize(OperationPathResolverInterface::class)->reveal());

        $this->assertEquals('/foos', $operationPathResolver->resolveOperationPath('Foo', ['route_name' => 'foos'], OperationType::COLLECTION, 'get'));
    }

    public function testResolveOperationPathWithSubresource()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Subresource operations are not supported by the RouterOperationPathResolver without a route name.');

        $routerProphecy = $this->prophesize(RouterInterface::class);

        $operationPathResolver = new RouterOperationPathResolver($routerProphecy->reveal(), $this->prophesize(OperationPathResolverInterface::class)->reveal());

        $operationPathResolver->resolveOperationPath('Foo', ['property' => 'bar', 'collection' => true, 'resource_class' => 'Foo'], OperationType::SUBRESOURCE, 'get');
    }

    public function testResolveOperationPathWithRouteNameGeneration()
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add(RouteNameGenerator::generate('get', 'Foo', OperationType::COLLECTION), new Route('/foos'));

        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->getRouteCollection()->willReturn($routeCollection)->shouldBeCalled();

        $operationPathResolver = new RouterOperationPathResolver($routerProphecy->reveal(), $this->prophesize(OperationPathResolverInterface::class)->reveal());

        $this->assertEquals('/foos', $operationPathResolver->resolveOperationPath('Foo', [], OperationType::COLLECTION, 'get'));
    }

    public function testResolveOperationPathWithRouteNotFound()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The route "api_foos_get_collection" of the resource "Foo" was not found.');

        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->getRouteCollection()->willReturn(new RouteCollection())->shouldBeCalled();

        $operationPathResolver = new RouterOperationPathResolver($routerProphecy->reveal(), $this->prophesize(OperationPathResolverInterface::class)->reveal());
        $operationPathResolver->resolveOperationPath('Foo', [], OperationType::COLLECTION, 'get');
    }

    /**
     * @group legacy
     * @expectedDeprecation Method ApiPlatform\Core\Bridge\Symfony\Routing\RouterOperationPathResolver::resolveOperationPath() will have a 4th `string $operationName` argument in version 3.0. Not defining it is deprecated since 2.1.
     * @expectedDeprecation Using a boolean for the Operation Type is deprecated since API Platform 2.1 and will not be possible anymore in API Platform 3
     */
    public function testLegacyResolveOperationPath()
    {
        $operationPathResolverProphecy = $this->prophesize(OperationPathResolverInterface::class);
        $operationPathResolverProphecy->resolveOperationPath('Foo', [], OperationType::ITEM, null)->willReturn('/foos/{id}.{_format}')->shouldBeCalled();

        $operationPathResolver = new RouterOperationPathResolver($this->prophesize(RouterInterface::class)->reveal(), $operationPathResolverProphecy->reveal());

        $this->assertEquals('/foos/{id}.{_format}', $operationPathResolver->resolveOperationPath('Foo', [], false));
    }
}
