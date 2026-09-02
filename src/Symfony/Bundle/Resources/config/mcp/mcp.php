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

use ApiPlatform\Mcp\Capability\Registry\Loader;
use ApiPlatform\Mcp\Capability\Registry\SecureRegistry;
use ApiPlatform\Mcp\JsonSchema\SchemaFactory;
use ApiPlatform\Mcp\Metadata\Operation\Factory\OperationMetadataFactory;
use ApiPlatform\Mcp\Routing\IriConverter;
use ApiPlatform\Mcp\State\ToolProvider;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('api_platform.mcp.json_schema.schema_factory', SchemaFactory::class)
        ->args([
            service('api_platform.json_schema.schema_factory'),
        ]);

    $services->set('api_platform.mcp.loader', Loader::class)
        ->args([
            service('api_platform.metadata.resource.name_collection_factory'),
            service('api_platform.metadata.resource.metadata_collection_factory'),
            service('api_platform.mcp.json_schema.schema_factory'),
        ])
        ->tag('mcp.loader');

    // Decorates the SDK registry so the SDK's own list handlers stay in charge (they receive the
    // configured mcp.pagination_limit, which the previous custom handler silently overrode).
    // Loading API Platform elements on first read heals a persistent runtime (e.g. FrankenPHP
    // worker mode) where the SDK builds the registry once and may capture an empty state.
    $services->set('api_platform.mcp.secure_registry', SecureRegistry::class)
        ->decorate('mcp.registry')
        ->args([
            service('api_platform.mcp.secure_registry.inner'),
            service('api_platform.mcp.loader'),
        ])
        ->arg('$operationMetadataFactory', service('api_platform.mcp.metadata.operation.mcp_factory'))
        ->arg('$resourceAccessChecker', service('api_platform.security.resource_access_checker')->ignoreOnInvalid())
        ->arg('$requestStack', service('request_stack'));

    $services->set('api_platform.mcp.iri_converter', IriConverter::class)
        ->decorate('api_platform.iri_converter', null, 300)
        ->args([
            service('api_platform.mcp.iri_converter.inner'),
        ]);

    $services->set(ToolProvider::class, ToolProvider::class)
        ->args([
            service('object_mapper'),
        ])
        ->tag('api_platform.state_provider');

    $services->alias('api_platform.mcp.state.tool_provider', ToolProvider::class);

    $services->set('api_platform.mcp.metadata.operation.mcp_factory', OperationMetadataFactory::class)
        ->args([
            service('api_platform.metadata.resource.name_collection_factory'),
            service('api_platform.metadata.resource.metadata_collection_factory'),
        ]);
};
