<?php


use ApiPlatform\Core\Bridge\Doctrine\EventListener\PublishMercureUpdatesListener;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set('api_platform.doctrine.listener.mercure.publish', PublishMercureUpdatesListener::class)
            ->args([service('api_platform.resource_class_resolver'), service('api_platform.iri_converter'), service('api_platform.metadata.resource.metadata_factory'), service('api_platform.serializer'), param('api_platform.formats'), service('messenger.default_bus')->ignoreOnInvalid, service('mercure.hub.default.publisher'), ])
            ->tag('doctrine.event_listener', ['event' => 'onFlush',])
            ->tag('doctrine.event_listener', ['event' => 'postFlush',])
    ;
};
