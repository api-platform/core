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

use ApiPlatform\HttpCache\BufferedPurger;
use ApiPlatform\HttpCache\State\AddTagsProcessor;
use ApiPlatform\Symfony\EventListener\HttpCachePurgeBufferListener;
use Symfony\Component\HttpKernel\KernelEvents;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('api_platform.http_cache_purger.processor.add_tags', AddTagsProcessor::class)
        ->decorate('api_platform.state_processor.respond', null, 0)
        ->args([
            service('api_platform.http_cache_purger.processor.add_tags.inner'),
            service('api_platform.iri_converter'),
            service('api_platform.http_cache.purger')->nullOnInvalid(),
        ]);

    $services->set('api_platform.http_cache.purger.buffered', BufferedPurger::class)
        ->decorate('api_platform.http_cache.purger')
        ->args([service('api_platform.http_cache.purger.buffered.inner')])
        ->tag('kernel.reset', ['method' => 'flush']);

    $services->set('api_platform.http_cache.purger.buffer_listener', HttpCachePurgeBufferListener::class)
        ->args([service('api_platform.http_cache.purger.buffered')])
        ->tag('kernel.event_listener', ['event' => KernelEvents::REQUEST, 'method' => 'onKernelRequest', 'priority' => 2048])
        ->tag('kernel.event_listener', ['event' => KernelEvents::RESPONSE, 'method' => 'onKernelResponse', 'priority' => -1024]);
};
