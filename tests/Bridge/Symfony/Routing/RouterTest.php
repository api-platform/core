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

use ApiPlatform\Core\Bridge\Symfony\Routing\Router;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Routing\Exception\ExceptionInterface as RoutingExceptionInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class RouterTest extends TestCase
{
    public function testContextAccessor()
    {
        $context = new RequestContext();

        $mockedRouter = $this->prophesize(RouterInterface::class);
        $mockedRouter->setContext($context)->shouldBeCalled();
        $mockedRouter->getContext()->willReturn($context)->shouldBeCalled();

        $router = new Router($mockedRouter->reveal());
        $router->setContext($context);
        $this->assertSame($context, $router->getContext());
    }

    public function testGetRouteCollection()
    {
        $routeCollection = new RouteCollection();

        $mockedRouter = $this->prophesize(RouterInterface::class);
        $mockedRouter->getRouteCollection()->willReturn($routeCollection)->shouldBeCalled();

        $router = new Router($mockedRouter->reveal());
        $this->assertSame($routeCollection, $router->getRouteCollection());
    }

    public function testGenerate()
    {
        $mockedRouter = $this->prophesize(RouterInterface::class);
        $mockedRouter->generate('foo', [], RouterInterface::ABSOLUTE_PATH)->willReturn('/bar')->shouldBeCalled();

        $router = new Router($mockedRouter->reveal());
        $this->assertSame('/bar', $router->generate('foo'));
    }

    public function testMatch()
    {
        $context = new RequestContext('/app_dev.php', 'GET', 'localhost', 'https');

        $mockedRouter = $this->prophesize(RouterInterface::class);
        $mockedRouter->getContext()->willReturn($context)->shouldBeCalled();
        $mockedRouter->setContext(Argument::type(RequestContext::class))->shouldBeCalled();
        $mockedRouter->setContext($context)->shouldBeCalled();
        $mockedRouter->match('/foo')->willReturn(['bar'])->shouldBeCalled();

        $router = new Router($mockedRouter->reveal());

        $this->assertEquals(['bar'], $router->match('/app_dev.php/foo'));
    }

    public function testWithinvalidContext()
    {
        $this->expectException(RoutingExceptionInterface::class);
        $this->expectExceptionMessage('Invalid request context.');
        $context = new RequestContext('/app_dev.php', 'GET', 'localhost', 'https');

        $mockedRouter = $this->prophesize(RouterInterface::class);
        $mockedRouter->getContext()->willReturn($context)->shouldBeCalled();

        $router = new Router($mockedRouter->reveal());
        $router->match('28-01-2018 10:10');
    }
}
