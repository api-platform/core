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

use ApiPlatform\HttpCache\SouinPurger;
use ApiPlatform\HttpCache\VarnishPurger;
use ApiPlatform\HttpCache\VarnishXKeyPurger;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->alias('api_platform.http_cache.purger.varnish', 'api_platform.http_cache.purger.varnish.ban');

    $services->set('api_platform.http_cache.purger.varnish.ban', VarnishPurger::class)
        ->args([tagged_iterator('api_platform.http_cache.http_client')]);

    $services->set('api_platform.http_cache.purger.varnish.xkey', VarnishXKeyPurger::class)
        ->args([
            tagged_iterator('api_platform.http_cache.http_client'),
            '%api_platform.http_cache.invalidation.max_header_length%',
            '%api_platform.http_cache.invalidation.xkey.glue%',
        ]);

    $services->set('api_platform.http_cache.purger.souin', SouinPurger::class)
        ->args([
            tagged_iterator('api_platform.http_cache.http_client'),
            '%api_platform.http_cache.invalidation.max_header_length%',
        ]);
};
