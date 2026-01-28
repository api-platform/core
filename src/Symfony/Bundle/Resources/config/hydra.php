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

use ApiPlatform\Hydra\JsonSchema\SchemaFactory;
use ApiPlatform\Hydra\Serializer\CollectionFiltersNormalizer;
use ApiPlatform\Hydra\Serializer\CollectionNormalizer;
use ApiPlatform\Hydra\Serializer\ConstraintViolationListNormalizer;
use ApiPlatform\Hydra\Serializer\DocumentationNormalizer;
use ApiPlatform\Hydra\Serializer\EntrypointNormalizer;
use ApiPlatform\Hydra\Serializer\HydraPrefixNameConverter;
use ApiPlatform\Hydra\Serializer\PartialCollectionViewNormalizer;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('api_platform.hydra.json_schema.schema_factory', SchemaFactory::class)
        ->decorate('api_platform.json_schema.schema_factory', null, 0)
        ->args([
            service('api_platform.hydra.json_schema.schema_factory.inner'),
            '%api_platform.serializer.default_context%',
            service('api_platform.json_schema.definition_name_factory')->ignoreOnInvalid(),
            service('api_platform.metadata.resource.metadata_collection_factory')->ignoreOnInvalid(),
        ]);

    $services->set('api_platform.hydra.normalizer.documentation', DocumentationNormalizer::class)
        ->args([
            service('api_platform.metadata.resource.metadata_collection_factory'),
            service('api_platform.metadata.property.name_collection_factory'),
            service('api_platform.metadata.property.metadata_factory'),
            service('api_platform.resource_class_resolver'),
            service('api_platform.router'),
            service('api_platform.name_converter')->ignoreOnInvalid(),
            '%api_platform.serializer.default_context%',
            '%api_platform.enable_entrypoint%',
        ])
        ->tag('serializer.normalizer', ['priority' => -800]);

    $services->set('api_platform.hydra.normalizer.constraint_violation_list', ConstraintViolationListNormalizer::class)
        ->args([
            '%api_platform.validator.serialize_payload_fields%',
            service('api_platform.name_converter')->ignoreOnInvalid(),
            '%api_platform.serializer.default_context%',
        ])
        ->tag('serializer.normalizer', ['priority' => -780]);

    $services->set('api_platform.hydra.normalizer.entrypoint', EntrypointNormalizer::class)
        ->args([
            service('api_platform.metadata.resource.metadata_collection_factory'),
            service('api_platform.iri_converter'),
            service('api_platform.router'),
        ])
        ->tag('serializer.normalizer', ['priority' => -800]);

    $services->set('api_platform.hydra.normalizer.collection', CollectionNormalizer::class)
        ->args([
            service('api_platform.jsonld.context_builder'),
            service('api_platform.resource_class_resolver'),
            service('api_platform.iri_converter'),
            '%api_platform.serializer.default_context%',
        ])
        ->tag('serializer.normalizer', ['priority' => -985]);

    $services->set('api_platform.hydra.normalizer.partial_collection_view', PartialCollectionViewNormalizer::class)
        ->decorate('api_platform.hydra.normalizer.collection', null, 0)
        ->args([
            service('api_platform.hydra.normalizer.partial_collection_view.inner'),
            '%api_platform.collection.pagination.page_parameter_name%',
            '%api_platform.collection.pagination.enabled_parameter_name%',
            service('api_platform.metadata.resource.metadata_collection_factory'),
            service('api_platform.property_accessor'),
            '%api_platform.url_generation_strategy%',
            '%api_platform.serializer.default_context%',
        ]);

    $services->set('api_platform.hydra.normalizer.collection_filters', CollectionFiltersNormalizer::class)
        ->decorate('api_platform.hydra.normalizer.collection', null, 0)
        ->args([
            service('api_platform.hydra.normalizer.collection_filters.inner'),
            service('api_platform.metadata.resource.metadata_collection_factory'),
            service('api_platform.resource_class_resolver'),
            service('api_platform.filter_locator'),
            '%api_platform.serializer.default_context%',
        ]);

    $services->set('api_platform.hydra.name_converter.hydra_prefix', HydraPrefixNameConverter::class)
        ->decorate('api_platform.name_converter', null, 0)
        ->args([
            service('api_platform.hydra.name_converter.hydra_prefix.inner'),
            '%api_platform.serializer.default_context%',
        ]);
};
