<?php


use ApiPlatform\Core\Bridge\Symfony\Messenger\DataPersister;
use ApiPlatform\Core\Bridge\Symfony\Messenger\DataTransformer;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->alias('api_platform.message_bus', 'messenger.default_bus')
        ->set('api_platform.messenger.data_persister', DataPersister::class)
            ->args([service('api_platform.metadata.resource.metadata_factory'), service('api_platform.message_bus'), ])
            ->tag('api_platform.data_persister', ['priority' => -900,])
        ->set('api_platform.messenger.data_transformer', DataTransformer::class)
            ->args([service('api_platform.metadata.resource.metadata_factory'), ])
            ->tag('api_platform.data_transformer', ['priority' => -10,])
    ;
};
