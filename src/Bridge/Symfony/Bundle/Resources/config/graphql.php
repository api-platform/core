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

use ApiPlatform\Core\Bridge\Symfony\Bundle\Command\GraphQlExportCommand;
use ApiPlatform\Core\GraphQl\Action\EntrypointAction;
use ApiPlatform\Core\GraphQl\Action\GraphiQlAction;
use ApiPlatform\Core\GraphQl\Action\GraphQlPlaygroundAction;
use ApiPlatform\Core\GraphQl\Executor;
use ApiPlatform\Core\GraphQl\Resolver\Factory\CollectionResolverFactory;
use ApiPlatform\Core\GraphQl\Resolver\Factory\ItemMutationResolverFactory;
use ApiPlatform\Core\GraphQl\Resolver\Factory\ItemResolverFactory;
use ApiPlatform\Core\GraphQl\Resolver\ResourceFieldResolver;
use ApiPlatform\Core\GraphQl\Resolver\Stage\DeserializeStage;
use ApiPlatform\Core\GraphQl\Resolver\Stage\ReadStage;
use ApiPlatform\Core\GraphQl\Resolver\Stage\SecurityPostDenormalizeStage;
use ApiPlatform\Core\GraphQl\Resolver\Stage\SecurityStage;
use ApiPlatform\Core\GraphQl\Resolver\Stage\SerializeStage;
use ApiPlatform\Core\GraphQl\Resolver\Stage\ValidateStage;
use ApiPlatform\Core\GraphQl\Resolver\Stage\WriteStage;
use ApiPlatform\Core\GraphQl\Serializer\ItemNormalizer;
use ApiPlatform\Core\GraphQl\Serializer\ObjectNormalizer;
use ApiPlatform\Core\GraphQl\Serializer\SerializerContextBuilder;
use ApiPlatform\Core\GraphQl\Serializer\SerializerContextBuilderInterface;
use ApiPlatform\Core\GraphQl\Type\Definition\IterableType;
use ApiPlatform\Core\GraphQl\Type\Definition\UploadType;
use ApiPlatform\Core\GraphQl\Type\FieldsBuilder;
use ApiPlatform\Core\GraphQl\Type\SchemaBuilder;
use ApiPlatform\Core\GraphQl\Type\TypeBuilder;
use ApiPlatform\Core\GraphQl\Type\TypeConverter;
use ApiPlatform\Core\GraphQl\Type\TypesContainer;
use ApiPlatform\Core\GraphQl\Type\TypesFactory;
use Symfony\Component\DependencyInjection\ServiceLocator;

use ApiPlatform\Core\GraphQl\Resolver\Factory\ItemSubscriptionResolverFactory;
use ApiPlatform\Core\GraphQl\Error\ErrorHandler;
use ApiPlatform\Core\GraphQl\Serializer\Exception\ErrorNormalizer;
use ApiPlatform\Core\GraphQl\Serializer\Exception\ValidationExceptionNormalizer;
use ApiPlatform\Core\GraphQl\Serializer\Exception\HttpExceptionNormalizer;
use ApiPlatform\Core\GraphQl\Serializer\Exception\RuntimeExceptionNormalizer;
use ApiPlatform\Core\GraphQl\Subscription\SubscriptionManager;
use ApiPlatform\Core\GraphQl\Subscription\SubscriptionIdentifierGenerator;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set('api_platform.graphql.executor', Executor::class)

        ->set('api_platform.graphql.resolver.factory.item', ItemResolverFactory::class)
            ->args([ref('api_platform.graphql.resolver.stage.read'), ref('api_platform.graphql.resolver.stage.security'), ref('api_platform.graphql.resolver.stage.security_post_denormalize'), ref('api_platform.graphql.resolver.stage.serialize'), ref('api_platform.graphql.query_resolver_locator'), ref('api_platform.metadata.resource.metadata_factory')])

        ->set('api_platform.graphql.resolver.factory.collection', CollectionResolverFactory::class)
            ->args([ref('api_platform.graphql.resolver.stage.read'), ref('api_platform.graphql.resolver.stage.security'), ref('api_platform.graphql.resolver.stage.security_post_denormalize'), ref('api_platform.graphql.resolver.stage.serialize'), ref('api_platform.graphql.query_resolver_locator'), ref('api_platform.metadata.resource.metadata_factory'), ref('request_stack')])

        ->set('api_platform.graphql.resolver.factory.item_mutation', ItemMutationResolverFactory::class)
            ->args([ref('api_platform.graphql.resolver.stage.read'), ref('api_platform.graphql.resolver.stage.security'), ref('api_platform.graphql.resolver.stage.security_post_denormalize'), ref('api_platform.graphql.resolver.stage.serialize'), ref('api_platform.graphql.resolver.stage.deserialize'), ref('api_platform.graphql.resolver.stage.write'), ref('api_platform.graphql.resolver.stage.validate'), ref('api_platform.graphql.mutation_resolver_locator'), ref('api_platform.metadata.resource.metadata_factory')])

        ->set('api_platform.graphql.resolver.factory.item_subscription', ItemSubscriptionResolverFactory::class)
            ->args([ref('api_platform.graphql.resolver.stage.read'), ref('api_platform.graphql.resolver.stage.security'), ref('api_platform.graphql.resolver.stage.serialize'), ref('api_platform.metadata.resource.metadata_factory'), ref('api_platform.graphql.subscription.subscription_manager'), ref('api_platform.graphql.subscription.mercure_iri_generator')->ignoreOnInvalid()])

        ->set('api_platform.graphql.resolver.stage.read', ReadStage::class)
            ->args([ref('api_platform.metadata.resource.metadata_factory'), ref('api_platform.iri_converter'), ref('api_platform.collection_data_provider'), ref('api_platform.subresource_data_provider'), ref('api_platform.graphql.serializer.context_builder'), param('api_platform.graphql.nesting_separator')])

        ->set('api_platform.graphql.resolver.stage.security', SecurityStage::class)
            ->args([ref('api_platform.metadata.resource.metadata_factory'), ref('api_platform.security.resource_access_checker')->ignoreOnInvalid()])

        ->set('api_platform.graphql.resolver.stage.security_post_denormalize', SecurityPostDenormalizeStage::class)
            ->args([ref('api_platform.metadata.resource.metadata_factory'), ref('api_platform.security.resource_access_checker')->ignoreOnInvalid()])

        ->set('api_platform.graphql.resolver.stage.serialize', SerializeStage::class)
            ->args([ref('api_platform.metadata.resource.metadata_factory'), ref('serializer'), ref('api_platform.graphql.serializer.context_builder'), ref('api_platform.pagination')])

        ->set('api_platform.graphql.resolver.stage.deserialize', DeserializeStage::class)
            ->args([ref('api_platform.metadata.resource.metadata_factory'), ref('serializer'), ref('api_platform.graphql.serializer.context_builder')])

        ->set('api_platform.graphql.resolver.stage.write', WriteStage::class)
            ->args([ref('api_platform.metadata.resource.metadata_factory'), ref('api_platform.data_persister'), ref('api_platform.graphql.serializer.context_builder')])

        ->set('api_platform.graphql.resolver.stage.validate', ValidateStage::class)
            ->args([ref('api_platform.metadata.resource.metadata_factory'), ref('api_platform.validator')])

        ->set('api_platform.graphql.resolver.resource_field', ResourceFieldResolver::class)
            ->args([ref('api_platform.iri_converter')])

        ->set('api_platform.graphql.query_resolver_locator', ServiceLocator::class)
            ->tag('container.service_locator')

        ->set('api_platform.graphql.mutation_resolver_locator', ServiceLocator::class)
            ->tag('container.service_locator')

        ->set('api_platform.graphql.iterable_type', IterableType::class)
            ->tag('api_platform.graphql.type')

        ->set('api_platform.graphql.upload_type', UploadType::class)
            ->tag('api_platform.graphql.type')

        ->set('api_platform.graphql.type_locator', ServiceLocator::class)
            ->tag('container.service_locator')

        ->set('api_platform.graphql.types_container', TypesContainer::class)

        ->set('api_platform.graphql.types_factory', TypesFactory::class)
            ->args([ref('api_platform.graphql.type_locator')])

        ->set('api_platform.graphql.type_converter', TypeConverter::class)
            ->args([ref('api_platform.graphql.type_builder'), ref('api_platform.graphql.types_container'), ref('api_platform.metadata.resource.metadata_factory')])

        ->set('api_platform.graphql.type_builder', TypeBuilder::class)
            ->args([ref('api_platform.graphql.types_container'), ref('api_platform.graphql.resolver.resource_field'), ref('api_platform.graphql.fields_builder_locator')])

        ->set('api_platform.graphql.fields_builder', FieldsBuilder::class)
            ->args([ref('api_platform.metadata.property.name_collection_factory'), ref('api_platform.metadata.property.metadata_factory'), ref('api_platform.metadata.resource.metadata_factory'), ref('api_platform.graphql.types_container'), ref('api_platform.graphql.type_builder'), ref('api_platform.graphql.type_converter'), ref('api_platform.graphql.resolver.factory.item'), ref('api_platform.graphql.resolver.factory.collection'), ref('api_platform.graphql.resolver.factory.item_mutation'), ref('api_platform.graphql.resolver.factory.item_subscription'), ref('api_platform.filter_locator'), ref('api_platform.pagination'), ref('api_platform.name_converter')->ignoreOnInvalid(), param('api_platform.graphql.nesting_separator')])

        ->set('api_platform.graphql.fields_builder_locator', ServiceLocator::class)
            ->args([[ref('api_platform.graphql.fields_builder')]])
            ->tag('container.service_locator')

        ->set('api_platform.graphql.schema_builder', SchemaBuilder::class)
            ->args([ref('api_platform.metadata.resource.name_collection_factory'), ref('api_platform.metadata.resource.metadata_factory'), ref('api_platform.graphql.types_factory'), ref('api_platform.graphql.types_container'), ref('api_platform.graphql.fields_builder')])

        ->set('api_platform.graphql.action.entrypoint', EntrypointAction::class)
            ->args([ref('api_platform.graphql.schema_builder'), ref('api_platform.graphql.executor'), ref('api_platform.graphql.action.graphiql'), ref('api_platform.graphql.action.graphql_playground'), ref('serializer'), ref('api_platform.graphql.error_handler'), param('kernel.debug'), param('api_platform.graphql.graphiql.enabled'), param('api_platform.graphql.graphql_playground.enabled'), param('api_platform.graphql.default_ide')])
            ->public()

        ->set('api_platform.graphql.action.graphiql', GraphiQlAction::class)
            ->args([ref('twig'), ref('api_platform.router'), param('api_platform.graphql.graphiql.enabled'), param('api_platform.title')])
            ->public()

        ->set('api_platform.graphql.action.graphql_playground', GraphQlPlaygroundAction::class)
            ->args([ref('twig'), ref('api_platform.router'), param('api_platform.graphql.graphql_playground.enabled'), param('api_platform.title')])
            ->public()

        ->set('api_platform.graphql.error_handler', ErrorHandler::class)

        ->set('api_platform.graphql.normalizer.item', ItemNormalizer::class)
            ->args([ref('api_platform.metadata.property.name_collection_factory'), ref('api_platform.metadata.property.metadata_factory'), ref('api_platform.iri_converter'), ref('api_platform.identifiers_extractor.cached'), ref('api_platform.resource_class_resolver'), ref('api_platform.property_accessor'), ref('api_platform.name_converter')->ignoreOnInvalid(), ref('serializer.mapping.class_metadata_factory')->ignoreOnInvalid(), ref('api_platform.item_data_provider')->ignoreOnInvalid(), param('api_platform.allow_plain_identifiers'), null, tagged_iterator('api_platform.data_transformer')->ignoreOnInvalid(), ref('api_platform.metadata.resource.metadata_factory')->ignoreOnInvalid()])
            ->tag('serializer.normalizer', ['priority' => -890])

        ->set('api_platform.graphql.normalizer.object', ObjectNormalizer::class)
            ->args([ref('serializer.normalizer.object'), ref('api_platform.iri_converter'), ref('api_platform.identifiers_extractor.cached')])
            ->tag('serializer.normalizer', ['priority' => -995])

        ->set('api_platform.graphql.normalizer.error', ErrorNormalizer::class)
            ->tag('serializer.normalizer', ['priority' => -790])

        ->set('api_platform.graphql.normalizer.validation_exception', ValidationExceptionNormalizer::class)
            ->tag('serializer.normalizer', ['priority' => -780])

        ->set('api_platform.graphql.normalizer.http_exception', HttpExceptionNormalizer::class)
            ->tag('serializer.normalizer', ['priority' => -780])

        ->set('api_platform.graphql.normalizer.runtime_exception', RuntimeExceptionNormalizer::class)
            ->tag('serializer.normalizer', ['priority' => -780])

        ->set('api_platform.graphql.serializer.context_builder', SerializerContextBuilder::class)
            ->args([ref('api_platform.metadata.resource.metadata_factory'), ref('api_platform.name_converter')->ignoreOnInvalid()])
        ->alias(SerializerContextBuilderInterface::class, 'api_platform.graphql.serializer.context_builder')

        ->set('api_platform.graphql.subscription.subscription_manager', SubscriptionManager::class)
            ->args([ref('api_platform.graphql.cache.subscription'), ref('api_platform.graphql.subscription.subscription_identifier_generator'), ref('api_platform.graphql.resolver.stage.serialize'), ref('api_platform.iri_converter')])

        ->set('api_platform.graphql.subscription.subscription_identifier_generator', SubscriptionIdentifierGenerator::class)

        ->set('api_platform.graphql.cache.subscription')
            ->parent('cache.system')
            ->tag('cache.pool')

        ->set('api_platform.graphql.command.export_command', GraphQlExportCommand::class)
            ->args([ref('api_platform.graphql.schema_builder')])
            ->tag('console.command');
};
