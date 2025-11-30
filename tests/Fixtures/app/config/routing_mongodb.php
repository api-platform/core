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

return function (RoutingConfigurator $routes) {
    $routes->import('routing_common.yml');
    $routes->import('@TestBundle/Controller/MongoDbOdm', 'attribute');

    if (class_exists(WebProfilerBundle::class)) {
        // 2. Resolve the actual directory of the bundle
        $reflection = new ReflectionClass(WebProfilerBundle::class);
        $bundleDir = dirname($reflection->getFileName());

        // 3. Check if the PHP config exists on the filesystem
        $usePhp = file_exists($bundleDir.'/Resources/config/routing/wdt.php');
        $ext = $usePhp ? 'php' : 'xml';

        // 4. Import dynamically based on the extension found
        $routes->import("@WebProfilerBundle/Resources/config/routing/wdt.$ext")
            ->prefix('/_wdt');

        $routes->import("@WebProfilerBundle/Resources/config/routing/profiler.$ext")
            ->prefix('/_profiler');
    }
};
