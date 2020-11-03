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

use ApiPlatform\Core\Bridge\Doctrine\EventListener\PublishMercureUpdatesListener;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set('api_platform.doctrine_mongodb.odm.listener.mercure.publish', PublishMercureUpdatesListener::class)
            ->args([ref('api_platform.resource_class_resolver'), ref('api_platform.iri_converter'), ref('api_platform.metadata.resource.metadata_factory'), ref('api_platform.serializer'), '%api_platform.formats%', ref('messenger.default_bus')->ignoreOnInvalid(), ref('mercure.hub.default.publisher'), ref('api_platform.graphql.subscription.subscription_manager')->ignoreOnInvalid(), ref('api_platform.graphql.subscription.mercure_iri_generator')->ignoreOnInvalid()])
            ->tag('doctrine_mongodb.odm.event_listener', ['event' => 'onFlush'])
            ->tag('doctrine_mongodb.odm.event_listener', ['event' => 'postFlush']);
};
