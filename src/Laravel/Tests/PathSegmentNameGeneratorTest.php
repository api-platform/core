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

namespace ApiPlatform\Laravel\Tests;

use ApiPlatform\Metadata\Operation\PathSegmentNameGeneratorInterface;
use ApiPlatform\Metadata\Operation\UnderscorePathSegmentNameGenerator;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Routing\Router;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;

final class PathSegmentNameGeneratorTest extends TestCase
{
    use WithWorkbench;

    public function testDefaultBindingResolvesToUnderscoreGenerator(): void
    {
        $generator = $this->app->make(PathSegmentNameGeneratorInterface::class);

        $this->assertInstanceOf(UnderscorePathSegmentNameGenerator::class, $generator);
    }

    public function testDefaultRouteUsesUnderscoreForMultiWordResource(): void
    {
        $segments = self::collectApiRouteSegments($this->app);

        $this->assertContains('api/product_orders', $segments);
    }

    /**
     * @return list<string>
     */
    public static function collectApiRouteSegments(Application $app): array
    {
        /** @var Router $router */
        $router = $app->make(Router::class);

        $segments = [];
        foreach ($router->getRoutes()->getRoutes() as $route) {
            $uri = $route->uri();
            $uri = preg_replace('/\{[^}]+\}/', '', $uri);
            $segments[] = rtrim($uri, '/');
        }

        return $segments;
    }
}
