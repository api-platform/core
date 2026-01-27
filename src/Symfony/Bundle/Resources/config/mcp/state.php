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

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('api_platform.mcp.state_processor.write', 'ApiPlatform\State\Processor\WriteProcessor')
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
            service('api_platform.state_provider.main'),
            service('api_platform.mcp.state_processor'),
            service('request_stack'),
            service('logger')->ignoreOnInvalid(),
        ])
        ->tag('mcp.request_handler');
};
