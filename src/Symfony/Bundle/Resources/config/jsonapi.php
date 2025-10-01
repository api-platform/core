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

    $services->set('api_platform.jsonapi.json_schema.schema_factory', 'ApiPlatform\JsonApi\JsonSchema\SchemaFactory')
        ->decorate('api_platform.json_schema.schema_factory', null, 0)
        ->args([
            service('api_platform.jsonapi.json_schema.schema_factory.inner'),
            service('api_platform.metadata.property.metadata_factory'),
            service('api_platform.resource_class_resolver'),
            service('api_platform.metadata.resource.metadata_collection_factory')->ignoreOnInvalid(),
            service('api_platform.json_schema.definition_name_factory')->ignoreOnInvalid(),
        ]);

    $services->set('api_platform.jsonapi.encoder', 'ApiPlatform\Serializer\JsonEncoder')
        ->args(['jsonapi'])
        ->tag('serializer.encoder');

    $services->set('api_platform.jsonapi.name_converter.reserved_attribute_name', 'ApiPlatform\JsonApi\Serializer\ReservedAttributeNameConverter')
        ->args([service('api_platform.name_converter')->ignoreOnInvalid()]);

    $services->set('api_platform.jsonapi.normalizer.entrypoint', 'ApiPlatform\JsonApi\Serializer\EntrypointNormalizer')
        ->args([
            service('api_platform.metadata.resource.metadata_collection_factory'),
            service('api_platform.iri_converter'),
            service('api_platform.router'),
        ])
        ->tag('serializer.normalizer', ['priority' => -800]);

    $services->set('api_platform.jsonapi.normalizer.collection', 'ApiPlatform\JsonApi\Serializer\CollectionNormalizer')
        ->args([
            service('api_platform.resource_class_resolver'),
            '%api_platform.collection.pagination.page_parameter_name%',
            service('api_platform.metadata.resource.metadata_collection_factory'),
        ])
        ->tag('serializer.normalizer', ['priority' => -985]);

    $services->set('api_platform.jsonapi.normalizer.item', 'ApiPlatform\JsonApi\Serializer\ItemNormalizer')
        ->args([
            service('api_platform.metadata.property.name_collection_factory'),
            service('api_platform.metadata.property.metadata_factory'),
            service('api_platform.iri_converter'),
            service('api_platform.resource_class_resolver'),
            service('api_platform.property_accessor'),
            service('api_platform.jsonapi.name_converter.reserved_attribute_name'),
            service('serializer.mapping.class_metadata_factory')->ignoreOnInvalid(),
            [],
            service('api_platform.metadata.resource.metadata_collection_factory'),
            service('api_platform.security.resource_access_checker')->ignoreOnInvalid(),
            service('api_platform.http_cache.tag_collector')->ignoreOnInvalid(),
        ])
        ->tag('serializer.normalizer', ['priority' => -890]);

    $services->set('api_platform.jsonapi.normalizer.object', 'ApiPlatform\JsonApi\Serializer\ObjectNormalizer')
        ->args([
            service('serializer.normalizer.object'),
            service('api_platform.iri_converter'),
            service('api_platform.resource_class_resolver'),
            service('api_platform.metadata.resource.metadata_collection_factory'),
        ])
        ->tag('serializer.normalizer', ['priority' => -995]);

    $services->set('api_platform.jsonapi.normalizer.constraint_violation_list', 'ApiPlatform\JsonApi\Serializer\ConstraintViolationListNormalizer')
        ->args([
            service('api_platform.metadata.property.metadata_factory'),
            service('api_platform.jsonapi.name_converter.reserved_attribute_name'),
        ])
        ->tag('serializer.normalizer', ['priority' => -780]);

    $services->set('api_platform.jsonapi.normalizer.error', 'ApiPlatform\JsonApi\Serializer\ErrorNormalizer')
        ->args([service('api_platform.jsonapi.normalizer.item')])
        ->tag('serializer.normalizer', ['priority' => -790]);
};
