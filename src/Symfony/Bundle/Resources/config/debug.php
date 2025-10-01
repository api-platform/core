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

    $services->set('debug.var_dumper.cloner', 'Symfony\Component\VarDumper\Cloner\VarCloner');

    $services->set('debug.var_dumper.cli_dumper', 'Symfony\Component\VarDumper\Dumper\CliDumper');

    $services->set('debug.api_platform.debug_resource.command', 'ApiPlatform\Symfony\Bundle\Command\DebugResourceCommand')
        ->args([
            service('api_platform.metadata.resource.metadata_collection_factory'),
            service('debug.var_dumper.cloner'),
            service('debug.var_dumper.cli_dumper'),
        ])
        ->tag('console.command');
};
