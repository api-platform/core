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

use ApiPlatform\HttpCache\State\PurgeTagsProcessor;
use ApiPlatform\Symfony\Doctrine\EventListener\PurgeHttpCacheListener;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('api_platform.doctrine.listener.http_cache.purge', PurgeHttpCacheListener::class)
        ->args([
            service('api_platform.http_cache.purger'),
            service('api_platform.iri_converter'),
            service('api_platform.resource_class_resolver'),
            service('api_platform.property_accessor'),
            service('api_platform.object_mapper')->nullOnInvalid(),
            service('api_platform.object_mapper.metadata_factory')->nullOnInvalid(),
        ])
        ->tag('doctrine.event_listener', ['event' => 'preUpdate'])
        ->tag('doctrine.event_listener', ['event' => 'onFlush'])
        ->tag('doctrine.event_listener', ['event' => 'postFlush']);

    $services->set('api_platform.http_cache.purge_tags.persist_processor', PurgeTagsProcessor::class)
        ->decorate('api_platform.doctrine.orm.state.persist_processor')
        ->args([
            service('api_platform.http_cache.purge_tags.persist_processor.inner'),
            service('api_platform.http_cache.purger'),
            tagged_iterator('api_platform.http_cache.purge_tag_provider'),
        ]);

    $services->set('api_platform.http_cache.purge_tags.remove_processor', PurgeTagsProcessor::class)
        ->decorate('api_platform.doctrine.orm.state.remove_processor')
        ->args([
            service('api_platform.http_cache.purge_tags.remove_processor.inner'),
            service('api_platform.http_cache.purger'),
            tagged_iterator('api_platform.http_cache.purge_tag_provider'),
        ]);
};
