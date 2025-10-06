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

    $services->set('api_platform.cache.metadata.resource')
        ->parent('cache.system')
        ->tag('cache.pool');

    $services->set('api_platform.metadata.resource.name_collection_factory.cached', 'ApiPlatform\Metadata\Resource\Factory\CachedResourceNameCollectionFactory')
        ->decorate('api_platform.metadata.resource.name_collection_factory', null, -10)
        ->args([
            service('api_platform.cache.metadata.resource'),
            service('api_platform.metadata.resource.name_collection_factory.cached.inner'),
        ]);

    $services->alias('api_platform.metadata.resource.name_collection_factory', 'api_platform.metadata.resource.name_collection_factory.xml');

    $services->set('api_platform.metadata.resource.name_collection_factory.xml', 'ApiPlatform\Metadata\Resource\Factory\ExtractorResourceNameCollectionFactory')
        ->args([service('api_platform.metadata.resource_extractor.xml')]);

    $services->set('api_platform.metadata.resource.name_collection_factory.php_file', 'ApiPlatform\Metadata\Resource\Factory\PhpFileResourceNameCollectionFactory')
        ->decorate('api_platform.metadata.resource.name_collection_factory', null, 900)
        ->args([
            service('api_platform.metadata.resource_extractor.php_file'),
            service('api_platform.metadata.resource.name_collection_factory.php_file.inner'),
        ]);

    $services->set('api_platform.metadata.resource.name_collection_factory.concerns', 'ApiPlatform\Metadata\Resource\Factory\ConcernsResourceNameCollectionFactory')
        ->decorate('api_platform.metadata.resource.name_collection_factory', null, 800)
        ->args([
            '%api_platform.resource_class_directories%',
            service('api_platform.metadata.resource.name_collection_factory.concerns.inner'),
        ]);

    $services->alias('ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface', 'api_platform.metadata.resource.name_collection_factory');

    $services->set('api_platform.metadata.resource.name_collection_factory.attributes', 'ApiPlatform\Metadata\Resource\Factory\AttributesResourceNameCollectionFactory')
        ->decorate('api_platform.metadata.resource.name_collection_factory', null, 0)
        ->args([
            '%api_platform.resource_class_directories%',
            service('api_platform.metadata.resource.name_collection_factory.attributes.inner'),
        ]);

    $services->set('api_platform.metadata.resource.name_collection_factory.class_name', 'ApiPlatform\Metadata\Resource\Factory\ClassNameResourceNameCollectionFactory')
        ->decorate('api_platform.metadata.resource.name_collection_factory', null, 0)
        ->args([
            '%api_platform.class_name_resources%',
            service('api_platform.metadata.resource.name_collection_factory.class_name.inner'),
        ]);
};
