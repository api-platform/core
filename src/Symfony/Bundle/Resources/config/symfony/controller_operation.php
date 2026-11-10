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

use ApiPlatform\Symfony\EventListener\ControllerApiOperationListener;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('api_platform.listener.request.controller_api_operation', ControllerApiOperationListener::class)
        ->args([
            service('api_platform.metadata.resource.metadata_collection_factory'),
            '%api_platform.controller_operations%',
        ])
        ->tag('kernel.event_listener', ['event' => 'kernel.request', 'method' => 'onKernelRequest', 'priority' => 30]);
};
