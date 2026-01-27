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

use ApiPlatform\Symfony\Maker\MakeFilter;
use ApiPlatform\Symfony\Maker\MakeStateProcessor;
use ApiPlatform\Symfony\Maker\MakeStateProvider;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('api_platform.maker.command.state_processor', MakeStateProcessor::class)
        ->args([param('api_platform.maker.namespace_prefix')])
        ->tag('maker.command');

    $services->set('api_platform.maker.command.state_provider', MakeStateProvider::class)
        ->args([param('api_platform.maker.namespace_prefix')])
        ->tag('maker.command');

    $services->set('api_platform.maker.command.filter', MakeFilter::class)
        ->args([param('api_platform.maker.namespace_prefix')])
        ->tag('maker.command');
};
