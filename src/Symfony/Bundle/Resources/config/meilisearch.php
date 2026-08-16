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

use ApiPlatform\Meilisearch\Extension\FilterExtension;
use ApiPlatform\Meilisearch\Extension\SortExtension;
use ApiPlatform\Meilisearch\Filter\SearchFilter;
use ApiPlatform\Meilisearch\Filter\TermFilter;
use ApiPlatform\Meilisearch\Metadata\Resource\Factory\MeilisearchProviderResourceMetadataCollectionFactory;
use ApiPlatform\Meilisearch\State\CollectionProvider;
use ApiPlatform\Meilisearch\State\ItemProvider;

return function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('api_platform.meilisearch.request_parameters_extension.filter', FilterExtension::class)
        ->args([service('api_platform.filter_locator')])
        ->tag('api_platform.meilisearch.request_parameters_extension.collection', ['priority' => 20]);

    $services->set('api_platform.meilisearch.request_parameters_extension.sort', SortExtension::class)
        ->args(['%api_platform.collection.order%'])
        ->tag('api_platform.meilisearch.request_parameters_extension.collection', ['priority' => 10]);

    $services->set('api_platform.meilisearch.term_filter', TermFilter::class)
        ->abstract()
        ->tag('api_platform.meilisearch.filter');

    $services->alias(TermFilter::class, 'api_platform.meilisearch.term_filter');

    $services->set('api_platform.meilisearch.search_filter', SearchFilter::class)
        ->abstract()
        ->tag('api_platform.meilisearch.filter');

    $services->alias(SearchFilter::class, 'api_platform.meilisearch.search_filter');

    $services->set('api_platform.meilisearch.state.item_provider', ItemProvider::class)
        ->args([
            service('api_platform.meilisearch.client'),
            service('serializer'),
            service('api_platform.inflector')->nullOnInvalid(),
        ])
        ->tag('api_platform.state_provider', ['priority' => -100, 'key' => 'ApiPlatform\Meilisearch\State\ItemProvider'])
        ->tag('api_platform.state_provider', ['priority' => -100]);

    $services->alias(ItemProvider::class, 'api_platform.meilisearch.state.item_provider');

    $services->set('api_platform.meilisearch.state.collection_provider', CollectionProvider::class)
        ->args([
            service('api_platform.meilisearch.client'),
            service('serializer'),
            service('api_platform.pagination'),
            tagged_iterator('api_platform.meilisearch.request_parameters_extension.collection'),
            service('api_platform.inflector')->nullOnInvalid(),
        ])
        ->tag('api_platform.state_provider', ['priority' => -100, 'key' => 'ApiPlatform\Meilisearch\State\CollectionProvider'])
        ->tag('api_platform.state_provider', ['priority' => -100]);

    $services->alias(CollectionProvider::class, 'api_platform.meilisearch.state.collection_provider');

    $services->set('api_platform.meilisearch.metadata.resource.metadata_collection_factory', MeilisearchProviderResourceMetadataCollectionFactory::class)
        ->decorate('api_platform.metadata.resource.metadata_collection_factory', null, 39)
        ->args([service('api_platform.meilisearch.metadata.resource.metadata_collection_factory.inner')]);
};
