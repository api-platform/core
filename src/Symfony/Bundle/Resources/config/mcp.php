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

use ApiPlatform\Mcp\Factory\McpDocumentationFactory;
use ApiPlatform\Mcp\Factory\McpOperationFactory;
use ApiPlatform\Mcp\Metadata\Factory\Operation\McpOperationMetadataFactory;
use ApiPlatform\Mcp\Metadata\Factory\Resource\McpNameResourceMetadataCollectionFactory;
use ApiPlatform\Mcp\Server\Builder;
use ApiPlatform\Mcp\Server\Handler\Request\CallToolHandler;
use ApiPlatform\Mcp\Server\Handler\Request\ReadResourceHandler;

return static function (ContainerConfigurator $container): void {
    $container->services()
        // Generator for MCP capabilities based on API Platform operations
        ->set('api_platform.mcp.operation_factory', McpOperationFactory::class)
            ->args([
                service('api_platform.metadata.resource.name_collection_factory'),
                service('api_platform.metadata.resource.metadata_collection_factory'),
                service('router.request_context'),
                service('api_platform.json_schema.schema_factory'),
            ])
            ->tag('api_platform.mcp_capability_factory')

        // Generator for MCP capabilities based on API Platform documentation endpoints
        ->set('api_platform.mcp.documentation_factory', McpDocumentationFactory::class)
            ->args([
                service('router.request_context'),
            ])
            ->tag('api_platform.mcp_capability_factory')

        // Creates an Mpc specific name and adds it to an operation's `extraProperties`
        ->set('api_platform.mcp.metadata_resource_factory.mcp_name', McpNameResourceMetadataCollectionFactory::class)
            ->decorate('api_platform.metadata.resource.metadata_collection_factory', priority: -10)
            ->args([
                service('.inner'),
            ])

        ->set('api_platform.mcp.operation_metadata_factory', McpOperationMetadataFactory::class)
            ->args([
                service('api_platform.metadata.resource.name_collection_factory'),
                service('api_platform.metadata.resource.metadata_collection_factory'),
            ])

        ->set('api_platform.mcp.request_handler.call_tool', CallToolHandler::class)
            ->args([
                service('http_kernel'),
                service('api_platform.mcp.operation_metadata_factory'),
                service('router'),
            ])
            ->tag('api_platform.mcp.request_handler')

        ->set('api_platform.mcp.request_handler.read_resource', ReadResourceHandler::class)
            ->args([
                service('http_kernel'),
            ])
            ->tag('api_platform.mcp.request_handler')

        ->set('api_platform.mcp.server.builder', Builder::class)
            ->decorate('mcp.server.builder')
            ->args([
                service('.inner'),
                tagged_iterator('api_platform.mcp_capability_factory'),
                tagged_iterator('api_platform.mcp.request_handler'),
            ]);
};
