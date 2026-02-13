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

use ApiPlatform\Doctrine\Common\State\PersistProcessor;
use ApiPlatform\Doctrine\Common\State\RemoveProcessor;
use ApiPlatform\Doctrine\Orm\Extension\EagerLoadingExtension;
use ApiPlatform\Doctrine\Orm\Extension\FilterEagerLoadingExtension;
use ApiPlatform\Doctrine\Orm\Extension\FilterExtension;
use ApiPlatform\Doctrine\Orm\Extension\OrderExtension;
use ApiPlatform\Doctrine\Orm\Extension\PaginationExtension;
use ApiPlatform\Doctrine\Orm\Extension\ParameterExtension;
use ApiPlatform\Doctrine\Orm\Filter\BackedEnumFilter;
use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Doctrine\Orm\Filter\ExistsFilter;
use ApiPlatform\Doctrine\Orm\Filter\NumericFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\RangeFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\Filter\UlidFilter;
use ApiPlatform\Doctrine\Orm\Filter\UuidBinaryFilter;
use ApiPlatform\Doctrine\Orm\Filter\UuidFilter;
use ApiPlatform\Doctrine\Orm\Metadata\Property\DoctrineOrmPropertyMetadataFactory;
use ApiPlatform\Doctrine\Orm\Metadata\Resource\DoctrineOrmLinkFactory;
use ApiPlatform\Doctrine\Orm\Metadata\Resource\DoctrineOrmResourceCollectionMetadataFactory;
use ApiPlatform\Doctrine\Orm\State\CollectionProvider;
use ApiPlatform\Doctrine\Orm\State\ItemProvider;
use ApiPlatform\Doctrine\Orm\State\LinksHandler;
use Doctrine\Persistence\Mapping\ClassMetadataFactory;

return function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('api_platform.doctrine.metadata_factory', ClassMetadataFactory::class)->factory([service('doctrine.orm.default_entity_manager'), 'getMetadataFactory']);

    $services->set('api_platform.doctrine.orm.state.remove_processor', RemoveProcessor::class)
        ->args([service('doctrine')])
        ->tag('api_platform.state_processor', ['priority' => -100, 'key' => 'api_platform.doctrine.orm.state.remove_processor'])
        ->tag('api_platform.state_processor', ['priority' => -100, 'key' => 'ApiPlatform\Doctrine\Common\State\RemoveProcessor']);

    $services->alias(RemoveProcessor::class, 'api_platform.doctrine.orm.state.remove_processor');

    $services->set('api_platform.doctrine.orm.state.persist_processor', PersistProcessor::class)
        ->args([service('doctrine')])
        ->tag('api_platform.state_processor', ['priority' => -100, 'key' => 'api_platform.doctrine.orm.state.persist_processor'])
        ->tag('api_platform.state_processor', ['priority' => -100, 'key' => 'ApiPlatform\Doctrine\Common\State\PersistProcessor']);

    $services->alias(PersistProcessor::class, 'api_platform.doctrine.orm.state.persist_processor');

    $services->set('api_platform.doctrine.orm.order_filter', OrderFilter::class)
        ->abstract()
        ->arg(0, service('doctrine'))
        ->arg(1, '%api_platform.collection.order_parameter_name%')
        ->arg(2, service('logger')->ignoreOnInvalid())
        ->arg('$nameConverter', service('api_platform.name_converter')->ignoreOnInvalid())
        ->arg('$orderNullsComparison', '%api_platform.collection.order_nulls_comparison%');

    $services->alias(OrderFilter::class, 'api_platform.doctrine.orm.order_filter');

    $services->set('api_platform.doctrine.orm.order_filter.instance')
        ->parent('api_platform.doctrine.orm.order_filter')
        ->args([[]]);

    $services->set('api_platform.doctrine.orm.range_filter', RangeFilter::class)
        ->abstract()
        ->arg(0, service('doctrine'))
        ->arg(1, service('logger')->ignoreOnInvalid())
        ->arg('$nameConverter', service('api_platform.name_converter')->ignoreOnInvalid());

    $services->alias(RangeFilter::class, 'api_platform.doctrine.orm.range_filter');

    $services->set('api_platform.doctrine.orm.range_filter.instance')
        ->parent('api_platform.doctrine.orm.range_filter')
        ->args([[]]);

    $services->set('api_platform.doctrine.orm.date_filter', DateFilter::class)
        ->abstract()
        ->arg(0, service('doctrine'))
        ->arg(1, service('logger')->ignoreOnInvalid())
        ->arg('$nameConverter', service('api_platform.name_converter')->ignoreOnInvalid());

    $services->alias(DateFilter::class, 'api_platform.doctrine.orm.date_filter');

    $services->set('api_platform.doctrine.orm.date_filter.instance')
        ->parent('api_platform.doctrine.orm.date_filter')
        ->args([[]]);

    $services->set('api_platform.doctrine.orm.backed_enum_filter', BackedEnumFilter::class)
        ->abstract()
        ->arg(0, service('doctrine'))
        ->arg(1, service('logger')->ignoreOnInvalid())
        ->arg('$nameConverter', service('api_platform.name_converter')->ignoreOnInvalid());

    $services->alias(BackedEnumFilter::class, 'api_platform.doctrine.orm.backed_enum_filter');

    $services->set('api_platform.doctrine.orm.backed_enum_filter.instance')
        ->parent('api_platform.doctrine.orm.backed_enum_filter')
        ->args([[]]);

    $services->set('api_platform.doctrine.orm.boolean_filter', BooleanFilter::class)
        ->abstract()
        ->arg(0, service('doctrine'))
        ->arg(1, service('logger')->ignoreOnInvalid())
        ->arg('$nameConverter', service('api_platform.name_converter')->ignoreOnInvalid());

    $services->alias(BooleanFilter::class, 'api_platform.doctrine.orm.boolean_filter');

    $services->set('api_platform.doctrine.orm.boolean_filter.instance')
        ->parent('api_platform.doctrine.orm.boolean_filter')
        ->args([[]]);

    $services->set('api_platform.doctrine.orm.numeric_filter', NumericFilter::class)
        ->abstract()
        ->arg(0, service('doctrine'))
        ->arg(1, service('logger')->ignoreOnInvalid())
        ->arg('$nameConverter', service('api_platform.name_converter')->ignoreOnInvalid());

    $services->alias(NumericFilter::class, 'api_platform.doctrine.orm.numeric_filter');

    $services->set('api_platform.doctrine.orm.numeric_filter.instance')
        ->parent('api_platform.doctrine.orm.numeric_filter')
        ->args([[]]);

    $services->set('api_platform.doctrine.orm.exists_filter', ExistsFilter::class)
        ->abstract()
        ->arg(0, service('doctrine'))
        ->arg(1, service('logger')->ignoreOnInvalid())
        ->arg('$existsParameterName', '%api_platform.collection.exists_parameter_name%')
        ->arg('$nameConverter', service('api_platform.name_converter')->ignoreOnInvalid());

    $services->alias(ExistsFilter::class, 'api_platform.doctrine.orm.exists_filter');

    $services->set('api_platform.doctrine.orm.exists_filter.instance')
        ->parent('api_platform.doctrine.orm.exists_filter')
        ->args([[]]);

    $services->set('api_platform.doctrine.orm.query_extension.eager_loading', EagerLoadingExtension::class)
        ->args([
            service('api_platform.metadata.property.name_collection_factory'),
            service('api_platform.metadata.property.metadata_factory'),
            '%api_platform.eager_loading.max_joins%',
            '%api_platform.eager_loading.force_eager%',
            '%api_platform.eager_loading.fetch_partial%',
            service('serializer.mapping.class_metadata_factory'),
        ])
        ->tag('api_platform.doctrine.orm.query_extension.item', ['priority' => -8])
        ->tag('api_platform.doctrine.orm.query_extension.collection', ['priority' => -18]);

    $services->alias(EagerLoadingExtension::class, 'api_platform.doctrine.orm.query_extension.eager_loading');

    $services->set('api_platform.doctrine.orm.query_extension.filter', FilterExtension::class)
        ->args([service('api_platform.filter_locator')])
        ->tag('api_platform.doctrine.orm.query_extension.collection', ['priority' => -16]);

    $services->alias(FilterExtension::class, 'api_platform.doctrine.orm.query_extension.filter');

    $services->set('api_platform.doctrine.orm.query_extension.filter_eager_loading', FilterEagerLoadingExtension::class)
        ->args([
            '%api_platform.eager_loading.force_eager%',
            service('api_platform.resource_class_resolver'),
        ])
        ->tag('api_platform.doctrine.orm.query_extension.collection', ['priority' => -17]);

    $services->alias(FilterEagerLoadingExtension::class, 'api_platform.doctrine.orm.query_extension.filter_eager_loading');

    $services->set('api_platform.doctrine.orm.query_extension.pagination', PaginationExtension::class)
        ->args([
            service('doctrine'),
            service('api_platform.pagination'),
        ])
        ->tag('api_platform.doctrine.orm.query_extension.collection', ['priority' => -64]);

    $services->alias(PaginationExtension::class, 'api_platform.doctrine.orm.query_extension.pagination');

    $services->set('api_platform.doctrine.orm.query_extension.order', OrderExtension::class)
        ->args(['%api_platform.collection.order%'])
        ->tag('api_platform.doctrine.orm.query_extension.collection', ['priority' => -32]);

    $services->alias(OrderExtension::class, 'api_platform.doctrine.orm.query_extension.order');

    $services->set('api_platform.doctrine.orm.extension.parameter_extension', ParameterExtension::class)
        ->args([
            service('api_platform.filter_locator'),
            service('doctrine')->nullOnInvalid(),
            service('logger')->nullOnInvalid(),
        ])
        ->tag('api_platform.doctrine.orm.query_extension.collection', ['priority' => -16])
        ->tag('api_platform.doctrine.orm.query_extension.item', ['priority' => -9]);

    $services->alias(ParameterExtension::class, 'api_platform.doctrine.orm.extension.parameter_extension');

    $services->set('api_platform.doctrine.orm.metadata.property.metadata_factory', DoctrineOrmPropertyMetadataFactory::class)
        ->decorate('api_platform.metadata.property.metadata_factory', null, 40)
        ->args([
            service('doctrine'),
            service('api_platform.doctrine.orm.metadata.property.metadata_factory.inner'),
        ]);

    $services->set('api_platform.doctrine.orm.state.collection_provider', CollectionProvider::class)
        ->args([
            service('api_platform.metadata.resource.metadata_collection_factory'),
            service('doctrine'),
            tagged_iterator('api_platform.doctrine.orm.query_extension.collection'),
            tagged_locator('api_platform.doctrine.orm.links_handler', 'key'),
        ])
        ->tag('api_platform.state_provider', ['priority' => -100, 'key' => 'ApiPlatform\Doctrine\Orm\State\CollectionProvider'])
        ->tag('api_platform.state_provider', ['priority' => -100, 'key' => 'api_platform.doctrine.orm.state.collection_provider']);

    $services->alias(CollectionProvider::class, 'api_platform.doctrine.orm.state.collection_provider');

    $services->set('api_platform.doctrine.orm.state.item_provider', ItemProvider::class)
        ->args([
            service('api_platform.metadata.resource.metadata_collection_factory'),
            service('doctrine'),
            tagged_iterator('api_platform.doctrine.orm.query_extension.item'),
            tagged_locator('api_platform.doctrine.orm.links_handler', 'key'),
        ])
        ->tag('api_platform.state_provider', ['priority' => -100, 'key' => 'ApiPlatform\Doctrine\Orm\State\ItemProvider'])
        ->tag('api_platform.state_provider', ['priority' => -100, 'key' => 'api_platform.doctrine.orm.state.item_provider']);

    $services->alias(ItemProvider::class, 'api_platform.doctrine.orm.state.item_provider');

    $services->alias('api_platform.state.item_provider', 'ApiPlatform\Doctrine\Orm\State\ItemProvider');

    $services->set('api_platform.doctrine.orm.search_filter', SearchFilter::class)
        ->abstract()
        ->arg(0, service('doctrine'))
        ->arg(1, service('api_platform.iri_converter'))
        ->arg(2, service('api_platform.property_accessor'))
        ->arg(3, service('logger')->ignoreOnInvalid())
        ->arg('$identifiersExtractor', service('api_platform.identifiers_extractor')->ignoreOnInvalid())
        ->arg('$nameConverter', service('api_platform.name_converter')->ignoreOnInvalid());

    $services->alias(SearchFilter::class, 'api_platform.doctrine.orm.search_filter');

    $services->set('api_platform.doctrine.orm.search_filter.instance')
        ->parent('api_platform.doctrine.orm.search_filter')
        ->args([[]]);

    $services->set('api_platform.doctrine.orm.uuid_filter', UuidFilter::class);
    $services->alias(UuidFilter::class, 'api_platform.doctrine.orm.uuid_filter');

    $services->set('api_platform.doctrine.orm.ulid_filter', UlidFilter::class);
    $services->alias(UlidFilter::class, 'api_platform.doctrine.orm.ulid_filter');

    $services->set('api_platform.doctrine.orm.uuid_binary_filter', UuidBinaryFilter::class);
    $services->alias(UuidBinaryFilter::class, 'api_platform.doctrine.orm.uuid_binary_filter');

    $services->set('api_platform.doctrine.orm.metadata.resource.metadata_collection_factory', DoctrineOrmResourceCollectionMetadataFactory::class)
        ->decorate('api_platform.metadata.resource.metadata_collection_factory', null, -50)
        ->args([
            service('doctrine'),
            service('api_platform.doctrine.orm.metadata.resource.metadata_collection_factory.inner'),
        ]);

    $services->set('api_platform.doctrine.orm.metadata.resource.link_factory', DoctrineOrmLinkFactory::class)
        ->decorate('api_platform.metadata.resource.link_factory', null, 40)
        ->args([
            service('doctrine'),
            service('api_platform.metadata.property.name_collection_factory'),
            service('api_platform.resource_class_resolver'),
            service('api_platform.doctrine.orm.metadata.resource.link_factory.inner'),
        ]);

    $services->set('api_platform.doctrine.orm.links_handler', LinksHandler::class)
        ->args([
            service('api_platform.metadata.resource.metadata_collection_factory'),
            service('doctrine'),
        ])
        ->tag('api_platform.doctrine.orm.links_handler', ['key' => 'api_platform.doctrine.orm.links_handler']);
};
