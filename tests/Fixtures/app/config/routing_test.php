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

use Symfony\Bundle\WebProfilerBundle\WebProfilerBundle;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes) {
    $routes->import('routing_common.yml');
    $routes->import('@TestBundle/Controller/Orm', 'attribute');

    $routes->import('.', 'mcp');

    if (class_exists(WebProfilerBundle::class)) {
        $reflection = new ReflectionClass(WebProfilerBundle::class);
        $bundleDir = dirname($reflection->getFileName());

        $usePhp = file_exists($bundleDir.'/Resources/config/routing/wdt.php');
        $ext = $usePhp ? 'php' : 'xml';

        $routes->import("@WebProfilerBundle/Resources/config/routing/wdt.$ext")
            ->prefix('/_wdt');

        $routes->import("@WebProfilerBundle/Resources/config/routing/profiler.$ext")
            ->prefix('/_profiler');
    }
};
