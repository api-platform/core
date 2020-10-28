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

use ApiPlatform\Core\Bridge\Doctrine\Common\DataPersister;
use ApiPlatform\Core\Bridge\Doctrine\Orm\CollectionDataProvider;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\EagerLoadingExtension;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\FilterEagerLoadingExtension;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\FilterExtension;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\OrderExtension;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\PaginationExtension;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\ExistsFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\NumericFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\RangeFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\ItemDataProvider;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Metadata\Property\DoctrineOrmPropertyMetadataFactory;
use ApiPlatform\Core\Bridge\Doctrine\Orm\SubresourceDataProvider;
use Doctrine\Persistence\Mapping\ClassMetadataFactory;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set('api_platform.doctrine.metadata_factory', ClassMetadataFactory::class)
            ->factory([ref('doctrine.orm.default_entity_manager'), 'getMetadataFactory'])

        ->set('api_platform.doctrine.orm.data_persister', DataPersister::class)
            ->args([ref('doctrine')])
            ->tag('api_platform.data_persister', ['priority' => -1000])

        ->set('api_platform.doctrine.orm.collection_data_provider')
            ->args([ref('doctrine'), tagged_iterator('api_platform.doctrine.orm.query_extension.collection')])

        ->set('api_platform.doctrine.orm.item_data_provider')
            ->args([ref('doctrine'), ref('api_platform.metadata.property.name_collection_factory'), ref('api_platform.metadata.property.metadata_factory'), tagged_iterator('api_platform.doctrine.orm.query_extension.item')])

        ->set('api_platform.doctrine.orm.subresource_data_provider')
            ->args([ref('doctrine'), ref('api_platform.metadata.property.name_collection_factory'), ref('api_platform.metadata.property.metadata_factory'), tagged_iterator('api_platform.doctrine.orm.query_extension.collection'), tagged_iterator('api_platform.doctrine.orm.query_extension.item')])

        ->set('api_platform.doctrine.orm.default.collection_data_provider', CollectionDataProvider::class)
            ->parent('api_platform.doctrine.orm.collection_data_provider')
            ->tag('api_platform.collection_data_provider')

        ->set('api_platform.doctrine.orm.default.item_data_provider', ItemDataProvider::class)
            ->parent('api_platform.doctrine.orm.item_data_provider')
            ->tag('api_platform.item_data_provider')

        ->set('api_platform.doctrine.orm.default.subresource_data_provider', SubresourceDataProvider::class)
            ->parent('api_platform.doctrine.orm.subresource_data_provider')
            ->tag('api_platform.subresource_data_provider')

        ->set('api_platform.doctrine.orm.search_filter', SearchFilter::class)
            ->args([ref('doctrine'), null, ref('api_platform.iri_converter'), ref('api_platform.property_accessor'), ref('logger')->ignoreOnInvalid(), "$identifiersExtractor" => ref('api_platform.identifiers_extractor.cached')->ignoreOnInvalid(), "$nameConverter" => ref('api_platform.name_converter')->ignoreOnInvalid()])
        ->alias(SearchFilter::class, 'api_platform.doctrine.orm.search_filter')

        ->set('api_platform.doctrine.orm.order_filter', OrderFilter::class)
            ->args([ref('doctrine'), null, param('api_platform.collection.order_parameter_name'), ref('logger')->ignoreOnInvalid(), "$nameConverter" => ref('api_platform.name_converter')->ignoreOnInvalid()])
        ->alias(OrderFilter::class, 'api_platform.doctrine.orm.order_filter')

        ->set('api_platform.doctrine.orm.range_filter', RangeFilter::class)
            ->args([ref('doctrine'), null, ref('logger')->ignoreOnInvalid()(), "$nameConverter" => ref('api_platform.name_converter')->ignoreOnInvalid()])
        ->alias(RangeFilter::class, 'api_platform.doctrine.orm.range_filter')

        ->set('api_platform.doctrine.orm.date_filter', DateFilter::class)
            ->args([ref('doctrine'), null, ref('logger')->ignoreOnInvalid()(), "$nameConverter" => ref('api_platform.name_converter')->ignoreOnInvalid()])
        ->alias(DateFilter::class, 'api_platform.doctrine.orm.date_filter')

        ->set('api_platform.doctrine.orm.boolean_filter', BooleanFilter::class)
            ->args([ref('doctrine'), null, ref('logger')->ignoreOnInvalid()(), "$nameConverter" => ref('api_platform.name_converter')->ignoreOnInvalid()])
        ->alias(BooleanFilter::class, 'api_platform.doctrine.orm.boolean_filter')

        ->set('api_platform.doctrine.orm.numeric_filter', NumericFilter::class)
            ->args([ref('doctrine'), null, ref('logger')->ignoreOnInvalid()(), "$nameConverter" => ref('api_platform.name_converter')->ignoreOnInvalid()])
        ->alias(NumericFilter::class, 'api_platform.doctrine.orm.numeric_filter')

        ->set('api_platform.doctrine.orm.exists_filter', ExistsFilter::class)
            ->args([ref('doctrine'), null, ref('logger')->ignoreOnInvalid()(), "$existsParameterName" => param('api_platform.collection.exists_parameter_name'), "$nameConverter" => ref('api_platform.name_converter')->ignoreOnInvalid()])
        ->alias(ExistsFilter::class, 'api_platform.doctrine.orm.exists_filter')

        ->set('api_platform.doctrine.orm.metadata.property.metadata_factory', DoctrineOrmPropertyMetadataFactory::class)
            ->decorate('api_platform.metadata.property.metadata_factory', null, 40)
            ->args([ref('doctrine'), ref('api_platform.doctrine.orm.metadata.property.metadata_factory.inner')])

        ->set('api_platform.doctrine.orm.query_extension.eager_loading', EagerLoadingExtension::class)
            ->args([ref('api_platform.metadata.property.name_collection_factory'), ref('api_platform.metadata.property.metadata_factory'), ref('api_platform.metadata.resource.metadata_factory'), param('api_platform.eager_loading.max_joins'), param('api_platform.eager_loading.force_eager'), null, null, param('api_platform.eager_loading.fetch_partial'), ref('serializer.mapping.class_metadata_factory')])
            ->tag('api_platform.doctrine.orm.query_extension.item', ['priority' => -8])
            ->tag('api_platform.doctrine.orm.query_extension.collection', ['priority' => -8])
        ->alias(EagerLoadingExtension::class, 'api_platform.doctrine.orm.query_extension.eager_loading')

        ->set('api_platform.doctrine.orm.query_extension.filter', FilterExtension::class)
            ->args([ref('api_platform.metadata.resource.metadata_factory'), ref('api_platform.filter_locator')])
            ->tag('api_platform.doctrine.orm.query_extension.collection', ['priority' => -16])
        ->alias(FilterExtension::class, 'api_platform.doctrine.orm.query_extension.filter')

        ->set('api_platform.doctrine.orm.query_extension.filter_eager_loading', FilterEagerLoadingExtension::class)
            ->args([ref('api_platform.metadata.resource.metadata_factory'), param('api_platform.eager_loading.force_eager'), ref('api_platform.resource_class_resolver')])
            ->tag('api_platform.doctrine.orm.query_extension.collection', ['priority' => -17])
        ->alias(FilterEagerLoadingExtension::class, 'api_platform.doctrine.orm.query_extension.filter_eager_loading')

        ->set('api_platform.doctrine.orm.query_extension.pagination', PaginationExtension::class)
            ->args([ref('doctrine'), ref('api_platform.metadata.resource.metadata_factory'), ref('api_platform.pagination')])
            ->tag('api_platform.doctrine.orm.query_extension.collection', ['priority' => -64])
        ->alias(PaginationExtension::class, 'api_platform.doctrine.orm.query_extension.pagination')

        ->set('api_platform.doctrine.orm.query_extension.order', OrderExtension::class)
            ->args([param('api_platform.collection.order'), ref('api_platform.metadata.resource.metadata_factory')])
            ->tag('api_platform.doctrine.orm.query_extension.collection', ['priority' => -32])
        ->alias(OrderExtension::class, 'api_platform.doctrine.orm.query_extension.order');
};
