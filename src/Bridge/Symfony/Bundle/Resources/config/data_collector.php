<?php


use ApiPlatform\Core\Bridge\Symfony\Bundle\DataCollector\RequestDataCollector;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set('api_platform.data_collector.request', RequestDataCollector::class)
            ->args([service('api_platform.metadata.resource.metadata_factory'), service('api_platform.filter_locator'), service('api_platform.collection_data_provider'), service('api_platform.item_data_provider'), service('api_platform.subresource_data_provider'), service('api_platform.data_persister'), ])
            ->tag('data_collector', ['priority' => 334,])
    ;
};
