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

return function (RoutingConfigurator $routes) {
    $routes->add('api_genid', '/.well-known/genid/{id}')
        ->controller('api_platform.action.not_exposed')
        ->methods(['GET', 'HEAD'])
        ->defaults([
            '_api_respond' => true,
        ]);
};
