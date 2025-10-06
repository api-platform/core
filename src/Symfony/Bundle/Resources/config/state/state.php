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

    $services->set('api_platform.state_provider.locator', 'ApiPlatform\State\CallableProvider')
        ->args([tagged_locator('api_platform.state_provider', 'key')]);

    $services->alias('api_platform.state_provider', 'api_platform.state_provider.locator');

    $services->set('api_platform.state_processor.locator', 'ApiPlatform\State\CallableProcessor')
        ->args([tagged_locator('api_platform.state_processor', 'key')]);

    $services->set('api_platform.pagination', 'ApiPlatform\State\Pagination\Pagination')
        ->args([
            '%api_platform.collection.pagination%',
            '%api_platform.graphql.collection.pagination%',
        ]);

    $services->alias('ApiPlatform\State\Pagination\Pagination', 'api_platform.pagination');

    $services->set('api_platform.serializer_locator', 'Symfony\Component\DependencyInjection\ServiceLocator')
        ->args([['serializer' => service('api_platform.serializer')]])
        ->tag('container.service_locator');

    $services->set('api_platform.pagination_options', 'ApiPlatform\State\Pagination\PaginationOptions')
        ->args([
            '%api_platform.collection.pagination.enabled%',
            '%api_platform.collection.pagination.page_parameter_name%',
            '%api_platform.collection.pagination.client_items_per_page%',
            '%api_platform.collection.pagination.items_per_page_parameter_name%',
            '%api_platform.collection.pagination.client_enabled%',
            '%api_platform.collection.pagination.enabled_parameter_name%',
            '%api_platform.collection.pagination.items_per_page%',
            '%api_platform.collection.pagination.maximum_items_per_page%',
            '%api_platform.collection.pagination.partial%',
            '%api_platform.collection.pagination.client_partial%',
            '%api_platform.collection.pagination.partial_parameter_name%',
        ]);

    $services->alias('ApiPlatform\State\Pagination\PaginationOptions', 'api_platform.pagination_options');

    $services->set('api_platform.state_provider.create', 'ApiPlatform\State\CreateProvider')
        ->args([
            service('api_platform.state_provider.locator'),
            service('api_platform.metadata.resource.metadata_collection_factory'),
        ])
        ->tag('api_platform.state_provider', ['key' => 'ApiPlatform\State\CreateProvider'])
        ->tag('api_platform.state_provider', ['key' => 'api_platform.state_provider.create']);

    $services->alias('ApiPlatform\State\CreateProvider', 'api_platform.state_provider.create');

    $services->set('api_platform.state_provider.object', 'ApiPlatform\State\ObjectProvider')
        ->tag('api_platform.state_provider', ['key' => 'ApiPlatform\State\ObjectProvider'])
        ->tag('api_platform.state_provider', ['key' => 'api_platform.state_provider.object']);

    $services->alias('ApiPlatform\State\ObjectProvider', 'api_platform.state_provider.object');

    $services->alias('ApiPlatform\State\SerializerContextBuilderInterface', 'api_platform.serializer.context_builder');

    $services->set('api_platform.state_provider.backed_enum', 'ApiPlatform\State\Provider\BackedEnumProvider')
        ->tag('api_platform.state_provider', ['key' => 'ApiPlatform\State\Provider\BackedEnumProvider'])
        ->tag('api_platform.state_provider', ['key' => 'api_platform.state_provider.backed_enum']);
};
