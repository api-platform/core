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

    $services->set('api_platform.state_processor.json_streamer', 'ApiPlatform\Serializer\State\JsonStreamerProcessor')
        ->decorate('api_platform.state_processor.main', null, 190)
        ->args([
            service('api_platform.state_processor.json_streamer.inner'),
            service('json_streamer.stream_writer'),
            service('api_platform.iri_converter'),
            service('api_platform.resource_class_resolver'),
            service('api_platform.metadata.operation.metadata_factory'),
        ]);

    $services->set('api_platform.state_provider.json_streamer', 'ApiPlatform\Serializer\State\JsonStreamerProvider')
        ->decorate('api_platform.state_provider.main', null, 310)
        ->args([
            service('api_platform.state_provider.json_streamer.inner'),
            service('json_streamer.stream_reader'),
        ]);
};
