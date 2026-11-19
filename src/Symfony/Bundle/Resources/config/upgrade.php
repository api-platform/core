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

use ApiPlatform\Symfony\Bundle\Command\Upgrade\UpgradeApiFilterMapper;
use ApiPlatform\Symfony\Bundle\Command\Upgrade\UpgradeApiFilterResolver;
use ApiPlatform\Symfony\Bundle\Command\UpgradeApiFilterCommand;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('api_platform.upgrade.filter_mapper', UpgradeApiFilterMapper::class);

    $services->set('api_platform.upgrade.filter_resolver', UpgradeApiFilterResolver::class)
        ->args([
            service('api_platform.upgrade.filter_mapper'),
            service('api_platform.metadata.property.metadata_factory'),
            service('api_platform.resource_class_resolver'),
        ]);

    $services->set('api_platform.upgrade.filter_command', UpgradeApiFilterCommand::class)
        ->args([
            service('api_platform.metadata.resource.name_collection_factory'),
            service('api_platform.metadata.resource.metadata_collection_factory'),
            service('api_platform.filter_locator'),
            service('api_platform.upgrade.filter_resolver'),
        ])
        ->tag('console.command');
};
