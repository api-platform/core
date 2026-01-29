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

use ApiPlatform\Metadata\Extractor\YamlPropertyExtractor;
use ApiPlatform\Metadata\Extractor\YamlResourceExtractor;
use ApiPlatform\Metadata\Property\Factory\ExtractorPropertyMetadataFactory;
use ApiPlatform\Metadata\Property\Factory\ExtractorPropertyNameCollectionFactory;
use ApiPlatform\Metadata\Resource\Factory\ExtractorResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\Resource\Factory\ExtractorResourceNameCollectionFactory;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('api_platform.metadata.resource_extractor.yaml', YamlResourceExtractor::class)
        ->args([
            [],
            service('service_container'),
        ]);

    $services->set('api_platform.metadata.property_extractor.yaml', YamlPropertyExtractor::class)
        ->args([
            [],
            service('service_container'),
        ]);

    $services->set('api_platform.metadata.resource.name_collection_factory.yaml', ExtractorResourceNameCollectionFactory::class)
        ->decorate('api_platform.metadata.resource.name_collection_factory', null, 0)
        ->args([
            service('api_platform.metadata.resource_extractor.yaml'),
            service('api_platform.metadata.resource.name_collection_factory.yaml.inner'),
        ]);

    $services->set('api_platform.metadata.resource.metadata_collection_factory.yaml', ExtractorResourceMetadataCollectionFactory::class)
        ->decorate('api_platform.metadata.resource.metadata_collection_factory', null, 800)
        ->args([
            service('api_platform.metadata.resource_extractor.yaml'),
            service('api_platform.metadata.resource.metadata_collection_factory.yaml.inner'),
            '%api_platform.defaults%',
            service('logger')->nullOnInvalid(),
            '%api_platform.graphql.enabled%',
        ]);

    $services->set('api_platform.metadata.property.metadata_factory.yaml', ExtractorPropertyMetadataFactory::class)
        ->decorate('api_platform.metadata.property.metadata_factory', null, 20)
        ->args([
            service('api_platform.metadata.property_extractor.yaml'),
            service('api_platform.metadata.property.metadata_factory.yaml.inner'),
        ]);

    $services->set('api_platform.metadata.property.name_collection_factory.yaml', ExtractorPropertyNameCollectionFactory::class)
        ->decorate('api_platform.metadata.property.name_collection_factory', null, 0)
        ->args([
            service('api_platform.metadata.property_extractor.yaml'),
            service('api_platform.metadata.property.name_collection_factory.yaml.inner'),
        ]);
};
