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

return function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('api_platform.listener.exception', 'ApiPlatform\Symfony\EventListener\ExceptionListener')
        ->args([
            service('api_platform.error_listener')->nullOnInvalid(),
            '%api_platform.handle_symfony_errors%',
        ])
        ->tag('kernel.event_listener', ['event' => 'kernel.exception', 'method' => 'onKernelException', 'priority' => -96])
        ->tag('monolog.logger', ['channel' => 'request']);

    $services->set('api_platform.cache_warmer.cache_pool_clearer', 'ApiPlatform\Symfony\Bundle\CacheWarmer\CachePoolClearerCacheWarmer')
        ->args([
            service('cache.system_clearer'),
            ['api_platform.cache.metadata.property', 'api_platform.cache.metadata.resource', 'api_platform.cache.metadata.resource_collection', 'api_platform.cache.route_name_resolver', 'api_platform.cache.identifiers_extractor', 'api_platform.elasticsearch.cache.metadata.document'],
        ])
        ->tag('kernel.cache_warmer', ['priority' => 64]);
};
