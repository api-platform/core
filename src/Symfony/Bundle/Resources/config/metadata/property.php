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

    $services->alias('api_platform.metadata.property.metadata_factory', 'api_platform.metadata.property.metadata_factory.xml');

    $services->alias('ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface', 'api_platform.metadata.property.metadata_factory');

    $services->set('api_platform.metadata.property.metadata_factory.property_info', 'ApiPlatform\Metadata\Property\Factory\PropertyInfoPropertyMetadataFactory')
        ->decorate('api_platform.metadata.property.metadata_factory', null, 40)
        ->args([
            service('api_platform.property_info'),
            service('api_platform.metadata.property.metadata_factory.property_info.inner'),
        ]);

    $services->set('api_platform.metadata.property.metadata_factory.attribute', 'ApiPlatform\Metadata\Property\Factory\AttributePropertyMetadataFactory')
        ->decorate('api_platform.metadata.property.metadata_factory', null, 35)
        ->args([service('api_platform.metadata.property.metadata_factory.attribute.inner')]);

    $services->set('api_platform.metadata.property.metadata_factory.serializer', 'ApiPlatform\Metadata\Property\Factory\SerializerPropertyMetadataFactory')
        ->decorate('api_platform.metadata.property.metadata_factory', null, 30)
        ->args([
            service('serializer.mapping.class_metadata_factory'),
            service('api_platform.metadata.property.metadata_factory.serializer.inner'),
            service('api_platform.resource_class_resolver'),
        ]);

    $services->set('api_platform.metadata.property.metadata_factory.cached', 'ApiPlatform\Metadata\Property\Factory\CachedPropertyMetadataFactory')
        ->decorate('api_platform.metadata.property.metadata_factory', null, -10)
        ->args([
            service('api_platform.cache.metadata.property'),
            service('api_platform.metadata.property.metadata_factory.cached.inner'),
        ]);

    $services->set('api_platform.metadata.property.metadata_factory.default_property', 'ApiPlatform\Metadata\Property\Factory\DefaultPropertyMetadataFactory')
        ->decorate('api_platform.metadata.property.metadata_factory', null, 30)
        ->args([service('api_platform.metadata.property.metadata_factory.default_property.inner')]);

    $services->set('api_platform.metadata.property.metadata_factory.identifier', 'ApiPlatform\Metadata\Property\Factory\IdentifierPropertyMetadataFactory')
        ->decorate('api_platform.metadata.property.metadata_factory', null, 30)
        ->args([
            service('api_platform.resource_class_resolver'),
            service('api_platform.metadata.property.metadata_factory.identifier.inner'),
        ]);

    $services->set('api_platform.metadata.property.metadata_factory.xml', 'ApiPlatform\Metadata\Property\Factory\ExtractorPropertyMetadataFactory')
        ->args([service('api_platform.metadata.property_extractor.xml')]);

    $services->set('api_platform.cache.metadata.property')
        ->parent('cache.system')
        ->tag('cache.pool');
};
