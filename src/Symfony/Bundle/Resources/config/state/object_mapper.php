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

use ApiPlatform\Metadata\Resource\Factory\ObjectMapperMetadataCollectionFactory;
use ApiPlatform\State\ObjectMapper\ObjectMapper;
use ApiPlatform\State\Processor\ObjectMapperProcessor;
use ApiPlatform\State\Provider\ObjectMapperProvider;
use Symfony\Component\ObjectMapper\Metadata\ReflectionObjectMapperMetadataFactory;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('api_platform.object_mapper.metadata_factory', ReflectionObjectMapperMetadataFactory::class);

    $services->alias('api_platform.object_mapper', 'object_mapper');

    $services->set('api_platform.object_mapper.relation', ObjectMapper::class)
        ->decorate('api_platform.object_mapper', null, -255)
        ->args([service('api_platform.object_mapper.relation.inner')]);

    $services->set('api_platform.state_provider.object_mapper', ObjectMapperProvider::class)
        ->decorate('api_platform.state_provider.locator', null, 0)
        ->args([
            service('api_platform.object_mapper')->nullOnInvalid(),
            service('api_platform.state_provider.object_mapper.inner'),
            service('api_platform.object_mapper.metadata_factory'),
        ]);

    $services->set('api_platform.state_processor.object_mapper', ObjectMapperProcessor::class)
        ->decorate('api_platform.state_processor.locator', null, 0)
        ->args([
            service('api_platform.object_mapper')->nullOnInvalid(),
            service('api_platform.state_processor.object_mapper.inner'),
            service('api_platform.object_mapper.metadata_factory'),
        ]);

    $services->set('api_platform.metadata.resource.metadata_collection_factory.object_mapper', ObjectMapperMetadataCollectionFactory::class)
        ->decorate('api_platform.metadata.resource.metadata_collection_factory', null, 100)
        ->args([
            service('api_platform.metadata.resource.metadata_collection_factory.object_mapper.inner'),
            service('api_platform.object_mapper.metadata_factory'),
        ]);
};
