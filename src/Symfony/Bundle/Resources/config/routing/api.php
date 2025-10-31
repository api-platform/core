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
    $routes->add('api_entrypoint', '/{index}.{_format}')
        ->controller('api_platform.action.entrypoint')
        ->methods(['GET', 'HEAD'])
        ->defaults([
            '_format' => null,
            '_api_respond' => true,
            'index' => 'index',
        ])
        ->requirements([
            'index' => 'index',
        ]);
};
