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

namespace ApiPlatform\Tests\Symfony\Routing;

use ApiPlatform\Metadata\UrlGeneratorInterface;
use ApiPlatform\Symfony\Routing\Router;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class RouterTest extends TestCase
{
    use ProphecyTrait;

    public function testContextAccessor(): void
    {
        $context = new RequestContext();

        $mockedRouter = $this->prophesize(RouterInterface::class);
        $mockedRouter->setContext($context)->shouldBeCalled();
        $mockedRouter->getContext()->willReturn($context)->shouldBeCalled();

        $router = new Router($mockedRouter->reveal());
        $router->setContext($context);
        $this->assertEquals($context, $router->getContext());
    }

    public function testGetRouteCollection(): void
    {
        $routeCollection = new RouteCollection();

        $mockedRouter = $this->prophesize(RouterInterface::class);
        $mockedRouter->getRouteCollection()->willReturn($routeCollection)->shouldBeCalled();

        $router = new Router($mockedRouter->reveal());
        $this->assertEquals($routeCollection, $router->getRouteCollection());
    }

    public function testGenerate(): void
    {
        $mockedRouter = $this->prophesize(RouterInterface::class);
        $mockedRouter->generate('foo', [], RouterInterface::ABSOLUTE_PATH)->willReturn('/bar')->shouldBeCalled();

        $router = new Router($mockedRouter->reveal());
        $this->assertSame('/bar', $router->generate('foo'));
    }

    public function testGenerateWithDefaultStrategy(): void
    {
        $mockedRouter = $this->prophesize(RouterInterface::class);
        $mockedRouter->generate('foo', [], UrlGeneratorInterface::ABS_URL)->willReturn('/bar')->shouldBeCalled();

        $router = new Router($mockedRouter->reveal(), UrlGeneratorInterface::ABS_URL);
        $this->assertSame('/bar', $router->generate('foo'));
    }

    public function testGenerateWithStrategy(): void
    {
        $mockedRouter = $this->prophesize(RouterInterface::class);
        $mockedRouter->generate('foo', [], UrlGeneratorInterface::ABS_URL)->willReturn('/bar')->shouldBeCalled();

        $router = new Router($mockedRouter->reveal());
        $this->assertSame('/bar', $router->generate('foo', [], UrlGeneratorInterface::ABS_URL));
    }

    public function testMatch(): void
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

    public function testMatchDuplicatedBaseUrl(): void
    {
        $context = new RequestContext('/app', 'GET', 'localhost', 'https');

        $mockedRouter = $this->prophesize(RouterInterface::class);
        $mockedRouter->getContext()->willReturn($context);
        $mockedRouter->setContext(Argument::type(RequestContext::class))->shouldBeCalled();
        $mockedRouter->match('/api/app_crm/resource')->willReturn(['bar']);

        $router = new Router($mockedRouter->reveal());

        $this->assertEquals(['bar'], $router->match('/app/api/app_crm/resource'));
    }

    public function testMatchEmptyBaseUrl(): void
    {
        $context = new RequestContext('', 'GET', 'localhost', 'https');

        $mockedRouter = $this->prophesize(RouterInterface::class);
        $mockedRouter->getContext()->willReturn($context);
        $mockedRouter->setContext(Argument::type(RequestContext::class))->shouldBeCalled();
        $mockedRouter->match('/foo')->willReturn(['bar']);

        $router = new Router($mockedRouter->reveal());

        $this->assertEquals(['bar'], $router->match('/foo'));
    }
}
