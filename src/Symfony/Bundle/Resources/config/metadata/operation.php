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

use ApiPlatform\Metadata\Operation\Factory\CacheOperationMetadataFactory;
use ApiPlatform\Metadata\Operation\Factory\OperationMetadataFactory;
use ApiPlatform\Metadata\Operation\Factory\OperationMetadataFactoryInterface;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('api_platform.metadata.operation.metadata_factory', OperationMetadataFactory::class)
        ->args([
            service('api_platform.metadata.resource.name_collection_factory'),
            service('api_platform.metadata.resource.metadata_collection_factory'),
        ]);

    $services->alias(OperationMetadataFactoryInterface::class, 'api_platform.metadata.operation.metadata_factory');

    $services->set('api_platform.metadata.operation.metadata_factory.cached', CacheOperationMetadataFactory::class)
        ->decorate('api_platform.metadata.operation.metadata_factory', null, -10)
        ->args([
            service('api_platform.cache.metadata.operation'),
            service('api_platform.metadata.operation.metadata_factory.cached.inner'),
        ]);

    $services->set('api_platform.cache.metadata.operation')
        ->parent('cache.system')
        ->tag('cache.pool');
};
