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

    $services->set('api_platform.http_cache_purger.processor.add_tags', 'ApiPlatform\HttpCache\State\AddTagsProcessor')
        ->decorate('api_platform.state_processor.respond', null, 0)
        ->args([
            service('api_platform.http_cache_purger.processor.add_tags.inner'),
            service('api_platform.iri_converter'),
            service('api_platform.http_cache.purger')->nullOnInvalid(),
        ]);
};
