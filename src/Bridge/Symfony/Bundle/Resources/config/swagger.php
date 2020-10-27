<?php


use ApiPlatform\Core\Bridge\Symfony\Bundle\Command\SwaggerCommand;
use ApiPlatform\Core\Swagger\Serializer\ApiGatewayNormalizer;
use ApiPlatform\Core\Swagger\Serializer\DocumentationNormalizer;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set('api_platform.swagger.normalizer.documentation', DocumentationNormalizer::class)
            ->args([service('api_platform.metadata.resource.metadata_factory'), service('api_platform.metadata.property.name_collection_factory'), service('api_platform.metadata.property.metadata_factory'), service('api_platform.json_schema.schema_factory'), service('api_platform.json_schema.type_factory'), service('api_platform.operation_path_resolver'), 'null', service('api_platform.filter_locator'), 'null', param('api_platform.oauth.enabled'), param('api_platform.oauth.type'), param('api_platform.oauth.flow'), param('api_platform.oauth.tokenUrl'), param('api_platform.oauth.authorizationUrl'), param('api_platform.oauth.scopes'), param('api_platform.swagger.api_keys'), service('api_platform.subresource_operation_factory'), param('api_platform.collection.pagination.enabled'), param('api_platform.collection.pagination.page_parameter_name'), param('api_platform.collection.pagination.client_items_per_page'), param('api_platform.collection.pagination.items_per_page_parameter_name'), param('api_platform.formats'), param('api_platform.collection.pagination.client_enabled'), param('api_platform.collection.pagination.enabled_parameter_name'), [],param('api_platform.swagger.versions'), ])
            ->tag('serializer.normalizer', ['priority' => -790,])
        ->set('api_platform.swagger.normalizer.api_gateway', ApiGatewayNormalizer::class)
            ->decorate('api_platform.swagger.normalizer.documentation', null, -1)
            ->args([service('api_platform.swagger.normalizer.api_gateway.inner'), ])
            ->tag('serializer.normalizer', ['priority' => -780,])
        ->set('api_platform.swagger.command.swagger_command', SwaggerCommand::class)
            ->args([service('api_platform.serializer'), service('api_platform.metadata.resource.name_collection_factory'), param('api_platform.title'), param('api_platform.description'), param('api_platform.version'), 'null', param('api_platform.swagger.versions'), ])
            ->tag('console.command')
    ;
};
