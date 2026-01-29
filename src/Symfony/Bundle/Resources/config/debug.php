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

use ApiPlatform\Symfony\Bundle\Command\DebugResourceCommand;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('debug.var_dumper.cloner', VarCloner::class);

    $services->set('debug.var_dumper.cli_dumper', CliDumper::class);

    $services->set('debug.api_platform.debug_resource.command', DebugResourceCommand::class)
        ->args([
            service('api_platform.metadata.resource.metadata_collection_factory'),
            service('debug.var_dumper.cloner'),
            service('debug.var_dumper.cli_dumper'),
        ])
        ->tag('console.command');
};
