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

use ApiPlatform\Mcp\Server\Handler;
use ApiPlatform\Mcp\State\StructuredContentProcessor;
use ApiPlatform\State\Processor\WriteProcessor;
use ApiPlatform\State\Provider\ContentNegotiationProvider;
use ApiPlatform\State\Provider\DeserializeProvider;
use ApiPlatform\State\Provider\ParameterProvider;
use ApiPlatform\State\Provider\ReadProvider;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    // A tool call is dispatched over JSON-RPC from within the handler, never as an HTTP request
    // cycle, so the kernel listeners that build the state pipeline in this mode never fire. MCP
    // therefore gets its own provider chain, mirroring "api_platform.state_provider.main" so that
    // security, parameters and validation behave the same whichever mode is configured.
    // See mcp/security.php, mcp/security_validator.php and mcp/validator.php for the decorators.
    $services->alias('api_platform.mcp.state_provider', 'api_platform.state_provider.locator');

    $services->set('api_platform.mcp.state_provider.read', ReadProvider::class)
        ->decorate('api_platform.mcp.state_provider', null, 500)
        ->args([
            service('api_platform.mcp.state_provider.read.inner'),
            service('api_platform.serializer.context_builder'),
        ]);

    $services->set('api_platform.mcp.state_provider.deserialize', DeserializeProvider::class)
        ->decorate('api_platform.mcp.state_provider', null, 300)
        ->args([
            service('api_platform.mcp.state_provider.deserialize.inner'),
            service('api_platform.serializer'),
            service('api_platform.serializer.context_builder'),
            service('translator')->nullOnInvalid(),
        ]);

    $services->set('api_platform.mcp.state_provider.parameter', ParameterProvider::class)
        ->decorate('api_platform.mcp.state_provider', null, 180)
        ->args([
            service('api_platform.mcp.state_provider.parameter.inner'),
            tagged_locator('api_platform.parameter_provider', 'key'),
        ]);

    $services->set('api_platform.mcp.state_provider.content_negotiation', ContentNegotiationProvider::class)
        ->decorate('api_platform.mcp.state_provider', null, 100)
        ->args([
            service('api_platform.mcp.state_provider.content_negotiation.inner'),
            service('api_platform.negotiator'),
            '%api_platform.formats%',
            '%api_platform.error_formats%',
        ]);

    $services->set('api_platform.mcp.state_processor.write', WriteProcessor::class)
        ->args([
            null,
            service('api_platform.state_processor.locator'),
        ]);

    $services->set('api_platform.mcp.state_processor', StructuredContentProcessor::class)
        ->args([
            service('api_platform.serializer'),
            service('api_platform.serializer.context_builder'),
            service('api_platform.mcp.state_processor.write'),
        ]);

    $services->set('api_platform.mcp.handler', Handler::class)
        ->args([
            service('api_platform.mcp.metadata.operation.mcp_factory'),
            service('api_platform.mcp.state_provider'),
            service('api_platform.mcp.state_processor'),
            service('request_stack'),
            service('logger')->ignoreOnInvalid(),
        ])
        ->tag('mcp.request_handler');
};
