<?php


use ApiPlatform\Core\Api\FilterCollection;
use ApiPlatform\Core\Api\FilterCollectionFactory;
use Symfony\Component\DependencyInjection\ServiceLocator;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set('api_platform.filter_locator', ServiceLocator::class)
            ->tag('container.service_locator')
        ->set('api_platform.filter_collection_factory', FilterCollectionFactory::class)
        ->set('api_platform.filters', FilterCollection::class)
            ->args([service('api_platform.filter_locator'), ])
    ;
};
