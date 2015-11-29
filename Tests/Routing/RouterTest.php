<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Tests\Routing;

use Dunglas\ApiBundle\Routing\Router;
use Prophecy\Argument;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class RouterTest extends \PHPUnit_Framework_TestCase
{
    public function testContextAccessor()
    {
        $context = new RequestContext();

        $mockedRouter = $this->prophesize('Symfony\Component\Routing\RouterInterface');
        $mockedRouter->setContext($context)->shouldBeCalled();
        $mockedRouter->getContext()->willReturn($context)->shouldBeCalled();

        $router = new Router($mockedRouter->reveal());
        $router->setContext($context);
        $this->assertSame($context, $router->getContext());
    }

    public function testGetRouteCollection()
    {
        $routeCollection = new RouteCollection();

        $mockedRouter = $this->prophesize('Symfony\Component\Routing\RouterInterface');
        $mockedRouter->getRouteCollection()->willReturn($routeCollection)->shouldBeCalled();

        $router = new Router($mockedRouter->reveal());
        $this->assertSame($routeCollection, $router->getRouteCollection());
    }

    public function testGenerate()
    {
        $mockedRouter = $this->prophesize('Symfony\Component\Routing\RouterInterface');
        $mockedRouter->generate('foo', [], false)->willReturn('/bar')->shouldBeCalled();

        $router = new Router($mockedRouter->reveal());
        $this->assertSame('/bar', $router->generate('foo'));
    }

    public function testMatch()
    {
        $context = new RequestContext('/app_dev.php', 'GET', 'localhost', 'https');

        $mockedRouter = $this->prophesize('Symfony\Component\Routing\RouterInterface');
        $mockedRouter->getContext()->willReturn($context)->shouldBeCalled();
        $mockedRouter->setContext(Argument::type('Symfony\Component\Routing\RequestContext'))->shouldBeCalled();
        $mockedRouter->setContext($context)->shouldBeCalled();
        $mockedRouter->match('/foo')->willReturn(['bar'])->shouldBeCalled();

        $router = new Router($mockedRouter->reveal());

        $this->assertEquals(['bar'], $router->match('/app_dev.php/foo'));
    }
}
