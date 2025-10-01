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

    $services->alias('api_platform.http_cache.purger.varnish', 'api_platform.http_cache.purger.varnish.ban');

    $services->set('api_platform.http_cache.purger.varnish.ban', 'ApiPlatform\HttpCache\VarnishPurger')
        ->args([tagged_iterator('api_platform.http_cache.http_client')]);

    $services->set('api_platform.http_cache.purger.varnish.xkey', 'ApiPlatform\HttpCache\VarnishXKeyPurger')
        ->args([
            tagged_iterator('api_platform.http_cache.http_client'),
            '%api_platform.http_cache.invalidation.max_header_length%',
            '%api_platform.http_cache.invalidation.xkey.glue%',
        ]);

    $services->set('api_platform.http_cache.purger.souin', 'ApiPlatform\HttpCache\SouinPurger')
        ->args([
            tagged_iterator('api_platform.http_cache.http_client'),
            '%api_platform.http_cache.invalidation.max_header_length%',
        ]);
};
