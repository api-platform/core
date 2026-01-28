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

use ApiPlatform\Hydra\State\JsonStreamerProcessor;
use ApiPlatform\Hydra\State\JsonStreamerProvider;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('api_platform.jsonld.state_processor.json_streamer', JsonStreamerProcessor::class)
        ->decorate('api_platform.state_processor.main', null, 190)
        ->args([
            service('api_platform.jsonld.state_processor.json_streamer.inner'),
            service('api_platform.jsonld.json_streamer.stream_writer'),
            service('api_platform.iri_converter'),
            service('api_platform.resource_class_resolver'),
            service('api_platform.metadata.operation.metadata_factory'),
            '%api_platform.collection.pagination.page_parameter_name%',
            '%api_platform.collection.pagination.enabled_parameter_name%',
            '%api_platform.url_generation_strategy%',
        ]);

    $services->set('api_platform.jsonld.state_provider.json_streamer', JsonStreamerProvider::class)
        ->decorate('api_platform.state_provider.main', null, 310)
        ->args([
            service('api_platform.jsonld.state_provider.json_streamer.inner'),
            service('api_platform.jsonld.json_streamer.stream_reader'),
        ]);
};
