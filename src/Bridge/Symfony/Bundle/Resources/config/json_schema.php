<?php


use ApiPlatform\Core\JsonSchema\Command\JsonSchemaGenerateCommand;
use ApiPlatform\Core\JsonSchema\SchemaFactory;
use ApiPlatform\Core\JsonSchema\SchemaFactoryInterface;
use ApiPlatform\Core\JsonSchema\TypeFactory;
use ApiPlatform\Core\JsonSchema\TypeFactoryInterface;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set('api_platform.json_schema.type_factory', TypeFactory::class)
            ->args([service('api_platform.resource_class_resolver'), ])
        ->alias(TypeFactoryInterface::class, 'api_platform.json_schema.type_factory')
        ->set('api_platform.json_schema.schema_factory', SchemaFactory::class)
            ->args([service('api_platform.json_schema.type_factory'), service('api_platform.metadata.resource.metadata_factory'), service('api_platform.metadata.property.name_collection_factory'), service('api_platform.metadata.property.metadata_factory'), service('api_platform.name_converter')->ignoreOnInvalid, service('api_platform.resource_class_resolver'), ])
        ->alias(SchemaFactoryInterface::class, 'api_platform.json_schema.schema_factory')
        ->set('api_platform.json_schema.json_schema_generate_command', JsonSchemaGenerateCommand::class)
            ->args([service('api_platform.json_schema.schema_factory'), param('api_platform.formats'), ])
            ->tag('console.command')
    ;
};
