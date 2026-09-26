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

use ApiPlatform\JsonApi\Filter\SparseFieldset;
use ApiPlatform\JsonApi\Filter\SparseFieldsetParameterProvider;
use ApiPlatform\JsonApi\State\JsonApiProvider;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('api_platform.jsonapi.state_provider', JsonApiProvider::class)
        ->decorate('api_platform.state_provider.read', null, 0)
        ->args([
            service('api_platform.jsonapi.state_provider.inner'),
            service('api_platform.metadata.property.name_collection_factory'),
            service('api_platform.metadata.property.metadata_factory'),
            service('api_platform.metadata.resource.metadata_collection_factory'),
            service('api_platform.jsonapi.resource_linkage_resolver'),
            '%api_platform.collection.order_parameter_name%',
        ]);

    $services->set('api_platform.jsonapi.parameter_provider.sparse_fieldset', SparseFieldsetParameterProvider::class)
        ->args([
            service('api_platform.metadata.resource.name_collection_factory'),
            service('api_platform.metadata.resource.metadata_collection_factory'),
            service('api_platform.metadata.property.name_collection_factory'),
            service('api_platform.metadata.property.metadata_factory'),
            service('api_platform.jsonapi.resource_linkage_resolver'),
        ])
        ->tag('api_platform.parameter_provider', ['key' => SparseFieldsetParameterProvider::class]);

    $services->set('api_platform.jsonapi.filter.sparse_fieldset', SparseFieldset::class)
        ->tag('api_platform.filter', ['id' => SparseFieldset::class]);
};
