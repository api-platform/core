<?php


use ApiPlatform\Core\HttpCache\EventListener\AddTagsListener;
use ApiPlatform\Core\HttpCache\VarnishPurger;
use GuzzleHttp\Client;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set('api_platform.http_cache.purger.varnish_client', Client::class)
        ->set('api_platform.http_cache.purger.varnish', VarnishPurger::class)
        ->set('api_platform.http_cache.listener.response.add_tags', AddTagsListener::class)
            ->args([service('api_platform.iri_converter'), ])
            ->tag('kernel.event_listener', ['event' => 'kernel.response','method' => 'onKernelResponse','priority' => -2,])
    ;
};
