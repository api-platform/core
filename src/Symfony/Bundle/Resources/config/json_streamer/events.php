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

use ApiPlatform\Hydra\State\JsonStreamerProcessor as HydraJsonStreamerProcessor;
use ApiPlatform\Hydra\State\JsonStreamerProvider as HydraJsonStreamerProvider;
use ApiPlatform\Serializer\State\JsonStreamerProcessor;
use ApiPlatform\Serializer\State\JsonStreamerProvider;
use ApiPlatform\Symfony\EventListener\JsonStreamerDeserializeListener;
use ApiPlatform\Symfony\EventListener\JsonStreamerSerializeListener;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('api_platform.jsonld.state_processor.json_streamer', HydraJsonStreamerProcessor::class)
        ->args([
            null,
            service('api_platform.jsonld.json_streamer.stream_writer'),
            service('api_platform.iri_converter'),
            service('api_platform.resource_class_resolver'),
            service('api_platform.metadata.operation.metadata_factory'),
            '%api_platform.collection.pagination.page_parameter_name%',
            '%api_platform.collection.pagination.enabled_parameter_name%',
            '%api_platform.url_generation_strategy%',
            service('api_platform.metadata.resource.metadata_collection_factory'),
        ]);

    $services->set('api_platform.jsonld.state_provider.json_streamer', HydraJsonStreamerProvider::class)
        ->args([
            null,
            service('api_platform.jsonld.json_streamer.stream_reader'),
        ]);

    $services->set('api_platform.state_processor.json_streamer', JsonStreamerProcessor::class)
        ->args([
            null,
            service('json_streamer.stream_writer'),
            service('api_platform.iri_converter'),
            service('api_platform.resource_class_resolver'),
            service('api_platform.metadata.operation.metadata_factory'),
            service('api_platform.metadata.resource.metadata_collection_factory'),
        ]);

    $services->set('api_platform.state_provider.json_streamer', JsonStreamerProvider::class)
        ->args([
            null,
            service('json_streamer.stream_reader'),
        ]);

    $services->set('api_platform.listener.request.json_streamer_deserialize', JsonStreamerDeserializeListener::class)
        ->args([
            service('api_platform.state_provider.json_streamer'),
            'json',
            service('api_platform.metadata.resource.metadata_collection_factory'),
        ])
        ->tag('kernel.event_listener', ['event' => 'kernel.request', 'method' => 'onKernelRequest', 'priority' => 3]);

    $services->set('api_platform.listener.request.jsonld_streamer_deserialize', JsonStreamerDeserializeListener::class)
        ->args([
            service('api_platform.jsonld.state_provider.json_streamer'),
            'jsonld',
            service('api_platform.metadata.resource.metadata_collection_factory'),
        ])
        ->tag('kernel.event_listener', ['event' => 'kernel.request', 'method' => 'onKernelRequest', 'priority' => 3]);

    $services->set('api_platform.listener.view.json_streamer_serialize', JsonStreamerSerializeListener::class)
        ->args([
            service('api_platform.state_processor.json_streamer'),
            'json',
            service('api_platform.metadata.resource.metadata_collection_factory'),
        ])
        ->tag('kernel.event_listener', ['event' => 'kernel.view', 'method' => 'onKernelView', 'priority' => 17]);

    $services->set('api_platform.listener.view.jsonld_streamer_serialize', JsonStreamerSerializeListener::class)
        ->args([
            service('api_platform.jsonld.state_processor.json_streamer'),
            'jsonld',
            service('api_platform.metadata.resource.metadata_collection_factory'),
        ])
        ->tag('kernel.event_listener', ['event' => 'kernel.view', 'method' => 'onKernelView', 'priority' => 17]);
};
