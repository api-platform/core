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

namespace Symfony\Component\Routing\Loader\Configurator;

use ApiPlatform\Documentation\ApiCatalogFactory;

return static function (RoutingConfigurator $routes) {
    $routes->add(ApiCatalogFactory::ROUTE_NAME, '/.well-known/api-catalog')
        ->controller('api_platform.action.api_catalog')
        ->methods(['GET', 'HEAD']);
};
