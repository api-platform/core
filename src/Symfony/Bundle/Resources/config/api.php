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

    $services->set('api_platform.action.not_found', 'ApiPlatform\Symfony\Action\NotFoundAction')
        ->public();

    $services->alias('ApiPlatform\Symfony\Action\NotFoundAction', 'api_platform.action.not_found')
        ->public();

    $services->set('api_platform.action.not_exposed', 'ApiPlatform\Symfony\Action\NotExposedAction')
        ->public();

    $services->alias('ApiPlatform\Symfony\Action\NotExposedAction', 'api_platform.action.not_exposed')
        ->public();

    $services->alias('api_platform.serializer', 'serializer');

    $services->alias('api_platform.property_accessor', 'property_accessor');

    $services->alias('api_platform.property_info', 'property_info');

    $services->set('api_platform.negotiator', 'Negotiation\Negotiator');

    $services->set('api_platform.resource_class_resolver', 'ApiPlatform\Metadata\ResourceClassResolver')
        ->args([service('api_platform.metadata.resource.name_collection_factory')]);

    $services->alias('ApiPlatform\Metadata\ResourceClassResolverInterface', 'api_platform.resource_class_resolver');

    $services->alias('ApiPlatform\Metadata\UrlGeneratorInterface', 'api_platform.router');

    $services->set('api_platform.router', 'ApiPlatform\Symfony\Routing\Router')
        ->args([
            service('router'),
            '%api_platform.url_generation_strategy%',
        ]);

    $services->set('api_platform.serializer.context_builder', 'ApiPlatform\Serializer\SerializerContextBuilder')
        ->arg(0, service('api_platform.metadata.resource.metadata_collection_factory'))
        ->arg('$debug', '%kernel.debug%');

    $services->set('api_platform.serializer.filter_parameter_provider', 'ApiPlatform\Serializer\Parameter\SerializerFilterParameterProvider')
        ->args([service('api_platform.filter_locator')])
        ->tag('api_platform.parameter_provider', ['key' => 'api_platform.serializer.filter_parameter_provider', 'priority' => -895]);

    $services->alias('ApiPlatform\State\SerializerContextBuilderInterface', 'api_platform.serializer.context_builder');

    $services->set('api_platform.serializer.context_builder.filter', 'ApiPlatform\Serializer\SerializerFilterContextBuilder')
        ->decorate('api_platform.serializer.context_builder', null, 0)
        ->args([
            service('api_platform.metadata.resource.metadata_collection_factory'),
            service('api_platform.filter_locator'),
            service('api_platform.serializer.context_builder.filter.inner'),
        ]);

    $services->set('api_platform.serializer.property_filter', 'ApiPlatform\Serializer\Filter\PropertyFilter')
        ->abstract()
        ->arg('$parameterName', 'properties')
        ->arg('$overrideDefaultProperties', false)
        ->arg('$whitelist', null)
        ->arg('$nameConverter', service('api_platform.name_converter')->ignoreOnInvalid());

    $services->alias('ApiPlatform\Serializer\Filter\PropertyFilter', 'api_platform.serializer.property_filter');

    $services->set('api_platform.serializer.group_filter', 'ApiPlatform\Serializer\Filter\GroupFilter')
        ->abstract();

    $services->alias('ApiPlatform\Serializer\Filter\GroupFilter', 'api_platform.serializer.group_filter');

    $services->set('api_platform.serializer.normalizer.item', 'ApiPlatform\Serializer\ItemNormalizer')
        ->args([
            service('api_platform.metadata.property.name_collection_factory'),
            service('api_platform.metadata.property.metadata_factory'),
            service('api_platform.iri_converter'),
            service('api_platform.resource_class_resolver'),
            service('api_platform.property_accessor'),
            service('api_platform.name_converter')->ignoreOnInvalid(),
            service('serializer.mapping.class_metadata_factory')->ignoreOnInvalid(),
            null,
            service('api_platform.metadata.resource.metadata_collection_factory')->ignoreOnInvalid(),
            service('api_platform.security.resource_access_checker')->ignoreOnInvalid(),
            [],
            service('api_platform.http_cache.tag_collector')->ignoreOnInvalid(),
        ])
        ->tag('serializer.normalizer', ['priority' => -895]);

    $services->set('api_platform.serializer.mapping.class_metadata_factory', 'ApiPlatform\Serializer\Mapping\Factory\ClassMetadataFactory')
        ->decorate('serializer.mapping.class_metadata_factory', null, -1)
        ->args([service('api_platform.serializer.mapping.class_metadata_factory.inner')]);

    $services->set('api_platform.serializer.mapping.cache_class_metadata_factory', 'Symfony\Component\Serializer\Mapping\Factory\CacheClassMetadataFactory')
        ->decorate('api_platform.serializer.mapping.class_metadata_factory', null, -2)
        ->args([
            service('api_platform.serializer.mapping.cache_class_metadata_factory.inner'),
            service('serializer.mapping.cache.symfony'),
        ]);

    $services->set('api_platform.path_segment_name_generator.underscore', 'ApiPlatform\Metadata\Operation\UnderscorePathSegmentNameGenerator');

    $services->set('api_platform.path_segment_name_generator.dash', 'ApiPlatform\Metadata\Operation\DashPathSegmentNameGenerator');

    $services->set('api_platform.metadata.path_segment_name_generator.underscore', 'ApiPlatform\Metadata\Operation\UnderscorePathSegmentNameGenerator')
        ->args([service('api_platform.inflector')->nullOnInvalid()]);

    $services->set('api_platform.metadata.path_segment_name_generator.dash', 'ApiPlatform\Metadata\Operation\DashPathSegmentNameGenerator')
        ->args([service('api_platform.inflector')->nullOnInvalid()]);

    $services->set('api_platform.metadata.inflector', 'ApiPlatform\Metadata\Util\Inflector');

    $services->set('api_platform.cache.route_name_resolver')
        ->parent('cache.system')
        ->tag('cache.pool');

    $services->set('api_platform.route_loader', 'ApiPlatform\Symfony\Routing\ApiLoader')
        ->args([
            service('kernel'),
            service('api_platform.metadata.resource.name_collection_factory'),
            service('api_platform.metadata.resource.metadata_collection_factory'),
            service('service_container'),
            '%api_platform.formats%',
            '%api_platform.resource_class_directories%',
            '%api_platform.graphql.enabled%',
            '%api_platform.enable_entrypoint%',
            '%api_platform.enable_docs%',
            '%api_platform.graphql.graphiql.enabled%',
        ])
        ->tag('routing.loader');

    $services->set('api_platform.symfony.iri_converter.skolem', 'ApiPlatform\Symfony\Routing\SkolemIriConverter')
        ->args([service('api_platform.router')]);

    $services->set('api_platform.api.identifiers_extractor', 'ApiPlatform\Metadata\IdentifiersExtractor')
        ->args([
            service('api_platform.metadata.resource.metadata_collection_factory'),
            service('api_platform.resource_class_resolver'),
            service('api_platform.metadata.property.name_collection_factory'),
            service('api_platform.metadata.property.metadata_factory'),
            service('api_platform.property_accessor'),
        ]);

    $services->alias('api_platform.identifiers_extractor', 'api_platform.api.identifiers_extractor');

    $services->alias('ApiPlatform\Metadata\IdentifiersExtractorInterface', 'api_platform.api.identifiers_extractor');

    $services->set('api_platform.uri_variables.converter', 'ApiPlatform\Metadata\UriVariablesConverter')
        ->args([
            service('api_platform.metadata.property.metadata_factory'),
            service('api_platform.metadata.resource.metadata_collection_factory'),
            tagged_iterator('api_platform.uri_variables.transformer'),
        ]);

    $services->set('api_platform.uri_variables.transformer.integer', 'ApiPlatform\Metadata\UriVariableTransformer\IntegerUriVariableTransformer')
        ->tag('api_platform.uri_variables.transformer', ['priority' => -100]);

    $services->set('api_platform.uri_variables.transformer.date_time', 'ApiPlatform\Metadata\UriVariableTransformer\DateTimeUriVariableTransformer')
        ->tag('api_platform.uri_variables.transformer', ['priority' => -100]);

    $services->alias('api_platform.iri_converter', 'api_platform.symfony.iri_converter');

    $services->set('api_platform.symfony.iri_converter', 'ApiPlatform\Symfony\Routing\IriConverter')
        ->args([
            service('api_platform.state_provider.locator'),
            service('api_platform.router'),
            service('api_platform.api.identifiers_extractor'),
            service('api_platform.resource_class_resolver'),
            service('api_platform.metadata.resource.metadata_collection_factory'),
            service('api_platform.uri_variables.converter'),
            service('api_platform.symfony.iri_converter.skolem'),
            service('api_platform.metadata.operation.metadata_factory'),
        ]);

    $services->alias('ApiPlatform\Metadata\IriConverterInterface', 'api_platform.symfony.iri_converter');

    $services->set('api_platform.state.error_provider', 'ApiPlatform\State\ErrorProvider')
        ->arg('$debug', '%kernel.debug%')
        ->arg('$resourceClassResolver', service('api_platform.resource_class_resolver'))
        ->arg('$resourceMetadataCollectionFactory', service('api_platform.metadata.resource.metadata_collection_factory'))
        ->tag('api_platform.state_provider', ['key' => 'api_platform.state.error_provider']);

    $services->set('api_platform.normalizer.constraint_violation_list', 'ApiPlatform\Serializer\ConstraintViolationListNormalizer')
        ->args([
            '%api_platform.validator.serialize_payload_fields%',
            service('api_platform.name_converter')->ignoreOnInvalid(),
        ])
        ->tag('serializer.normalizer', ['priority' => -780]);

    $services->set('api_platform.serializer.property_metadata_loader', 'ApiPlatform\Serializer\Mapping\Loader\PropertyMetadataLoader')
        ->args([service('api_platform.metadata.property.name_collection_factory')]);
};
