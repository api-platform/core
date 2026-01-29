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

use ApiPlatform\Elasticsearch\Extension\ConstantScoreFilterExtension;
use ApiPlatform\Elasticsearch\Extension\SortExtension;
use ApiPlatform\Elasticsearch\Extension\SortFilterExtension;
use ApiPlatform\Elasticsearch\Filter\MatchFilter;
use ApiPlatform\Elasticsearch\Filter\OrderFilter;
use ApiPlatform\Elasticsearch\Filter\TermFilter;
use ApiPlatform\Elasticsearch\Metadata\Resource\Factory\ElasticsearchProviderResourceMetadataCollectionFactory;
use ApiPlatform\Elasticsearch\Serializer\DocumentNormalizer;
use ApiPlatform\Elasticsearch\Serializer\ItemNormalizer;
use ApiPlatform\Elasticsearch\Serializer\NameConverter\InnerFieldsNameConverter;
use ApiPlatform\Elasticsearch\State\CollectionProvider;
use ApiPlatform\Elasticsearch\State\ItemProvider;

return function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('api_platform.elasticsearch.name_converter.inner_fields', InnerFieldsNameConverter::class)
        ->args([service('api_platform.name_converter')->ignoreOnInvalid()]);

    $services->set('api_platform.elasticsearch.normalizer.item', ItemNormalizer::class)
        ->decorate('api_platform.serializer.normalizer.item', null, 0)
        ->args([service('api_platform.elasticsearch.normalizer.item.inner')]);

    $services->set('api_platform.elasticsearch.normalizer.document', DocumentNormalizer::class)
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

    $services->set('api_platform.elasticsearch.request_body_search_extension.constant_score_filter', ConstantScoreFilterExtension::class)
        ->parent('api_platform.elasticsearch.request_body_search_extension.filter')
        ->tag('api_platform.elasticsearch.request_body_search_extension.collection', ['priority' => 30]);

    $services->set('api_platform.elasticsearch.request_body_search_extension.sort_filter', SortFilterExtension::class)
        ->parent('api_platform.elasticsearch.request_body_search_extension.filter')
        ->tag('api_platform.elasticsearch.request_body_search_extension.collection', ['priority' => 20]);

    $services->set('api_platform.elasticsearch.request_body_search_extension.sort', SortExtension::class)
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

    $services->set('api_platform.elasticsearch.term_filter', TermFilter::class)
        ->abstract()
        ->parent('api_platform.elasticsearch.search_filter');

    $services->alias(TermFilter::class, 'api_platform.elasticsearch.term_filter');

    $services->set('api_platform.elasticsearch.match_filter', MatchFilter::class)
        ->abstract()
        ->parent('api_platform.elasticsearch.search_filter');

    $services->alias(MatchFilter::class, 'api_platform.elasticsearch.match_filter');

    $services->set('api_platform.elasticsearch.order_filter', OrderFilter::class)
        ->abstract()
        ->args([
            service('api_platform.metadata.property.name_collection_factory'),
            service('api_platform.metadata.property.metadata_factory'),
            service('api_platform.resource_class_resolver'),
            service('api_platform.elasticsearch.name_converter.inner_fields'),
            '%api_platform.collection.order_parameter_name%',
        ]);

    $services->alias(OrderFilter::class, 'api_platform.elasticsearch.order_filter');

    $services->set('api_platform.elasticsearch.state.item_provider', ItemProvider::class)
        ->args([
            service('api_platform.elasticsearch.client'),
            service('serializer'),
            service('api_platform.inflector')->nullOnInvalid(),
        ])
        ->tag('api_platform.state_provider', ['priority' => -100, 'key' => 'ApiPlatform\Elasticsearch\State\ItemProvider'])
        ->tag('api_platform.state_provider', ['priority' => -100]);

    $services->alias(ItemProvider::class, 'api_platform.elasticsearch.state.item_provider');

    $services->set('api_platform.elasticsearch.state.collection_provider', CollectionProvider::class)
        ->args([
            service('api_platform.elasticsearch.client'),
            service('serializer'),
            service('api_platform.pagination'),
            tagged_iterator('api_platform.elasticsearch.request_body_search_extension.collection'),
            service('api_platform.inflector')->nullOnInvalid(),
        ])
        ->tag('api_platform.state_provider', ['priority' => -100, 'key' => 'ApiPlatform\Elasticsearch\State\CollectionProvider'])
        ->tag('api_platform.state_provider', ['priority' => -100]);

    $services->alias(CollectionProvider::class, 'api_platform.elasticsearch.state.collection_provider');

    $services->set('api_platform.elasticsearch.metadata.resource.metadata_collection_factory', ElasticsearchProviderResourceMetadataCollectionFactory::class)
        ->decorate('api_platform.metadata.resource.metadata_collection_factory', null, 40)
        ->args([service('api_platform.elasticsearch.metadata.resource.metadata_collection_factory.inner')]);
};
