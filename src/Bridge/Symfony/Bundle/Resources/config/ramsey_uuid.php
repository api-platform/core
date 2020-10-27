<?php


use ApiPlatform\Core\Bridge\RamseyUuid\Identifier\Normalizer\UuidNormalizer;
use ApiPlatform\Core\Bridge\RamseyUuid\Serializer\UuidDenormalizer;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set('api_platform.identifier.uuid_normalizer', UuidNormalizer::class)
            ->tag('api_platform.identifier.denormalizer')
        ->set('api_platform.serializer.uuid_denormalizer', UuidDenormalizer::class)
            ->tag('serializer.normalizer')
    ;
};
