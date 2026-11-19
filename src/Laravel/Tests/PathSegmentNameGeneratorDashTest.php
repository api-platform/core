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

use ApiPlatform\Metadata\Operation\DashPathSegmentNameGenerator;
use ApiPlatform\Metadata\Operation\PathSegmentNameGeneratorInterface;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;

final class PathSegmentNameGeneratorDashTest extends TestCase
{
    use WithWorkbench;

    /**
     * @param Application $app
     */
    protected function defineEnvironment($app): void
    {
        tap($app->make('config'), static function (Repository $config): void {
            $config->set('api-platform.path_segment_name_generator', DashPathSegmentNameGenerator::class);
            $config->set('app.debug', true);
        });
    }

    public function testConfigOverrideBindingResolvesToDashGenerator(): void
    {
        $generator = $this->app->make(PathSegmentNameGeneratorInterface::class);

        $this->assertInstanceOf(DashPathSegmentNameGenerator::class, $generator);
    }

    public function testRouteUsesDashForMultiWordResource(): void
    {
        $segments = PathSegmentNameGeneratorTest::collectApiRouteSegments($this->app);

        $this->assertContains('api/product-orders', $segments);
        $this->assertNotContains('api/product_orders', $segments);
    }
}
