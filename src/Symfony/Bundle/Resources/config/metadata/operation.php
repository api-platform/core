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

    $services->set('api_platform.metadata.operation.metadata_factory', 'ApiPlatform\Metadata\Operation\Factory\OperationMetadataFactory')
        ->args([
            service('api_platform.metadata.resource.name_collection_factory'),
            service('api_platform.metadata.resource.metadata_collection_factory'),
        ]);

    $services->alias('ApiPlatform\Metadata\Operation\Factory\OperationMetadataFactoryInterface', 'api_platform.metadata.operation.metadata_factory');
};
