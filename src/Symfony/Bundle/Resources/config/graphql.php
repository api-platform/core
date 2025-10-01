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

    $services->set('api_platform.graphql.executor', 'ApiPlatform\GraphQl\Executor')
        ->args([
            '%api_platform.graphql.introspection.enabled%',
            '%api_platform.graphql.max_query_complexity%',
            '%api_platform.graphql.max_query_depth%',
        ]);

    $services->set('api_platform.graphql.resolver_locator', 'Symfony\Component\DependencyInjection\ServiceLocator')
        ->tag('container.service_locator');

    $services->set('api_platform.graphql.iterable_type', 'ApiPlatform\GraphQl\Type\Definition\IterableType')
        ->tag('api_platform.graphql.type');

    $services->set('api_platform.graphql.upload_type', 'ApiPlatform\GraphQl\Type\Definition\UploadType')
        ->tag('api_platform.graphql.type');

    $services->set('api_platform.graphql.type_locator', 'Symfony\Component\DependencyInjection\ServiceLocator')
        ->tag('container.service_locator');

    $services->set('api_platform.graphql.types_container', 'ApiPlatform\GraphQl\Type\TypesContainer');

    $services->set('api_platform.graphql.types_factory', 'ApiPlatform\GraphQl\Type\TypesFactory')
        ->args([service('api_platform.graphql.type_locator')]);

    $services->set('api_platform.graphql.fields_builder_locator', 'Symfony\Component\DependencyInjection\ServiceLocator')
        ->args([[service('api_platform.graphql.fields_builder')]])
        ->tag('container.service_locator');

    $services->set('api_platform.graphql.action.entrypoint', 'ApiPlatform\GraphQl\Action\EntrypointAction')
        ->public()
        ->args([
            service('api_platform.graphql.schema_builder'),
            service('api_platform.graphql.executor'),
            service('api_platform.graphql.action.graphiql')->nullOnInvalid(),
            service('serializer'),
            service('api_platform.graphql.error_handler'),
            '%kernel.debug%',
            '%api_platform.graphql.graphiql.enabled%',
            '%api_platform.graphql.default_ide%',
        ]);

    $services->set('api_platform.graphql.action.graphiql', 'ApiPlatform\GraphQl\Action\GraphiQlAction')
        ->public()
        ->args([
            service('twig'),
            service('api_platform.router'),
            '%api_platform.graphql.graphiql.enabled%',
            '%api_platform.title%',
            '%api_platform.asset_package%',
        ]);

    $services->set('api_platform.graphql.error_handler', 'ApiPlatform\GraphQl\Error\ErrorHandler');

    $services->set('api_platform.graphql.subscription.subscription_identifier_generator', 'ApiPlatform\GraphQl\Subscription\SubscriptionIdentifierGenerator');

    $services->set('api_platform.graphql.cache.subscription')
        ->parent('cache.system')
        ->tag('cache.pool');

    $services->set('api_platform.graphql.command.export_command', 'ApiPlatform\Symfony\Bundle\Command\GraphQlExportCommand')
        ->args([service('api_platform.graphql.schema_builder')])
        ->tag('console.command');

    $services->set('api_platform.graphql.type_converter', 'ApiPlatform\GraphQl\Type\TypeConverter')
        ->args([
            service('api_platform.graphql.type_builder'),
            service('api_platform.graphql.types_container'),
            service('api_platform.metadata.resource.metadata_collection_factory'),
            service('api_platform.metadata.property.metadata_factory'),
        ]);

    $services->set('api_platform.graphql.type_builder', 'ApiPlatform\GraphQl\Type\TypeBuilder')
        ->args([
            service('api_platform.graphql.types_container'),
            service('api_platform.graphql.resolver.resource_field'),
            service('api_platform.graphql.fields_builder_locator'),
            service('api_platform.pagination'),
        ]);

    $services->set('api_platform.graphql.fields_builder', 'ApiPlatform\GraphQl\Type\FieldsBuilder')
        ->args([
            service('api_platform.metadata.property.name_collection_factory'),
            service('api_platform.metadata.property.metadata_factory'),
            service('api_platform.metadata.resource.metadata_collection_factory'),
            service('api_platform.resource_class_resolver'),
            service('api_platform.graphql.types_container'),
            service('api_platform.graphql.type_builder'),
            service('api_platform.graphql.type_converter'),
            service('api_platform.graphql.resolver.factory'),
            service('api_platform.filter_locator'),
            service('api_platform.pagination'),
            service('api_platform.name_converter')->ignoreOnInvalid(),
            '%api_platform.graphql.nesting_separator%',
            service('api_platform.inflector')->nullOnInvalid(),
        ]);

    $services->set('api_platform.graphql.schema_builder', 'ApiPlatform\GraphQl\Type\SchemaBuilder')
        ->args([
            service('api_platform.metadata.resource.name_collection_factory'),
            service('api_platform.metadata.resource.metadata_collection_factory'),
            service('api_platform.graphql.types_factory'),
            service('api_platform.graphql.types_container'),
            service('api_platform.graphql.fields_builder'),
        ]);

    $services->set('api_platform.graphql.serializer.context_builder', 'ApiPlatform\GraphQl\Serializer\SerializerContextBuilder')
        ->args([service('api_platform.name_converter')->ignoreOnInvalid()]);

    $services->alias('ApiPlatform\GraphQl\Serializer\SerializerContextBuilderInterface', 'api_platform.graphql.serializer.context_builder');

    $services->alias('api_platform.graphql.state_provider', 'api_platform.state_provider.locator');

    $services->alias('api_platform.graphql.state_processor', 'api_platform.graphql.state_processor.normalize');

    $services->set('api_platform.graphql.state_provider.read', 'ApiPlatform\GraphQl\State\Provider\ReadProvider')
        ->decorate('api_platform.graphql.state_provider', null, 500)
        ->args([
            service('api_platform.graphql.state_provider.read.inner'),
            service('api_platform.symfony.iri_converter'),
            service('api_platform.graphql.serializer.context_builder')->ignoreOnInvalid(),
            '%api_platform.graphql.nesting_separator%',
        ]);

    $services->set('api_platform.graphql.state_provider.parameter', 'ApiPlatform\State\Provider\ParameterProvider')
        ->decorate('api_platform.graphql.state_provider', null, 300)
        ->args([
            service('api_platform.graphql.state_provider.parameter.inner'),
            tagged_locator('api_platform.parameter_provider', 'key'),
        ]);

    $services->set('api_platform.graphql.state_provider.resolver', 'ApiPlatform\GraphQl\State\Provider\ResolverProvider')
        ->decorate('api_platform.graphql.state_provider', null, 190)
        ->args([
            service('api_platform.graphql.state_provider.resolver.inner'),
            service('api_platform.graphql.resolver_locator'),
        ]);

    $services->set('api_platform.graphql.state_provider.denormalizer', 'ApiPlatform\GraphQl\State\Provider\DenormalizeProvider')
        ->decorate('api_platform.graphql.state_provider', null, 300)
        ->args([
            service('api_platform.graphql.state_provider.denormalizer.inner'),
            service('serializer'),
            service('api_platform.graphql.serializer.context_builder'),
        ]);

    $services->set('api_platform.graphql.state_processor.subscription', 'ApiPlatform\GraphQl\State\Processor\SubscriptionProcessor')
        ->decorate('api_platform.graphql.state_processor', null, 200)
        ->args([
            service('api_platform.graphql.state_processor.subscription.inner'),
            service('api_platform.graphql.subscription.subscription_manager'),
            service('api_platform.graphql.subscription.mercure_iri_generator')->ignoreOnInvalid(),
        ]);

    $services->set('api_platform.graphql.state_processor.write', 'ApiPlatform\State\Processor\WriteProcessor')
        ->decorate('api_platform.graphql.state_processor', null, 100)
        ->args([
            service('api_platform.graphql.state_processor.write.inner'),
            service('api_platform.state_processor.locator'),
        ]);

    $services->set('api_platform.graphql.state_processor.normalize', 'ApiPlatform\GraphQl\State\Processor\NormalizeProcessor')
        ->args([
            service('serializer'),
            service('api_platform.graphql.serializer.context_builder'),
            service('api_platform.pagination'),
        ]);

    $services->set('api_platform.graphql.resolver.factory', 'ApiPlatform\GraphQl\Resolver\Factory\ResolverFactory')
        ->args([
            service('api_platform.graphql.state_provider'),
            service('api_platform.graphql.state_processor'),
            service('api_platform.graphql.runtime_operation_metadata_factory'),
        ]);

    $services->set('api_platform.graphql.runtime_operation_metadata_factory', 'ApiPlatform\GraphQl\Metadata\RuntimeOperationMetadataFactory')
        ->args([
            service('api_platform.metadata.resource.metadata_collection_factory'),
            service('api_platform.router'),
        ]);

    $services->set('api_platform.graphql.resolver.resource_field', 'ApiPlatform\GraphQl\Resolver\ResourceFieldResolver')
        ->args([service('api_platform.symfony.iri_converter')]);

    $services->set('api_platform.graphql.normalizer.item', 'ApiPlatform\GraphQl\Serializer\ItemNormalizer')
        ->args([
            service('api_platform.metadata.property.name_collection_factory'),
            service('api_platform.metadata.property.metadata_factory'),
            service('api_platform.symfony.iri_converter'),
            service('api_platform.api.identifiers_extractor'),
            service('api_platform.resource_class_resolver'),
            service('api_platform.property_accessor'),
            service('api_platform.name_converter')->ignoreOnInvalid(),
            service('serializer.mapping.class_metadata_factory')->ignoreOnInvalid(),
            null,
            service('api_platform.metadata.resource.metadata_collection_factory')->ignoreOnInvalid(),
            service('api_platform.security.resource_access_checker')->ignoreOnInvalid(),
        ])
        ->tag('serializer.normalizer', ['priority' => -890]);

    $services->set('api_platform.graphql.normalizer.object', 'ApiPlatform\GraphQl\Serializer\ObjectNormalizer')
        ->args([
            service('serializer.normalizer.object'),
            service('api_platform.symfony.iri_converter'),
            service('api_platform.api.identifiers_extractor'),
        ])
        ->tag('serializer.normalizer', ['priority' => -995]);

    $services->set('api_platform.graphql.subscription.subscription_manager', 'ApiPlatform\GraphQl\Subscription\SubscriptionManager')
        ->args([
            service('api_platform.graphql.cache.subscription'),
            service('api_platform.graphql.subscription.subscription_identifier_generator'),
            service('api_platform.graphql.state_processor.normalize')->ignoreOnInvalid(),
            service('api_platform.symfony.iri_converter'),
            service('api_platform.metadata.resource.metadata_collection_factory'),
        ]);

    $services->set('api_platform.graphql.normalizer.error', 'ApiPlatform\GraphQl\Serializer\Exception\ErrorNormalizer')
        ->tag('serializer.normalizer', ['priority' => -790]);

    $services->set('api_platform.graphql.normalizer.validation_exception', 'ApiPlatform\GraphQl\Serializer\Exception\ValidationExceptionNormalizer')
        ->args(['%api_platform.exception_to_status%'])
        ->tag('serializer.normalizer', ['priority' => -780]);

    $services->set('api_platform.graphql.normalizer.http_exception', 'ApiPlatform\GraphQl\Serializer\Exception\HttpExceptionNormalizer')
        ->tag('serializer.normalizer', ['priority' => -780]);

    $services->set('api_platform.graphql.normalizer.runtime_exception', 'ApiPlatform\GraphQl\Serializer\Exception\RuntimeExceptionNormalizer')
        ->tag('serializer.normalizer', ['priority' => -780]);
};
