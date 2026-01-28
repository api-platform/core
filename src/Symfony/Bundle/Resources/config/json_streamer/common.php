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

use ApiPlatform\JsonLd\JsonStreamer\ValueTransformer\ContextValueTransformer;
use ApiPlatform\JsonLd\JsonStreamer\ValueTransformer\IriValueTransformer;
use ApiPlatform\JsonLd\JsonStreamer\ValueTransformer\TypeValueTransformer;
use ApiPlatform\JsonLd\JsonStreamer\WritePropertyMetadataLoader;
use Symfony\Component\JsonStreamer\CacheWarmer\LazyGhostCacheWarmer;
use Symfony\Component\JsonStreamer\JsonStreamReader;
use Symfony\Component\JsonStreamer\JsonStreamWriter;
use Symfony\Component\JsonStreamer\StreamerDumper;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('api_platform.jsonld.json_streamer.stream_writer', JsonStreamWriter::class)
        ->args([
            tagged_locator('json_streamer.value_transformer'),
            service('api_platform.jsonld.json_streamer.write.property_metadata_loader'),
            '%.json_streamer.stream_writers_dir.jsonld%',
            service('config_cache_factory')->ignoreOnInvalid(),
        ]);

    $jsonStreamReaderArgs = [
        tagged_locator('json_streamer.value_transformer'),
        service('json_streamer.read.property_metadata_loader'),
        '%.json_streamer.stream_readers_dir.jsonld%',
    ];
    $isJsonStreamer74OrHigher = class_exists(StreamerDumper::class);
    $isJsonStreamer80OrHigher = !class_exists(LazyGhostCacheWarmer::class);

    if ($isJsonStreamer80OrHigher) {
        $jsonStreamReaderArgs[] = service('config_cache_factory')->ignoreOnInvalid();
    } elseif ($isJsonStreamer74OrHigher) {
        $jsonStreamReaderArgs[] = service('config_cache_factory')->ignoreOnInvalid();
        $jsonStreamReaderArgs[] = param('.json_streamer.lazy_ghosts_dir');
    } else {
        $jsonStreamReaderArgs[] = param('.json_streamer.lazy_ghosts_dir');
    }

    $services->set('api_platform.jsonld.json_streamer.stream_reader', JsonStreamReader::class)
        ->args($jsonStreamReaderArgs);

    $services->set('api_platform.jsonld.json_streamer.write.property_metadata_loader', WritePropertyMetadataLoader::class)
        ->args([
            service('json_streamer.write.property_metadata_loader'),
            service('api_platform.resource_class_resolver'),
            service('api_platform.metadata.property.name_collection_factory'),
            service('api_platform.metadata.property.metadata_factory'),
        ]);

    $services->set('api_platform.jsonld.json_streamer.write.value_transformer.iri', IriValueTransformer::class)
        ->args([service('api_platform.iri_converter')])
        ->tag('json_streamer.value_transformer');

    $services->set('api_platform.jsonld.json_streamer.write.value_transformer.type', TypeValueTransformer::class)
        ->args([
            service('api_platform.resource_class_resolver'),
            service('api_platform.metadata.resource.metadata_collection_factory'),
        ])
        ->tag('json_streamer.value_transformer');

    $services->set('api_platform.jsonld.json_streamer.write.value_transformer.context', ContextValueTransformer::class)
        ->args([service('api_platform.router')])
        ->tag('json_streamer.value_transformer');
};
