<?php


use ApiPlatform\Core\Mercure\EventListener\AddLinkHeaderListener;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set('api_platform.mercure.listener.response.add_link_header', AddLinkHeaderListener::class)
            ->args([service('api_platform.metadata.resource.metadata_factory'), ])
            ->tag('kernel.event_listener', ['event' => 'kernel.response','method' => 'onKernelResponse',])
    ;
};
