<?php


use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\Client;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set('test.api_platform.client', Client::class)
            ->args([service('test.client'), ])
            ->public()
    ;
};
