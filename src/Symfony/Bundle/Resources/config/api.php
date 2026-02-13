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

use ApiPlatform\Metadata\IdentifiersExtractor;
use ApiPlatform\Metadata\IdentifiersExtractorInterface;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Operation\DashPathSegmentNameGenerator;
use ApiPlatform\Metadata\Operation\UnderscorePathSegmentNameGenerator;
use ApiPlatform\Metadata\ResourceClassResolver;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Metadata\UriVariablesConverter;
use ApiPlatform\Metadata\UriVariableTransformer\ApiResourceUriVariableTransformer;
use ApiPlatform\Metadata\UriVariableTransformer\DateTimeUriVariableTransformer;
use ApiPlatform\Metadata\UriVariableTransformer\IntegerUriVariableTransformer;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use ApiPlatform\Metadata\Util\Inflector;
use ApiPlatform\Serializer\ConstraintViolationListNormalizer;
use ApiPlatform\Serializer\Filter\GroupFilter;
use ApiPlatform\Serializer\Filter\PropertyFilter;
use ApiPlatform\Serializer\ItemNormalizer;
use ApiPlatform\Serializer\Mapping\Factory\ClassMetadataFactory;
use ApiPlatform\Serializer\Mapping\Loader\PropertyMetadataLoader;
use ApiPlatform\Serializer\OperationResourceClassResolver;
use ApiPlatform\Serializer\OperationResourceClassResolverInterface;
use ApiPlatform\Serializer\Parameter\SerializerFilterParameterProvider;
use ApiPlatform\Serializer\SerializerContextBuilder;
use ApiPlatform\Serializer\SerializerFilterContextBuilder;
use ApiPlatform\State\ErrorProvider;
use ApiPlatform\State\SerializerContextBuilderInterface;
use ApiPlatform\Symfony\Action\NotExposedAction;
use ApiPlatform\Symfony\Action\NotFoundAction;
use ApiPlatform\Symfony\Routing\ApiLoader;
use ApiPlatform\Symfony\Routing\IriConverter;
use ApiPlatform\Symfony\Routing\Router;
use ApiPlatform\Symfony\Routing\SkolemIriConverter;
use Negotiation\Negotiator;
use Symfony\Component\Serializer\Mapping\Factory\CacheClassMetadataFactory;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

return function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('api_platform.action.not_found', NotFoundAction::class)
        ->public();

    $services->alias(NotFoundAction::class, 'api_platform.action.not_found')
        ->public();

    $services->set('api_platform.action.not_exposed', NotExposedAction::class)
        ->public();

    $services->alias(NotExposedAction::class, 'api_platform.action.not_exposed')
        ->public();

    $services->alias('api_platform.serializer', 'serializer');

    $services->alias('api_platform.property_accessor', 'property_accessor');

    $services->alias('api_platform.property_info', 'property_info');

    $services->set('api_platform.negotiator', Negotiator::class);

    $services->set('api_platform.resource_class_resolver', ResourceClassResolver::class)
        ->args([service('api_platform.metadata.resource.name_collection_factory')]);

    $services->alias(ResourceClassResolverInterface::class, 'api_platform.resource_class_resolver');

    $services->alias(UrlGeneratorInterface::class, 'api_platform.router');

    $services->set('api_platform.router', Router::class)
        ->args([
            service('router'),
            '%api_platform.url_generation_strategy%',
        ]);

    $services->set('api_platform.serializer.context_builder', SerializerContextBuilder::class)
        ->arg(0, service('api_platform.metadata.resource.metadata_collection_factory'))
        ->arg('$debug', '%kernel.debug%');

    $services->set('api_platform.serializer.filter_parameter_provider', SerializerFilterParameterProvider::class)
        ->args([service('api_platform.filter_locator')])
        ->tag('api_platform.parameter_provider', ['key' => 'api_platform.serializer.filter_parameter_provider', 'priority' => -895]);

    $services->alias(SerializerContextBuilderInterface::class, 'api_platform.serializer.context_builder');

    $services->set('api_platform.serializer.context_builder.filter', SerializerFilterContextBuilder::class)
        ->decorate('api_platform.serializer.context_builder', null, 0)
        ->args([
            service('api_platform.metadata.resource.metadata_collection_factory'),
            service('api_platform.filter_locator'),
            service('api_platform.serializer.context_builder.filter.inner'),
        ]);

    $services->set('api_platform.serializer.property_filter', PropertyFilter::class)
        ->abstract()
        ->arg('$parameterName', 'properties')
        ->arg('$overrideDefaultProperties', false)
        ->arg('$whitelist', null)
        ->arg('$nameConverter', service('api_platform.name_converter')->ignoreOnInvalid());

    $services->alias(PropertyFilter::class, 'api_platform.serializer.property_filter');

    $services->set('api_platform.serializer.group_filter', GroupFilter::class)
        ->abstract();

    $services->alias(GroupFilter::class, 'api_platform.serializer.group_filter');

    $services->set('api_platform.serializer.operation_resource_resolver', OperationResourceClassResolver::class);
    $services->alias(OperationResourceClassResolverInterface::class, 'api_platform.serializer.operation_resource_resolver');

    $services->set('api_platform.serializer.normalizer.item', ItemNormalizer::class)
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
            service('api_platform.serializer.operation_resource_resolver'),
        ])
        ->tag('serializer.normalizer', ['priority' => -895]);

    $services->set('api_platform.normalizer.object', ObjectNormalizer::class)
        ->args([
            service('serializer.mapping.class_metadata_factory'),
            service('api_platform.name_converter')->ignoreOnInvalid(),
            service('serializer.property_accessor'),
            service('property_info')->ignoreOnInvalid(),
            service('serializer.mapping.class_discriminator_resolver')->ignoreOnInvalid(),
            null,
            [],
            service('property_info')->ignoreOnInvalid(),
        ])
        ->tag('serializer.normalizer', ['built_in' => true, 'priority' => -1000]);

    $services->set('api_platform.serializer.mapping.class_metadata_factory', ClassMetadataFactory::class)
        ->decorate('serializer.mapping.class_metadata_factory', null, -1)
        ->args([service('api_platform.serializer.mapping.class_metadata_factory.inner')]);

    $services->set('api_platform.serializer.mapping.cache_class_metadata_factory', CacheClassMetadataFactory::class)
        ->decorate('api_platform.serializer.mapping.class_metadata_factory', null, -2)
        ->args([
            service('api_platform.serializer.mapping.cache_class_metadata_factory.inner'),
            service('serializer.mapping.cache.symfony'),
        ]);

    $services->set('api_platform.path_segment_name_generator.underscore', UnderscorePathSegmentNameGenerator::class);

    $services->set('api_platform.path_segment_name_generator.dash', DashPathSegmentNameGenerator::class);

    $services->set('api_platform.metadata.path_segment_name_generator.underscore', UnderscorePathSegmentNameGenerator::class)
        ->args([service('api_platform.inflector')->nullOnInvalid()]);

    $services->set('api_platform.metadata.path_segment_name_generator.dash', DashPathSegmentNameGenerator::class)
        ->args([service('api_platform.inflector')->nullOnInvalid()]);

    $services->set('api_platform.metadata.inflector', Inflector::class);

    $services->set('api_platform.cache.route_name_resolver')
        ->parent('cache.system')
        ->tag('cache.pool');

    $services->set('api_platform.route_loader', ApiLoader::class)
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

    $services->set('api_platform.symfony.iri_converter.skolem', SkolemIriConverter::class)
        ->args([service('api_platform.router')]);

    $services->set('api_platform.api.identifiers_extractor', IdentifiersExtractor::class)
        ->args([
            service('api_platform.metadata.resource.metadata_collection_factory'),
            service('api_platform.resource_class_resolver'),
            service('api_platform.metadata.property.name_collection_factory'),
            service('api_platform.metadata.property.metadata_factory'),
            service('api_platform.property_accessor'),
        ]);

    $services->alias('api_platform.identifiers_extractor', 'api_platform.api.identifiers_extractor');

    $services->alias(IdentifiersExtractorInterface::class, 'api_platform.api.identifiers_extractor');

    $services->set('api_platform.uri_variables.converter', UriVariablesConverter::class)
        ->args([
            service('api_platform.metadata.property.metadata_factory'),
            service('api_platform.metadata.resource.metadata_collection_factory'),
            tagged_iterator('api_platform.uri_variables.transformer'),
        ]);

    $services->set('api_platform.uri_variables.transformer.integer', IntegerUriVariableTransformer::class)
        ->tag('api_platform.uri_variables.transformer', ['priority' => -100]);

    $services->set('api_platform.uri_variables.transformer.date_time', DateTimeUriVariableTransformer::class)
        ->tag('api_platform.uri_variables.transformer', ['priority' => -100]);

    $services->set('api_platform.uri_variables.transformer.api_resource', ApiResourceUriVariableTransformer::class)
        ->args([
            service('api_platform.api.identifiers_extractor'),
            service('api_platform.resource_class_resolver'),
        ])
        ->tag('api_platform.uri_variables.transformer', ['priority' => -100]);

    $services->alias('api_platform.iri_converter', 'api_platform.symfony.iri_converter');

    $services->set('api_platform.symfony.iri_converter', IriConverter::class)
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

    $services->alias(IriConverterInterface::class, 'api_platform.symfony.iri_converter');

    $services->set('api_platform.state.error_provider', ErrorProvider::class)
        ->arg('$debug', '%kernel.debug%')
        ->arg('$resourceClassResolver', service('api_platform.resource_class_resolver'))
        ->arg('$resourceMetadataCollectionFactory', service('api_platform.metadata.resource.metadata_collection_factory'))
        ->tag('api_platform.state_provider', ['key' => 'api_platform.state.error_provider']);

    $services->set('api_platform.normalizer.constraint_violation_list', ConstraintViolationListNormalizer::class)
        ->args([
            '%api_platform.validator.serialize_payload_fields%',
            service('api_platform.name_converter')->ignoreOnInvalid(),
        ])
        ->tag('serializer.normalizer', ['priority' => -780]);

    $services->set('api_platform.serializer.property_metadata_loader', PropertyMetadataLoader::class)
        ->args([service('api_platform.metadata.property.name_collection_factory')]);
};
