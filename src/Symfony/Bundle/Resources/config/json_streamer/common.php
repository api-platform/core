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

use Symfony\Component\JsonStreamer\StreamerDumper;

return function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('api_platform.jsonld.json_streamer.stream_writer', 'Symfony\Component\JsonStreamer\JsonStreamWriter')
        ->args([
            tagged_locator('json_streamer.value_transformer'),
            service('api_platform.jsonld.json_streamer.write.property_metadata_loader'),
            '%.json_streamer.stream_writers_dir.jsonld%',
            service('config_cache_factory')->ignoreOnInvalid(),
        ]);

    $services->set('api_platform.jsonld.json_streamer.stream_reader', 'Symfony\Component\JsonStreamer\JsonStreamReader')
        ->args([
            tagged_locator('json_streamer.value_transformer'),
            service('json_streamer.read.property_metadata_loader'),
            '%.json_streamer.stream_readers_dir.jsonld%',
            class_exists(StreamerDumper::class) ? service('config_cache_factory')->ignoreOnInvalid() : param('.json_streamer.lazy_ghosts_dir'),
            param('.json_streamer.lazy_ghosts_dir'),
        ]);

    $services->set('api_platform.jsonld.json_streamer.write.property_metadata_loader', 'ApiPlatform\JsonLd\JsonStreamer\WritePropertyMetadataLoader')
        ->args([
            service('json_streamer.write.property_metadata_loader'),
            service('api_platform.resource_class_resolver'),
            service('api_platform.metadata.property.name_collection_factory'),
            service('api_platform.metadata.property.metadata_factory'),
        ]);

    $services->set('api_platform.jsonld.json_streamer.write.value_transformer.iri', 'ApiPlatform\JsonLd\JsonStreamer\ValueTransformer\IriValueTransformer')
        ->args([service('api_platform.iri_converter')])
        ->tag('json_streamer.value_transformer');

    $services->set('api_platform.jsonld.json_streamer.write.value_transformer.type', 'ApiPlatform\JsonLd\JsonStreamer\ValueTransformer\TypeValueTransformer')
        ->args([
            service('api_platform.resource_class_resolver'),
            service('api_platform.metadata.resource.metadata_collection_factory'),
        ])
        ->tag('json_streamer.value_transformer');

    $services->set('api_platform.jsonld.json_streamer.write.value_transformer.context', 'ApiPlatform\JsonLd\JsonStreamer\ValueTransformer\ContextValueTransformer')
        ->args([service('api_platform.router')])
        ->tag('json_streamer.value_transformer');
};
