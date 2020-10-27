<?php


use ApiPlatform\Core\Metadata\Extractor\YamlExtractor;
use ApiPlatform\Core\Metadata\Property\Factory\ExtractorPropertyMetadataFactory;
use ApiPlatform\Core\Metadata\Property\Factory\ExtractorPropertyNameCollectionFactory;
use ApiPlatform\Core\Metadata\Resource\Factory\ExtractorResourceMetadataFactory;
use ApiPlatform\Core\Metadata\Resource\Factory\ExtractorResourceNameCollectionFactory;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set('api_platform.metadata.extractor.yaml', YamlExtractor::class)
            ->args([[],service('service_container'), ])
        ->set('api_platform.metadata.resource.name_collection_factory.yaml', ExtractorResourceNameCollectionFactory::class)
            ->decorate('api_platform.metadata.resource.name_collection_factory', null, )
            ->args([service('api_platform.metadata.extractor.yaml'), service('api_platform.metadata.resource.name_collection_factory.yaml.inner'), ])
        ->set('api_platform.metadata.resource.metadata_factory.yaml', ExtractorResourceMetadataFactory::class)
            ->decorate('api_platform.metadata.resource.metadata_factory', null, 40)
            ->args([service('api_platform.metadata.extractor.yaml'), service('api_platform.metadata.resource.metadata_factory.yaml.inner'), ])
        ->set('api_platform.metadata.property.name_collection_factory.yaml', ExtractorPropertyNameCollectionFactory::class)
            ->decorate('api_platform.metadata.property.name_collection_factory', null, )
            ->args([service('api_platform.metadata.extractor.yaml'), service('api_platform.metadata.property.name_collection_factory.yaml.inner'), ])
        ->set('api_platform.metadata.property.metadata_factory.yaml', ExtractorPropertyMetadataFactory::class)
            ->decorate('api_platform.metadata.property.metadata_factory', null, 20)
            ->args([service('api_platform.metadata.extractor.yaml'), service('api_platform.metadata.property.metadata_factory.yaml.inner'), ])
    ;
};
