<?php


use ApiPlatform\Core\Bridge\FosUser\EventListener;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set('api_platform.fos_user.event_listener', EventListener::class)
            ->args([service('fos_user.user_manager'), ])
            ->tag('kernel.event_listener', ['event' => 'kernel.view','method' => 'onKernelView','priority' => 24,])
    ;
};
