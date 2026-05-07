<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    // Toon encoder - can be used with any representation format (JSON-LD, JSON:API, HAL, Hydra)
    // Priority: 10 to ensure it's checked before JsonEncoder for jsonld format
    $services->set('api_platform.toon.encoder', 'ApiPlatform\Toon\Serializer\ToonEncoder')
        ->tag('serializer.encoder', ['priority' => 10]);

    // Toon Normalizers

    $services->set('api_platform.toon.normalizer.hydra.collection', 'ApiPlatform\Toon\Serializer\ToonHydraCollectionNormalizer')
        ->decorate('api_platform.hydra.normalizer.collection', null, 0)
        ->args([
            service('.inner'),
        ])
        ->tag('serializer.normalizer', ['priority' => 8]);

    $services->set('api_platform.toon.normalizer.hydra.entrypoint', 'ApiPlatform\Toon\Serializer\ToonHydraEntrypointNormalizer')
        ->decorate('api_platform.hydra.normalizer.entrypoint')
        ->args([
            service('.inner'),
            service('api_platform.metadata.resource.metadata_collection_factory')
        ])
        ->tag('serializer.normalizer', ['priority' => 8]);

    $services->set('api_platform.toon.normalizer.jsonapi.collection', 'ApiPlatform\Toon\Serializer\ToonJsonApiCollectionNormalizer')
        ->decorate('api_platform.jsonapi.normalizer.collection', null, 0)
        ->args([service('.inner')])
        ->tag('serializer.normalizer', ['priority' => 8]);

    $services->set('api_platform.toon.normalizer.jsonapi.entrypoint', 'ApiPlatform\Toon\Serializer\ToonJsonApiEntrypointNormalizer')
        ->decorate('api_platform.jsonapi.normalizer.entrypoint')
        ->args([service('.inner')])
        ->tag('serializer.normalizer', ['priority' => 8]);
    $services->set('api_platform.toon.normalizer.jsonapi.item', 'ApiPlatform\Toon\Serializer\ToonJsonApiItemNormalizer')
        ->decorate('api_platform.jsonapi.normalizer.item')
        ->args([service('.inner')])
        ->tag('serializer.normalizer', ['priority' => 8]);
    $services->set('api_platform.toon.normalizer.jsonld.item', 'ApiPlatform\Toon\Serializer\ToonJsonLdItemNormalizer')
        ->decorate('api_platform.jsonld.normalizer.item')
        ->args([service('.inner')])
        ->tag('serializer.normalizer', ['priority' => 8]);
};
