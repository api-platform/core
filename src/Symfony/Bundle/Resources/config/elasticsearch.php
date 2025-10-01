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

    $services->set('api_platform.elasticsearch.name_converter.inner_fields', 'ApiPlatform\Elasticsearch\Serializer\NameConverter\InnerFieldsNameConverter')
        ->args([service('api_platform.name_converter')->ignoreOnInvalid()]);

    $services->set('api_platform.elasticsearch.normalizer.item', 'ApiPlatform\Elasticsearch\Serializer\ItemNormalizer')
        ->decorate('api_platform.serializer.normalizer.item', null, 0)
        ->args([service('api_platform.elasticsearch.normalizer.item.inner')]);

    $services->set('api_platform.elasticsearch.normalizer.document', 'ApiPlatform\Elasticsearch\Serializer\DocumentNormalizer')
        ->args([
            service('api_platform.metadata.resource.metadata_collection_factory'),
            service('serializer.mapping.class_metadata_factory'),
            service('api_platform.elasticsearch.name_converter.inner_fields'),
            service('serializer.property_accessor'),
            service('property_info')->ignoreOnInvalid(),
            service('serializer.mapping.class_discriminator_resolver')->ignoreOnInvalid(),
            null,
            '%api_platform.serializer.default_context%',
        ])
        ->tag('serializer.normalizer', ['priority' => -922]);

    $services->set('api_platform.elasticsearch.request_body_search_extension.filter')
        ->abstract()
        ->args([service('api_platform.filter_locator')]);

    $services->set('api_platform.elasticsearch.request_body_search_extension.constant_score_filter', 'ApiPlatform\Elasticsearch\Extension\ConstantScoreFilterExtension')
        ->parent('api_platform.elasticsearch.request_body_search_extension.filter')
        ->tag('api_platform.elasticsearch.request_body_search_extension.collection', ['priority' => 30]);

    $services->set('api_platform.elasticsearch.request_body_search_extension.sort_filter', 'ApiPlatform\Elasticsearch\Extension\SortFilterExtension')
        ->parent('api_platform.elasticsearch.request_body_search_extension.filter')
        ->tag('api_platform.elasticsearch.request_body_search_extension.collection', ['priority' => 20]);

    $services->set('api_platform.elasticsearch.request_body_search_extension.sort', 'ApiPlatform\Elasticsearch\Extension\SortExtension')
        ->args([
            service('api_platform.metadata.property.metadata_factory'),
            service('api_platform.resource_class_resolver'),
            service('api_platform.elasticsearch.name_converter.inner_fields'),
            '%api_platform.collection.order%',
        ])
        ->tag('api_platform.elasticsearch.request_body_search_extension.collection', ['priority' => 10]);

    $services->set('api_platform.elasticsearch.search_filter')
        ->abstract()
        ->args([
            service('api_platform.metadata.property.name_collection_factory'),
            service('api_platform.metadata.property.metadata_factory'),
            service('api_platform.resource_class_resolver'),
            service('api_platform.iri_converter'),
            service('api_platform.property_accessor'),
            service('api_platform.elasticsearch.name_converter.inner_fields'),
        ]);

    $services->set('api_platform.elasticsearch.term_filter', 'ApiPlatform\Elasticsearch\Filter\TermFilter')
        ->abstract()
        ->parent('api_platform.elasticsearch.search_filter');

    $services->alias('ApiPlatform\Elasticsearch\Filter\TermFilter', 'api_platform.elasticsearch.term_filter');

    $services->set('api_platform.elasticsearch.match_filter', 'ApiPlatform\Elasticsearch\Filter\MatchFilter')
        ->abstract()
        ->parent('api_platform.elasticsearch.search_filter');

    $services->alias('ApiPlatform\Elasticsearch\Filter\MatchFilter', 'api_platform.elasticsearch.match_filter');

    $services->set('api_platform.elasticsearch.order_filter', 'ApiPlatform\Elasticsearch\Filter\OrderFilter')
        ->abstract()
        ->args([
            service('api_platform.metadata.property.name_collection_factory'),
            service('api_platform.metadata.property.metadata_factory'),
            service('api_platform.resource_class_resolver'),
            service('api_platform.elasticsearch.name_converter.inner_fields'),
            '%api_platform.collection.order_parameter_name%',
        ]);

    $services->alias('ApiPlatform\Elasticsearch\Filter\OrderFilter', 'api_platform.elasticsearch.order_filter');

    $services->set('api_platform.elasticsearch.state.item_provider', 'ApiPlatform\Elasticsearch\State\ItemProvider')
        ->args([
            service('api_platform.elasticsearch.client'),
            service('serializer'),
            service('api_platform.inflector')->nullOnInvalid(),
        ])
        ->tag('api_platform.state_provider', ['priority' => -100, 'key' => 'ApiPlatform\Elasticsearch\State\ItemProvider'])
        ->tag('api_platform.state_provider', ['priority' => -100]);

    $services->alias('ApiPlatform\Elasticsearch\State\ItemProvider', 'api_platform.elasticsearch.state.item_provider');

    $services->set('api_platform.elasticsearch.state.collection_provider', 'ApiPlatform\Elasticsearch\State\CollectionProvider')
        ->args([
            service('api_platform.elasticsearch.client'),
            service('serializer'),
            service('api_platform.pagination'),
            tagged_iterator('api_platform.elasticsearch.request_body_search_extension.collection'),
            service('api_platform.inflector')->nullOnInvalid(),
        ])
        ->tag('api_platform.state_provider', ['priority' => -100, 'key' => 'ApiPlatform\Elasticsearch\State\CollectionProvider'])
        ->tag('api_platform.state_provider', ['priority' => -100]);

    $services->alias('ApiPlatform\Elasticsearch\State\CollectionProvider', 'api_platform.elasticsearch.state.collection_provider');

    $services->set('api_platform.elasticsearch.metadata.resource.metadata_collection_factory', 'ApiPlatform\Elasticsearch\Metadata\Resource\Factory\ElasticsearchProviderResourceMetadataCollectionFactory')
        ->decorate('api_platform.metadata.resource.metadata_collection_factory', null, 40)
        ->args([service('api_platform.elasticsearch.metadata.resource.metadata_collection_factory.inner')]);
};
