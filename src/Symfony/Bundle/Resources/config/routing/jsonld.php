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
    $routes->add('api_jsonld_context', '/contexts/{shortName}.{_format}')
        ->controller('api_platform.jsonld.action.context')
        ->methods(['GET', 'HEAD'])
        ->defaults([
            '_format' => 'jsonld',
            '_api_respond' => true,
        ])
        ->requirements([
            'shortName' => '[^.]+',
            '_format' => 'jsonld',
        ]);
};
