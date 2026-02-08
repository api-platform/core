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

use ApiPlatform\State\Processor\ObjectMapperInputProcessor;
use ApiPlatform\State\Processor\ObjectMapperOutputProcessor;
use ApiPlatform\Symfony\EventListener\ObjectMapperInputListener;
use ApiPlatform\Symfony\EventListener\ObjectMapperOutputListener;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('api_platform.state_processor.object_mapper_input', ObjectMapperInputProcessor::class)
        ->args([
            service('api_platform.object_mapper')->nullOnInvalid(),
        ]);

    $services->set('api_platform.state_processor.object_mapper_output', ObjectMapperOutputProcessor::class)
        ->args([
            service('api_platform.object_mapper')->nullOnInvalid(),
        ]);

    $services->set('api_platform.listener.view.object_mapper_input', ObjectMapperInputListener::class)
        ->args([
            service('api_platform.state_processor.object_mapper_input'),
            service('api_platform.metadata.resource.metadata_collection_factory'),
        ])
        ->tag('kernel.event_listener', ['event' => 'kernel.view', 'method' => 'onKernelView', 'priority' => 48]);

    $services->set('api_platform.listener.view.object_mapper_output', ObjectMapperOutputListener::class)
        ->args([
            service('api_platform.state_processor.object_mapper_output'),
            service('api_platform.metadata.resource.metadata_collection_factory'),
        ])
        ->tag('kernel.event_listener', ['event' => 'kernel.view', 'method' => 'onKernelView', 'priority' => 24]);
};
