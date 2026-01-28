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

use ApiPlatform\Metadata\Property\Factory\AttributePropertyMetadataFactory;
use ApiPlatform\Metadata\Property\Factory\CachedPropertyMetadataFactory;
use ApiPlatform\Metadata\Property\Factory\DefaultPropertyMetadataFactory;
use ApiPlatform\Metadata\Property\Factory\ExtractorPropertyMetadataFactory;
use ApiPlatform\Metadata\Property\Factory\IdentifierPropertyMetadataFactory;
use ApiPlatform\Metadata\Property\Factory\PropertyInfoPropertyMetadataFactory;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\SerializerPropertyMetadataFactory;

return function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->alias('api_platform.metadata.property.metadata_factory', 'api_platform.metadata.property.metadata_factory.xml');

    $services->alias(PropertyMetadataFactoryInterface::class, 'api_platform.metadata.property.metadata_factory');

    $services->set('api_platform.metadata.property.metadata_factory.property_info', PropertyInfoPropertyMetadataFactory::class)
        ->decorate('api_platform.metadata.property.metadata_factory', null, 40)
        ->args([
            service('api_platform.property_info'),
            service('api_platform.metadata.property.metadata_factory.property_info.inner'),
        ]);

    $services->set('api_platform.metadata.property.metadata_factory.attribute', AttributePropertyMetadataFactory::class)
        ->decorate('api_platform.metadata.property.metadata_factory', null, 35)
        ->args([service('api_platform.metadata.property.metadata_factory.attribute.inner')]);

    $services->set('api_platform.metadata.property.metadata_factory.serializer', SerializerPropertyMetadataFactory::class)
        ->decorate('api_platform.metadata.property.metadata_factory', null, 30)
        ->args([
            service('serializer.mapping.class_metadata_factory'),
            service('api_platform.metadata.property.metadata_factory.serializer.inner'),
            service('api_platform.resource_class_resolver'),
        ]);

    $services->set('api_platform.metadata.property.metadata_factory.cached', CachedPropertyMetadataFactory::class)
        ->decorate('api_platform.metadata.property.metadata_factory', null, -10)
        ->args([
            service('api_platform.cache.metadata.property'),
            service('api_platform.metadata.property.metadata_factory.cached.inner'),
        ]);

    $services->set('api_platform.metadata.property.metadata_factory.default_property', DefaultPropertyMetadataFactory::class)
        ->decorate('api_platform.metadata.property.metadata_factory', null, 30)
        ->args([service('api_platform.metadata.property.metadata_factory.default_property.inner')]);

    $services->set('api_platform.metadata.property.metadata_factory.identifier', IdentifierPropertyMetadataFactory::class)
        ->decorate('api_platform.metadata.property.metadata_factory', null, 30)
        ->args([
            service('api_platform.resource_class_resolver'),
            service('api_platform.metadata.property.metadata_factory.identifier.inner'),
        ]);

    $services->set('api_platform.metadata.property.metadata_factory.xml', ExtractorPropertyMetadataFactory::class)
        ->args([service('api_platform.metadata.property_extractor.xml')]);

    $services->set('api_platform.cache.metadata.property')
        ->parent('cache.system')
        ->tag('cache.pool');
};
