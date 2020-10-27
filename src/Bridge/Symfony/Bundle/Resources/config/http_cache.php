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

use ApiPlatform\Core\HttpCache\EventListener\AddHeadersListener;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set('api_platform.http_cache.listener.response.configure', AddHeadersListener::class)
            ->args([param('api_platform.http_cache.etag'), param('api_platform.http_cache.max_age'), param('api_platform.http_cache.shared_max_age'), param('api_platform.http_cache.vary'), param('api_platform.http_cache.public'), service('api_platform.metadata.resource.metadata_factory')])
            ->tag('kernel.event_listener', ['event' => 'kernel.response', 'method' => 'onKernelResponse', 'priority' => -1]);
};
