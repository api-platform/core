<?php


use ApiPlatform\Core\DataPersister\ChainDataPersister;
use ApiPlatform\Core\DataPersister\DataPersisterInterface;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set('api_platform.data_persister', ChainDataPersister::class)
            ->args([tagged('api_platform.data_persister'), ])
        ->alias(DataPersisterInterface::class, 'api_platform.data_persister')
    ;
};
