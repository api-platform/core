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

return function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('api_platform.metadata.resource_extractor.yaml', 'ApiPlatform\Metadata\Extractor\YamlResourceExtractor')
        ->args([
            [],
            service('service_container'),
        ]);

    $services->set('api_platform.metadata.property_extractor.yaml', 'ApiPlatform\Metadata\Extractor\YamlPropertyExtractor')
        ->args([
            [],
            service('service_container'),
        ]);

    $services->set('api_platform.metadata.resource.name_collection_factory.yaml', 'ApiPlatform\Metadata\Resource\Factory\ExtractorResourceNameCollectionFactory')
        ->decorate('api_platform.metadata.resource.name_collection_factory', null, 0)
        ->args([
            service('api_platform.metadata.resource_extractor.yaml'),
            service('api_platform.metadata.resource.name_collection_factory.yaml.inner'),
        ]);

    $services->set('api_platform.metadata.resource.metadata_collection_factory.yaml', 'ApiPlatform\Metadata\Resource\Factory\ExtractorResourceMetadataCollectionFactory')
        ->decorate('api_platform.metadata.resource.metadata_collection_factory', null, 800)
        ->args([
            service('api_platform.metadata.resource_extractor.yaml'),
            service('api_platform.metadata.resource.metadata_collection_factory.yaml.inner'),
            '%api_platform.defaults%',
            service('logger')->nullOnInvalid(),
            '%api_platform.graphql.enabled%',
        ]);

    $services->set('api_platform.metadata.property.metadata_factory.yaml', 'ApiPlatform\Metadata\Property\Factory\ExtractorPropertyMetadataFactory')
        ->decorate('api_platform.metadata.property.metadata_factory', null, 20)
        ->args([
            service('api_platform.metadata.property_extractor.yaml'),
            service('api_platform.metadata.property.metadata_factory.yaml.inner'),
        ]);

    $services->set('api_platform.metadata.property.name_collection_factory.yaml', 'ApiPlatform\Metadata\Property\Factory\ExtractorPropertyNameCollectionFactory')
        ->decorate('api_platform.metadata.property.name_collection_factory', null, 0)
        ->args([
            service('api_platform.metadata.property_extractor.yaml'),
            service('api_platform.metadata.property.name_collection_factory.yaml.inner'),
        ]);
};
