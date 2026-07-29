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

use ApiPlatform\Elasticsearch\Esql\Extension\PaginationExtension as EsqlPaginationExtension;
use ApiPlatform\Elasticsearch\Esql\Extension\ParameterExtension as EsqlParameterExtension;
use ApiPlatform\Elasticsearch\Esql\Extension\SortExtension as EsqlSortExtension;
use ApiPlatform\Elasticsearch\Esql\State\CollectionProvider as EsqlCollectionProvider;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('api_platform.elasticsearch.esql_extension.parameter', EsqlParameterExtension::class)
        ->args([
            service('api_platform.filter_locator'),
            service('api_platform.metadata.property.metadata_factory'),
            service('api_platform.resource_class_resolver'),
            service('api_platform.elasticsearch.name_converter.inner_fields'),
        ])
        ->tag('api_platform.elasticsearch.esql_extension.collection', ['priority' => 30]);

    $services->set('api_platform.elasticsearch.esql_extension.sort', EsqlSortExtension::class)
        ->args([
            service('api_platform.elasticsearch.name_converter.inner_fields'),
            '%api_platform.collection.order%',
        ])
        ->tag('api_platform.elasticsearch.esql_extension.collection', ['priority' => 20]);

    $services->set('api_platform.elasticsearch.esql_extension.pagination', EsqlPaginationExtension::class)
        ->args([
            service('api_platform.elasticsearch.client'),
            service('serializer'),
            service('api_platform.pagination'),
        ])
        ->tag('api_platform.elasticsearch.esql_extension.collection', ['priority' => 10]);

    $services->set('api_platform.elasticsearch.esql.state.collection_provider', EsqlCollectionProvider::class)
        ->args([
            service('api_platform.elasticsearch.client'),
            service('serializer'),
            tagged_iterator('api_platform.elasticsearch.esql_extension.collection'),
            service('api_platform.inflector')->nullOnInvalid(),
        ])
        ->tag('api_platform.state_provider', ['priority' => -100, 'key' => 'ApiPlatform\Elasticsearch\Esql\State\CollectionProvider'])
        ->tag('api_platform.state_provider', ['priority' => -100]);

    $services->alias(EsqlCollectionProvider::class, 'api_platform.elasticsearch.esql.state.collection_provider');
};
