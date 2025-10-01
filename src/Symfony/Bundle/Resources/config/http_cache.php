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

    $services->set('api_platform.http_cache.processor.add_headers', 'ApiPlatform\HttpCache\State\AddHeadersProcessor')
        ->decorate('api_platform.state_processor.respond', null, 0)
        ->args([
            service('api_platform.http_cache.processor.add_headers.inner'),
            '%api_platform.http_cache.etag%',
            '%api_platform.http_cache.max_age%',
            '%api_platform.http_cache.shared_max_age%',
            '%api_platform.http_cache.vary%',
            '%api_platform.http_cache.public%',
        ]);
};
