<?php


use ApiPlatform\Core\Bridge\Symfony\Bundle\Command\GraphQlExportCommand;
use ApiPlatform\Core\GraphQl\Action\EntrypointAction;
use ApiPlatform\Core\GraphQl\Action\GraphQlPlaygroundAction;
use ApiPlatform\Core\GraphQl\Action\GraphiQlAction;
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

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set('api_platform.graphql.executor', Executor::class)
        ->set('api_platform.graphql.resolver.factory.item', ItemResolverFactory::class)
            ->args([service('api_platform.graphql.resolver.stage.read'), service('api_platform.graphql.resolver.stage.security'), service('api_platform.graphql.resolver.stage.security_post_denormalize'), service('api_platform.graphql.resolver.stage.serialize'), service('api_platform.graphql.query_resolver_locator'), service('api_platform.metadata.resource.metadata_factory'), ])
        ->set('api_platform.graphql.resolver.factory.collection', CollectionResolverFactory::class)
            ->args([service('api_platform.graphql.resolver.stage.read'), service('api_platform.graphql.resolver.stage.security'), service('api_platform.graphql.resolver.stage.security_post_denormalize'), service('api_platform.graphql.resolver.stage.serialize'), service('api_platform.graphql.query_resolver_locator'), service('api_platform.metadata.resource.metadata_factory'), service('request_stack'), ])
        ->set('api_platform.graphql.resolver.factory.item_mutation', ItemMutationResolverFactory::class)
            ->args([service('api_platform.graphql.resolver.stage.read'), service('api_platform.graphql.resolver.stage.security'), service('api_platform.graphql.resolver.stage.security_post_denormalize'), service('api_platform.graphql.resolver.stage.serialize'), service('api_platform.graphql.resolver.stage.deserialize'), service('api_platform.graphql.resolver.stage.write'), service('api_platform.graphql.resolver.stage.validate'), service('api_platform.graphql.mutation_resolver_locator'), service('api_platform.metadata.resource.metadata_factory'), ])
        ->set('api_platform.graphql.resolver.stage.read', ReadStage::class)
            ->args([service('api_platform.metadata.resource.metadata_factory'), service('api_platform.iri_converter'), service('api_platform.collection_data_provider'), service('api_platform.subresource_data_provider'), service('api_platform.graphql.serializer.context_builder'), param('api_platform.graphql.nesting_separator'), ])
        ->set('api_platform.graphql.resolver.stage.security', SecurityStage::class)
            ->args([service('api_platform.metadata.resource.metadata_factory'), service('api_platform.security.resource_access_checker')->ignoreOnInvalid, ])
        ->set('api_platform.graphql.resolver.stage.security_post_denormalize', SecurityPostDenormalizeStage::class)
            ->args([service('api_platform.metadata.resource.metadata_factory'), service('api_platform.security.resource_access_checker')->ignoreOnInvalid, ])
        ->set('api_platform.graphql.resolver.stage.serialize', SerializeStage::class)
            ->args([service('api_platform.metadata.resource.metadata_factory'), service('serializer'), service('api_platform.graphql.serializer.context_builder'), service('api_platform.pagination'), ])
        ->set('api_platform.graphql.resolver.stage.deserialize', DeserializeStage::class)
            ->args([service('api_platform.metadata.resource.metadata_factory'), service('serializer'), service('api_platform.graphql.serializer.context_builder'), ])
        ->set('api_platform.graphql.resolver.stage.write', WriteStage::class)
            ->args([service('api_platform.metadata.resource.metadata_factory'), service('api_platform.data_persister'), service('api_platform.graphql.serializer.context_builder'), ])
        ->set('api_platform.graphql.resolver.stage.validate', ValidateStage::class)
            ->args([service('api_platform.metadata.resource.metadata_factory'), service('api_platform.validator'), ])
        ->set('api_platform.graphql.resolver.resource_field', ResourceFieldResolver::class)
            ->args([service('api_platform.iri_converter'), ])
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
            ->args([service('api_platform.graphql.type_locator'), ])
        ->set('api_platform.graphql.type_converter', TypeConverter::class)
            ->args([service('api_platform.graphql.type_builder'), service('api_platform.graphql.types_container'), service('api_platform.metadata.resource.metadata_factory'), ])
        ->set('api_platform.graphql.type_builder', TypeBuilder::class)
            ->args([service('api_platform.graphql.types_container'), service('api_platform.graphql.resolver.resource_field'), service('api_platform.graphql.fields_builder_locator'), ])
        ->set('api_platform.graphql.fields_builder', FieldsBuilder::class)
            ->args([service('api_platform.metadata.property.name_collection_factory'), service('api_platform.metadata.property.metadata_factory'), service('api_platform.metadata.resource.metadata_factory'), service('api_platform.graphql.types_container'), service('api_platform.graphql.type_builder'), service('api_platform.graphql.type_converter'), service('api_platform.graphql.resolver.factory.item'), service('api_platform.graphql.resolver.factory.collection'), service('api_platform.graphql.resolver.factory.item_mutation'), service('api_platform.filter_locator'), service('api_platform.pagination'), service('api_platform.name_converter')->ignoreOnInvalid, param('api_platform.graphql.nesting_separator'), ])
        ->set('api_platform.graphql.fields_builder_locator', ServiceLocator::class)
            ->args([[service('api_platform.graphql.fields_builder'), ],])
            ->tag('container.service_locator')
        ->set('api_platform.graphql.schema_builder', SchemaBuilder::class)
            ->args([service('api_platform.metadata.resource.name_collection_factory'), service('api_platform.metadata.resource.metadata_factory'), service('api_platform.graphql.types_factory'), service('api_platform.graphql.types_container'), service('api_platform.graphql.fields_builder'), ])
        ->set('api_platform.graphql.action.entrypoint', EntrypointAction::class)
            ->args([service('api_platform.graphql.schema_builder'), service('api_platform.graphql.executor'), service('api_platform.graphql.action.graphiql'), service('api_platform.graphql.action.graphql_playground'), param('kernel.debug'), param('api_platform.graphql.graphiql.enabled'), param('api_platform.graphql.graphql_playground.enabled'), param('api_platform.graphql.default_ide'), ])
            ->public()
        ->set('api_platform.graphql.action.graphiql', GraphiQlAction::class)
            ->args([service('twig'), service('api_platform.router'), param('api_platform.graphql.graphiql.enabled'), param('api_platform.title'), ])
            ->public()
        ->set('api_platform.graphql.action.graphql_playground', GraphQlPlaygroundAction::class)
            ->args([service('twig'), service('api_platform.router'), param('api_platform.graphql.graphql_playground.enabled'), param('api_platform.title'), ])
            ->public()
        ->set('api_platform.graphql.normalizer.item', ItemNormalizer::class)
            ->args([service('api_platform.metadata.property.name_collection_factory'), service('api_platform.metadata.property.metadata_factory'), service('api_platform.iri_converter'), service('api_platform.identifiers_extractor.cached'), service('api_platform.resource_class_resolver'), service('api_platform.property_accessor'), service('api_platform.name_converter')->ignoreOnInvalid, service('serializer.mapping.class_metadata_factory')->ignoreOnInvalid, service('api_platform.item_data_provider')->ignoreOnInvalid, param('api_platform.allow_plain_identifiers'), 'null', tagged('api_platform.data_transformer')->ignoreOnInvalid, service('api_platform.metadata.resource.metadata_factory')->ignoreOnInvalid, ])
            ->tag('serializer.normalizer', ['priority' => -890,])
        ->set('api_platform.graphql.normalizer.object', ObjectNormalizer::class)
            ->args([service('serializer.normalizer.object'), service('api_platform.iri_converter'), service('api_platform.identifiers_extractor.cached'), ])
            ->tag('serializer.normalizer', ['priority' => -995,])
        ->set('api_platform.graphql.serializer.context_builder', SerializerContextBuilder::class)
            ->args([service('api_platform.metadata.resource.metadata_factory'), service('api_platform.name_converter')->ignoreOnInvalid, ])
        ->alias(SerializerContextBuilderInterface::class, 'api_platform.graphql.serializer.context_builder')
        ->set('api_platform.graphql.command.export_command', GraphQlExportCommand::class)
            ->args([service('api_platform.graphql.schema_builder'), ])
            ->tag('console.command')
    ;
};
