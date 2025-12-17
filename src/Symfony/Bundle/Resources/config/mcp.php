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
use ApiPlatform\Mcp\Metadata\Operation\Factory\OperationMetadataFactory;
use ApiPlatform\Mcp\Routing\IriConverter;
use ApiPlatform\Mcp\Server\Handler;
use ApiPlatform\Mcp\State\StructuredContentProcessor;
use ApiPlatform\Mcp\State\ToolProvider;

return function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('api_platform.mcp.loader', Loader::class)
        ->args([
            service('api_platform.metadata.resource.name_collection_factory'),
            service('api_platform.metadata.resource.metadata_collection_factory'),
            service('api_platform.json_schema.schema_factory'),
        ])
        ->tag('mcp.loader');

    $services->set('api_platform.mcp.iri_converter', IriConverter::class)
        ->decorate('api_platform.iri_converter', null, 300)
        ->args([
            service('api_platform.mcp.iri_converter.inner'),
        ]);

    $services->set('api_platform.mcp.handler', Handler::class)
        ->args([
            service('api_platform.mcp.metadata.operation.mcp_factory'),
            service('api_platform.state_provider.main'),
            service('api_platform.state_processor.main'),
            service('request_stack'),
            service('monolog.logger.mcp'),
        ])
        ->tag('mcp.request_handler');

    $services->set('api_platform.mcp.state.tool_provider', ToolProvider::class)
        ->args([
            service('object_mapper'),
        ])
        ->tag('api_platform.state_provider');

    $services->set('api_platform.mcp.metadata.operation.mcp_factory', OperationMetadataFactory::class)
        ->args([
            service('api_platform.metadata.resource.name_collection_factory'),
            service('api_platform.metadata.resource.metadata_collection_factory'),
        ]);

    $services->set('api_platform.mcp.state_processor.structured_content', StructuredContentProcessor::class)
        ->decorate('api_platform.state_processor.main', null, 200)
        ->args([
            service('api_platform.serializer'),
            service('api_platform.serializer.context_builder'),
            service('api_platform.mcp.state_processor.structured_content.inner'),
        ]);
};
