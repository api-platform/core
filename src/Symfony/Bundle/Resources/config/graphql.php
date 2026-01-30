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

use ApiPlatform\GraphQl\Action\EntrypointAction;
use ApiPlatform\GraphQl\Action\GraphiQlAction;
use ApiPlatform\GraphQl\Error\ErrorHandler;
use ApiPlatform\GraphQl\Executor;
use ApiPlatform\GraphQl\Metadata\RuntimeOperationMetadataFactory;
use ApiPlatform\GraphQl\Resolver\Factory\ResolverFactory;
use ApiPlatform\GraphQl\Resolver\ResourceFieldResolver;
use ApiPlatform\GraphQl\Serializer\Exception\ErrorNormalizer;
use ApiPlatform\GraphQl\Serializer\Exception\HttpExceptionNormalizer;
use ApiPlatform\GraphQl\Serializer\Exception\RuntimeExceptionNormalizer;
use ApiPlatform\GraphQl\Serializer\Exception\ValidationExceptionNormalizer;
use ApiPlatform\GraphQl\Serializer\ItemNormalizer;
use ApiPlatform\GraphQl\Serializer\ObjectNormalizer;
use ApiPlatform\GraphQl\Serializer\SerializerContextBuilder;
use ApiPlatform\GraphQl\Serializer\SerializerContextBuilderInterface;
use ApiPlatform\GraphQl\State\Processor\NormalizeProcessor;
use ApiPlatform\GraphQl\State\Processor\SubscriptionProcessor;
use ApiPlatform\GraphQl\State\Provider\DenormalizeProvider;
use ApiPlatform\GraphQl\State\Provider\ReadProvider;
use ApiPlatform\GraphQl\State\Provider\ResolverProvider;
use ApiPlatform\GraphQl\Subscription\SubscriptionIdentifierGenerator;
use ApiPlatform\GraphQl\Subscription\SubscriptionManager;
use ApiPlatform\GraphQl\Type\Definition\IterableType;
use ApiPlatform\GraphQl\Type\Definition\UploadType;
use ApiPlatform\GraphQl\Type\FieldsBuilder;
use ApiPlatform\GraphQl\Type\SchemaBuilder;
use ApiPlatform\GraphQl\Type\TypeBuilder;
use ApiPlatform\GraphQl\Type\TypeConverter;
use ApiPlatform\GraphQl\Type\TypesContainer;
use ApiPlatform\GraphQl\Type\TypesFactory;
use ApiPlatform\State\Processor\WriteProcessor;
use ApiPlatform\State\Provider\ParameterProvider;
use ApiPlatform\Symfony\Bundle\Command\GraphQlExportCommand;
use Symfony\Component\DependencyInjection\ServiceLocator;

return function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('api_platform.graphql.executor', Executor::class)
        ->args([
            '%api_platform.graphql.introspection.enabled%',
            '%api_platform.graphql.max_query_complexity%',
            '%api_platform.graphql.max_query_depth%',
        ]);

    $services->set('api_platform.graphql.resolver_locator', ServiceLocator::class)
        ->tag('container.service_locator');

    $services->set('api_platform.graphql.iterable_type', IterableType::class)
        ->tag('api_platform.graphql.type');

    $services->set('api_platform.graphql.upload_type', UploadType::class)
        ->tag('api_platform.graphql.type');

    $services->set('api_platform.graphql.type_locator', ServiceLocator::class)
        ->tag('container.service_locator');

    $services->set('api_platform.graphql.types_container', TypesContainer::class);

    $services->set('api_platform.graphql.types_factory', TypesFactory::class)
        ->args([service('api_platform.graphql.type_locator')]);

    $services->set('api_platform.graphql.fields_builder_locator', ServiceLocator::class)
        ->args([[service('api_platform.graphql.fields_builder')]])
        ->tag('container.service_locator');

    $services->set('api_platform.graphql.action.entrypoint', EntrypointAction::class)
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

    $services->set('api_platform.graphql.action.graphiql', GraphiQlAction::class)
        ->public()
        ->args([
            service('twig'),
            service('api_platform.router'),
            '%api_platform.graphql.graphiql.enabled%',
            '%api_platform.title%',
            '%api_platform.asset_package%',
        ]);

    $services->set('api_platform.graphql.error_handler', ErrorHandler::class);

    $services->set('api_platform.graphql.subscription.subscription_identifier_generator', SubscriptionIdentifierGenerator::class);

    $services->set('api_platform.graphql.cache.subscription')
        ->parent('cache.system')
        ->tag('cache.pool');

    $services->set('api_platform.graphql.command.export_command', GraphQlExportCommand::class)
        ->args([service('api_platform.graphql.schema_builder')])
        ->tag('console.command');

    $services->set('api_platform.graphql.type_converter', TypeConverter::class)
        ->args([
            service('api_platform.graphql.type_builder'),
            service('api_platform.graphql.types_container'),
            service('api_platform.metadata.resource.metadata_collection_factory'),
            service('api_platform.metadata.property.metadata_factory'),
        ]);

    $services->set('api_platform.graphql.type_builder', TypeBuilder::class)
        ->args([
            service('api_platform.graphql.types_container'),
            service('api_platform.graphql.resolver.resource_field'),
            service('api_platform.graphql.fields_builder_locator'),
            service('api_platform.pagination'),
        ]);

    $services->set('api_platform.graphql.fields_builder', FieldsBuilder::class)
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

    $services->set('api_platform.graphql.schema_builder', SchemaBuilder::class)
        ->args([
            service('api_platform.metadata.resource.name_collection_factory'),
            service('api_platform.metadata.resource.metadata_collection_factory'),
            service('api_platform.graphql.types_factory'),
            service('api_platform.graphql.types_container'),
            service('api_platform.graphql.fields_builder'),
        ]);

    $services->set('api_platform.graphql.serializer.context_builder', SerializerContextBuilder::class)
        ->args([service('api_platform.name_converter')->ignoreOnInvalid()]);

    $services->alias(SerializerContextBuilderInterface::class, 'api_platform.graphql.serializer.context_builder');

    $services->alias('api_platform.graphql.state_provider', 'api_platform.state_provider.locator');

    $services->alias('api_platform.graphql.state_processor', 'api_platform.graphql.state_processor.normalize');

    $services->set('api_platform.graphql.state_provider.read', ReadProvider::class)
        ->decorate('api_platform.graphql.state_provider', null, 500)
        ->args([
            service('api_platform.graphql.state_provider.read.inner'),
            service('api_platform.symfony.iri_converter'),
            service('api_platform.graphql.serializer.context_builder')->ignoreOnInvalid(),
            '%api_platform.graphql.nesting_separator%',
        ]);

    $services->set('api_platform.graphql.state_provider.parameter', ParameterProvider::class)
        ->decorate('api_platform.graphql.state_provider', null, 300)
        ->args([
            service('api_platform.graphql.state_provider.parameter.inner'),
            tagged_locator('api_platform.parameter_provider', 'key'),
        ]);

    $services->set('api_platform.graphql.state_provider.resolver', ResolverProvider::class)
        ->decorate('api_platform.graphql.state_provider', null, 190)
        ->args([
            service('api_platform.graphql.state_provider.resolver.inner'),
            service('api_platform.graphql.resolver_locator'),
        ]);

    $services->set('api_platform.graphql.state_provider.denormalizer', DenormalizeProvider::class)
        ->decorate('api_platform.graphql.state_provider', null, 300)
        ->args([
            service('api_platform.graphql.state_provider.denormalizer.inner'),
            service('serializer'),
            service('api_platform.graphql.serializer.context_builder'),
        ]);

    $services->set('api_platform.graphql.state_processor.subscription', SubscriptionProcessor::class)
        ->decorate('api_platform.graphql.state_processor', null, 200)
        ->args([
            service('api_platform.graphql.state_processor.subscription.inner'),
            service('api_platform.graphql.subscription.subscription_manager'),
            service('api_platform.graphql.subscription.mercure_iri_generator')->ignoreOnInvalid(),
        ]);

    $services->set('api_platform.graphql.state_processor.write', WriteProcessor::class)
        ->decorate('api_platform.graphql.state_processor', null, 100)
        ->args([
            service('api_platform.graphql.state_processor.write.inner'),
            service('api_platform.state_processor.locator'),
        ]);

    $services->set('api_platform.graphql.state_processor.normalize', NormalizeProcessor::class)
        ->args([
            service('serializer'),
            service('api_platform.graphql.serializer.context_builder'),
            service('api_platform.pagination'),
        ]);

    $services->set('api_platform.graphql.resolver.factory', ResolverFactory::class)
        ->args([
            service('api_platform.graphql.state_provider'),
            service('api_platform.graphql.state_processor'),
            service('api_platform.graphql.runtime_operation_metadata_factory'),
        ]);

    $services->set('api_platform.graphql.runtime_operation_metadata_factory', RuntimeOperationMetadataFactory::class)
        ->args([
            service('api_platform.metadata.resource.metadata_collection_factory'),
            service('api_platform.router'),
        ]);

    $services->set('api_platform.graphql.resolver.resource_field', ResourceFieldResolver::class)
        ->args([service('api_platform.symfony.iri_converter')]);

    $services->set('api_platform.graphql.normalizer.item', ItemNormalizer::class)
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

    $services->set('api_platform.graphql.normalizer.object', ObjectNormalizer::class)
        ->args([
            service('api_platform.normalizer.object'),
            service('api_platform.symfony.iri_converter'),
            service('api_platform.api.identifiers_extractor'),
        ])
        ->tag('serializer.normalizer', ['priority' => -995]);

    $services->set('api_platform.graphql.subscription.subscription_manager', SubscriptionManager::class)
        ->args([
            service('api_platform.graphql.cache.subscription'),
            service('api_platform.graphql.subscription.subscription_identifier_generator'),
            service('api_platform.graphql.state_processor.normalize')->ignoreOnInvalid(),
            service('api_platform.symfony.iri_converter'),
            service('api_platform.metadata.resource.metadata_collection_factory'),
        ]);

    $services->set('api_platform.graphql.normalizer.error', ErrorNormalizer::class)
        ->tag('serializer.normalizer', ['priority' => -790]);

    $services->set('api_platform.graphql.normalizer.validation_exception', ValidationExceptionNormalizer::class)
        ->args(['%api_platform.exception_to_status%'])
        ->tag('serializer.normalizer', ['priority' => -780]);

    $services->set('api_platform.graphql.normalizer.http_exception', HttpExceptionNormalizer::class)
        ->tag('serializer.normalizer', ['priority' => -780]);

    $services->set('api_platform.graphql.normalizer.runtime_exception', RuntimeExceptionNormalizer::class)
        ->tag('serializer.normalizer', ['priority' => -780]);
};
