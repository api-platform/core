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

use ApiPlatform\Hal\JsonSchema\SchemaFactory;
use ApiPlatform\Hal\Serializer\CollectionNormalizer;
use ApiPlatform\Hal\Serializer\EntrypointNormalizer;
use ApiPlatform\Hal\Serializer\ItemNormalizer;
use ApiPlatform\Hal\Serializer\ObjectNormalizer;
use ApiPlatform\Serializer\JsonEncoder;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('api_platform.hal.json_schema.schema_factory', SchemaFactory::class)
        ->decorate('api_platform.json_schema.schema_factory', null, 0)
        ->args([
            service('api_platform.hal.json_schema.schema_factory.inner'),
            service('api_platform.json_schema.definition_name_factory')->ignoreOnInvalid(),
            service('api_platform.metadata.resource.metadata_collection_factory')->ignoreOnInvalid(),
        ]);

    $services->set('api_platform.hal.encoder', JsonEncoder::class)
        ->args(['jsonhal'])
        ->tag('serializer.encoder');

    $services->set('api_platform.hal.normalizer.entrypoint', EntrypointNormalizer::class)
        ->args([
            service('api_platform.metadata.resource.metadata_collection_factory'),
            service('api_platform.iri_converter'),
            service('api_platform.router'),
        ])
        ->tag('serializer.normalizer', ['priority' => -800]);

    $services->set('api_platform.hal.normalizer.collection', CollectionNormalizer::class)
        ->args([
            service('api_platform.resource_class_resolver'),
            '%api_platform.collection.pagination.page_parameter_name%',
            service('api_platform.metadata.resource.metadata_collection_factory'),
        ])
        ->tag('serializer.normalizer', ['priority' => -985]);

    $services->set('api_platform.hal.normalizer.item', ItemNormalizer::class)
        ->args([
            service('api_platform.metadata.property.name_collection_factory'),
            service('api_platform.metadata.property.metadata_factory'),
            service('api_platform.iri_converter'),
            service('api_platform.resource_class_resolver'),
            service('api_platform.property_accessor'),
            service('api_platform.name_converter')->ignoreOnInvalid(),
            service('serializer.mapping.class_metadata_factory')->ignoreOnInvalid(),
            [],
            service('api_platform.metadata.resource.metadata_collection_factory')->ignoreOnInvalid(),
            service('api_platform.security.resource_access_checker')->ignoreOnInvalid(),
            service('api_platform.http_cache.tag_collector')->ignoreOnInvalid(),
            service('api_platform.serializer.operation_resource_resolver'),
        ])
        ->tag('serializer.normalizer', ['priority' => -890]);

    $services->set('api_platform.hal.normalizer.object', ObjectNormalizer::class)
        ->args([
            service('api_platform.normalizer.object'),
            service('api_platform.iri_converter'),
        ])
        ->tag('serializer.normalizer', ['priority' => -995]);
};
