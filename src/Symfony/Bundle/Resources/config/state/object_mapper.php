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

    $services->set('api_platform.object_mapper.metadata_factory', 'Symfony\Component\ObjectMapper\Metadata\ReflectionObjectMapperMetadataFactory');

    $services->set('api_platform.object_mapper', 'Symfony\Component\ObjectMapper\ObjectMapper')
        ->args([
            service('api_platform.object_mapper.metadata_factory'),
            service('property_accessor')->nullOnInvalid(),
            tagged_locator('object_mapper.transform_callable'),
            tagged_locator('object_mapper.condition_callable'),
        ]);

    $services->set('api_platform.object_mapper.relation', 'ApiPlatform\State\ObjectMapper\ObjectMapper')
        ->decorate('api_platform.object_mapper', null, -255)
        ->args([service('api_platform.object_mapper.relation.inner')]);

    $services->set('api_platform.state_provider.object_mapper', 'ApiPlatform\State\Provider\ObjectMapperProvider')
        ->decorate('api_platform.state_provider.locator', null, 0)
        ->args([
            service('api_platform.object_mapper')->nullOnInvalid(),
            service('api_platform.state_provider.object_mapper.inner'),
            service('api_platform.object_mapper.metadata_factory'),
        ]);

    $services->set('api_platform.state_processor.object_mapper', 'ApiPlatform\State\Processor\ObjectMapperProcessor')
        ->decorate('api_platform.state_processor.locator', null, 0)
        ->args([
            service('api_platform.object_mapper')->nullOnInvalid(),
            service('api_platform.state_processor.object_mapper.inner'),
            service('api_platform.object_mapper.metadata_factory'),
        ]);

    $services->set('api_platform.metadata.resource.metadata_collection_factory.object_mapper', 'ApiPlatform\Metadata\Resource\Factory\ObjectMapperMetadataCollectionFactory')
        ->decorate('api_platform.metadata.resource.metadata_collection_factory', null, 100)
        ->args([
            service('api_platform.metadata.resource.metadata_collection_factory.object_mapper.inner'),
            service('api_platform.object_mapper.metadata_factory'),
        ]);
};
