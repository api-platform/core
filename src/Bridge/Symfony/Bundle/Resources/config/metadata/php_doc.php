<?php


use ApiPlatform\Core\Metadata\Resource\Factory\PhpDocResourceMetadataFactory;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set('api_platform.metadata.resource.metadata_factory.php_doc', PhpDocResourceMetadataFactory::class)
            ->decorate('api_platform.metadata.resource.metadata_factory', null, 30)
            ->args([service('api_platform.metadata.resource.metadata_factory.php_doc.inner'), ])
    ;
};
