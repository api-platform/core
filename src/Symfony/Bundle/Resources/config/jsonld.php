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

    $services->set('api_platform.jsonld.context_builder', 'ApiPlatform\JsonLd\ContextBuilder')
        ->args([
            service('api_platform.metadata.resource.name_collection_factory'),
            service('api_platform.metadata.resource.metadata_collection_factory'),
            service('api_platform.metadata.property.name_collection_factory'),
            service('api_platform.metadata.property.metadata_factory'),
            service('api_platform.router'),
            service('api_platform.symfony.iri_converter.skolem'),
            service('api_platform.name_converter'),
            '%api_platform.serializer.default_context%',
        ]);

    $services->set('api_platform.jsonld.normalizer.item', 'ApiPlatform\JsonLd\Serializer\ItemNormalizer')
        ->args([
            service('api_platform.metadata.resource.metadata_collection_factory'),
            service('api_platform.metadata.property.name_collection_factory'),
            service('api_platform.metadata.property.metadata_factory'),
            service('api_platform.iri_converter'),
            service('api_platform.resource_class_resolver'),
            service('api_platform.jsonld.context_builder'),
            service('api_platform.property_accessor'),
            service('api_platform.name_converter')->ignoreOnInvalid(),
            service('serializer.mapping.class_metadata_factory')->ignoreOnInvalid(),
            '%api_platform.serializer.default_context%',
            service('api_platform.security.resource_access_checker')->ignoreOnInvalid(),
            service('api_platform.http_cache.tag_collector')->ignoreOnInvalid(),
        ])
        ->tag('serializer.normalizer', ['priority' => -890]);

    $services->set('api_platform.jsonld.normalizer.error', 'ApiPlatform\JsonLd\Serializer\ErrorNormalizer')
        ->args([
            service('api_platform.jsonld.normalizer.item'),
            '%api_platform.serializer.default_context%',
        ])
        ->tag('serializer.normalizer', ['priority' => -880]);

    $services->set('api_platform.jsonld.normalizer.object', 'ApiPlatform\JsonLd\Serializer\ObjectNormalizer')
        ->args([
            service('serializer.normalizer.object'),
            service('api_platform.iri_converter'),
            service('api_platform.jsonld.context_builder'),
        ])
        ->tag('serializer.normalizer', ['priority' => -995]);

    $services->set('api_platform.jsonld.normalizer.validation_exception', 'ApiPlatform\Symfony\Validator\Serializer\ValidationExceptionNormalizer')
        ->args([
            service('api_platform.jsonld.normalizer.error'),
            service('api_platform.name_converter')->ignoreOnInvalid(),
        ])
        ->tag('serializer.normalizer', ['priority' => -800]);

    $services->set('api_platform.jsonld.encoder', 'ApiPlatform\Serializer\JsonEncoder')
        ->args([
            'jsonld',
            service('serializer.json.encoder')->nullOnInvalid(),
        ])
        ->tag('serializer.encoder');
};
