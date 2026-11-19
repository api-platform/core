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

namespace ApiPlatform\Laravel\Tests\Unit\Routing;

use ApiPlatform\Laravel\Routing\Router;
use Illuminate\Routing\RouteCollection as IlluminateRouteCollection;
use Illuminate\Routing\Router as BaseRouter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\RouteCollection;

class RouterTest extends TestCase
{
    public function testGetRouteCollectionIsMemoizedAcrossCalls(): void
    {
        $symfonyRoutes = new RouteCollection();

        $illuminateRoutes = $this->createMock(IlluminateRouteCollection::class);
        $illuminateRoutes->expects($this->once())
            ->method('toSymfonyRouteCollection')
            ->willReturn($symfonyRoutes);

        $baseRouter = $this->createStub(BaseRouter::class);
        $baseRouter->method('getRoutes')->willReturn($illuminateRoutes);

        $router = new Router($baseRouter);

        $first = $router->getRouteCollection();
        $second = $router->getRouteCollection();

        $this->assertSame($symfonyRoutes, $first);
        $this->assertSame($first, $second);
    }

    public function testMatchThrowsResourceNotFoundException(): void
    {
        $illuminateRoutes = $this->createStub(IlluminateRouteCollection::class);
        $illuminateRoutes->method('match')->willThrowException(new NotFoundHttpException());

        $baseRouter = $this->createStub(BaseRouter::class);
        $baseRouter->method('getRoutes')->willReturn($illuminateRoutes);

        $this->expectException(ResourceNotFoundException::class);

        (new Router($baseRouter))->match('/api/nope/1');
    }

    public function testMatchThrowsMethodNotAllowedException(): void
    {
        $illuminateRoutes = $this->createStub(IlluminateRouteCollection::class);
        $illuminateRoutes->method('match')->willThrowException(new MethodNotAllowedHttpException(['POST', 'PUT']));

        $baseRouter = $this->createStub(BaseRouter::class);
        $baseRouter->method('getRoutes')->willReturn($illuminateRoutes);

        try {
            (new Router($baseRouter))->match('/api/books');
            $this->fail('A MethodNotAllowedException should have been thrown.');
        } catch (MethodNotAllowedException $e) {
            $this->assertSame(['POST', 'PUT'], $e->getAllowedMethods());
            $this->assertInstanceOf(MethodNotAllowedHttpException::class, $e->getPrevious());
        }
    }
}
