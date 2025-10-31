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

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('api_platform.maker.command.state_processor', 'ApiPlatform\Symfony\Maker\MakeStateProcessor')
        ->args([param('api_platform.maker.namespace_prefix')])
        ->tag('maker.command');

    $services->set('api_platform.maker.command.state_provider', 'ApiPlatform\Symfony\Maker\MakeStateProvider')
        ->args([param('api_platform.maker.namespace_prefix')])
        ->tag('maker.command');

    $services->set('api_platform.maker.command.filter', 'ApiPlatform\Symfony\Maker\MakeFilter')
        ->args([param('api_platform.maker.namespace_prefix')])
        ->tag('maker.command');
};
