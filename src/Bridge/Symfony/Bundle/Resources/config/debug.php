<?php


use ApiPlatform\Core\Bridge\Symfony\Bundle\DataPersister\TraceableChainDataPersister;
use ApiPlatform\Core\Bridge\Symfony\Bundle\DataProvider\TraceableChainCollectionDataProvider;
use ApiPlatform\Core\Bridge\Symfony\Bundle\DataProvider\TraceableChainItemDataProvider;
use ApiPlatform\Core\Bridge\Symfony\Bundle\DataProvider\TraceableChainSubresourceDataProvider;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set('debug.api_platform.collection_data_provider', TraceableChainCollectionDataProvider::class)
            ->decorate('api_platform.collection_data_provider', null, )
            ->args([service('debug.api_platform.collection_data_provider.inner'), ])
        ->set('debug.api_platform.item_data_provider', TraceableChainItemDataProvider::class)
            ->decorate('api_platform.item_data_provider', null, )
            ->args([service('debug.api_platform.item_data_provider.inner'), ])
        ->set('debug.api_platform.subresource_data_provider', TraceableChainSubresourceDataProvider::class)
            ->decorate('api_platform.subresource_data_provider', null, )
            ->args([service('debug.api_platform.subresource_data_provider.inner'), ])
        ->set('debug.api_platform.data_persister', TraceableChainDataPersister::class)
            ->decorate('api_platform.data_persister', null, )
            ->args([service('debug.api_platform.data_persister.inner'), ])
    ;
};
