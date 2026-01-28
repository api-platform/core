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

use ApiPlatform\Metadata\Property\Factory\CachedPropertyNameCollectionFactory;
use ApiPlatform\Metadata\Property\Factory\ConcernsPropertyNameCollectionMetadataFactory;
use ApiPlatform\Metadata\Property\Factory\ExtractorPropertyNameCollectionFactory;
use ApiPlatform\Metadata\Property\Factory\PropertyInfoPropertyNameCollectionFactory;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->alias('api_platform.metadata.property.name_collection_factory', 'api_platform.metadata.property.name_collection_factory.property_info');

    $services->alias(PropertyNameCollectionFactoryInterface::class, 'api_platform.metadata.property.name_collection_factory');

    $services->set('api_platform.metadata.property.name_collection_factory.property_info', PropertyInfoPropertyNameCollectionFactory::class)
        ->args([service('api_platform.property_info')]);

    $services->set('api_platform.metadata.property.name_collection_factory.cached', CachedPropertyNameCollectionFactory::class)
        ->decorate('api_platform.metadata.property.name_collection_factory', null, -10)
        ->args([
            service('api_platform.cache.metadata.property'),
            service('api_platform.metadata.property.name_collection_factory.cached.inner'),
        ]);

    $services->set('api_platform.metadata.property.name_collection_factory.xml', ExtractorPropertyNameCollectionFactory::class)
        ->decorate('api_platform.metadata.property.name_collection_factory', null, 0)
        ->args([
            service('api_platform.metadata.property_extractor.xml'),
            service('api_platform.metadata.property.name_collection_factory.xml.inner'),
        ]);

    $services->set('api_platform.metadata.property.name_collection_factory.concerns', ConcernsPropertyNameCollectionMetadataFactory::class)
        ->decorate('api_platform.metadata.property.name_collection_factory', null, 0)
        ->args([service('api_platform.metadata.property.name_collection_factory.concerns.inner')]);
};
