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
        ->args([service('api_platform.metadata.resource.name_collection_factory')])
        ->tag('maker.command');

    $services->set('api_platform.maker.command.state_provider', 'ApiPlatform\Symfony\Maker\MakeStateProvider')
        ->args([service('api_platform.metadata.resource.name_collection_factory')])
        ->tag('maker.command');

    $services->set('api_platform.maker.command.filter', 'ApiPlatform\Symfony\Maker\MakeFilter')
        ->args([service('api_platform.metadata.resource.name_collection_factory')])
        ->tag('maker.command');
};
