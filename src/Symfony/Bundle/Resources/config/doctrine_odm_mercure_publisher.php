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

    $services->set('api_platform.doctrine_mongodb.odm.listener.mercure.publish', 'ApiPlatform\Symfony\Doctrine\EventListener\PublishMercureUpdatesListener')
        ->args([
            service('api_platform.resource_class_resolver'),
            service('api_platform.symfony.iri_converter'),
            service('api_platform.metadata.resource.metadata_collection_factory'),
            service('api_platform.serializer'),
            '%api_platform.formats%',
            service('messenger.default_bus')->ignoreOnInvalid(),
            service('Symfony\Component\Mercure\HubRegistry'),
            service('api_platform.graphql.subscription.subscription_manager')->ignoreOnInvalid(),
            service('api_platform.graphql.subscription.mercure_iri_generator')->ignoreOnInvalid(),
            null,
            '%api_platform.mercure.include_type%',
        ])
        ->tag('doctrine_mongodb.odm.event_listener', ['event' => 'onFlush'])
        ->tag('doctrine_mongodb.odm.event_listener', ['event' => 'postFlush']);
};
