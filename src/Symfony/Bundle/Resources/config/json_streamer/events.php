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

    $services->set('api_platform.jsonld.state_processor.json_streamer', 'ApiPlatform\Hydra\State\JsonStreamerProcessor')
        ->args([
            null,
            service('api_platform.jsonld.json_streamer.stream_writer'),
            service('api_platform.iri_converter'),
            service('api_platform.resource_class_resolver'),
            service('api_platform.metadata.operation.metadata_factory'),
            '%api_platform.collection.pagination.page_parameter_name%',
            '%api_platform.collection.pagination.enabled_parameter_name%',
            '%api_platform.url_generation_strategy%',
        ]);

    $services->set('api_platform.jsonld.state_provider.json_streamer', 'ApiPlatform\Hydra\State\JsonStreamerProvider')
        ->args([
            null,
            service('api_platform.jsonld.json_streamer.stream_reader'),
        ]);

    $services->set('api_platform.state_processor.json_streamer', 'ApiPlatform\Serializer\State\JsonStreamerProcessor')
        ->args([
            null,
            service('json_streamer.stream_writer'),
            service('api_platform.iri_converter'),
            service('api_platform.resource_class_resolver'),
            service('api_platform.metadata.operation.metadata_factory'),
        ]);

    $services->set('api_platform.state_provider.json_streamer', 'ApiPlatform\Serializer\State\JsonStreamerProvider')
        ->args([
            null,
            service('json_streamer.stream_reader'),
        ]);

    $services->set('api_platform.listener.request.json_streamer_deserialize', 'ApiPlatform\Symfony\EventListener\JsonStreamerDeserializeListener')
        ->args([
            service('api_platform.state_provider.json_streamer'),
            'json',
            service('api_platform.metadata.resource.metadata_collection_factory'),
        ])
        ->tag('kernel.event_listener', ['event' => 'kernel.request', 'method' => 'onKernelRequest', 'priority' => 3]);

    $services->set('api_platform.listener.request.jsonld_streamer_deserialize', 'ApiPlatform\Symfony\EventListener\JsonStreamerDeserializeListener')
        ->args([
            service('api_platform.jsonld.state_provider.json_streamer'),
            'jsonld',
            service('api_platform.metadata.resource.metadata_collection_factory'),
        ])
        ->tag('kernel.event_listener', ['event' => 'kernel.request', 'method' => 'onKernelRequest', 'priority' => 3]);

    $services->set('api_platform.listener.view.json_streamer_serialize', 'ApiPlatform\Symfony\EventListener\JsonStreamerSerializeListener')
        ->args([
            service('api_platform.state_processor.json_streamer'),
            'json',
            service('api_platform.metadata.resource.metadata_collection_factory'),
        ])
        ->tag('kernel.event_listener', ['event' => 'kernel.view', 'method' => 'onKernelView', 'priority' => 17]);

    $services->set('api_platform.listener.view.jsonld_streamer_serialize', 'ApiPlatform\Symfony\EventListener\JsonStreamerSerializeListener')
        ->args([
            service('api_platform.jsonld.state_processor.json_streamer'),
            'jsonld',
            service('api_platform.metadata.resource.metadata_collection_factory'),
        ])
        ->tag('kernel.event_listener', ['event' => 'kernel.view', 'method' => 'onKernelView', 'priority' => 17]);
};
