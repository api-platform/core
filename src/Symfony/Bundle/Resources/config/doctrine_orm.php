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

    $services->set('api_platform.doctrine.metadata_factory', 'Doctrine\Persistence\Mapping\ClassMetadataFactory')->factory([service('doctrine.orm.default_entity_manager'), 'getMetadataFactory']);

    $services->set('api_platform.doctrine.orm.state.remove_processor', 'ApiPlatform\Doctrine\Common\State\RemoveProcessor')
        ->args([service('doctrine')])
        ->tag('api_platform.state_processor', ['priority' => -100, 'key' => 'api_platform.doctrine.orm.state.remove_processor'])
        ->tag('api_platform.state_processor', ['priority' => -100, 'key' => 'ApiPlatform\Doctrine\Common\State\RemoveProcessor']);

    $services->alias('ApiPlatform\Doctrine\Common\State\RemoveProcessor', 'api_platform.doctrine.orm.state.remove_processor');

    $services->set('api_platform.doctrine.orm.state.persist_processor', 'ApiPlatform\Doctrine\Common\State\PersistProcessor')
        ->args([service('doctrine')])
        ->tag('api_platform.state_processor', ['priority' => -100, 'key' => 'api_platform.doctrine.orm.state.persist_processor'])
        ->tag('api_platform.state_processor', ['priority' => -100, 'key' => 'ApiPlatform\Doctrine\Common\State\PersistProcessor']);

    $services->alias('ApiPlatform\Doctrine\Common\State\PersistProcessor', 'api_platform.doctrine.orm.state.persist_processor');

    $services->set('api_platform.doctrine.orm.order_filter', 'ApiPlatform\Doctrine\Orm\Filter\OrderFilter')
        ->abstract()
        ->arg(0, service('doctrine'))
        ->arg(1, '%api_platform.collection.order_parameter_name%')
        ->arg(2, service('logger')->ignoreOnInvalid())
        ->arg('$nameConverter', service('api_platform.name_converter')->ignoreOnInvalid())
        ->arg('$orderNullsComparison', '%api_platform.collection.order_nulls_comparison%');

    $services->alias('ApiPlatform\Doctrine\Orm\Filter\OrderFilter', 'api_platform.doctrine.orm.order_filter');

    $services->set('api_platform.doctrine.orm.order_filter.instance')
        ->parent('api_platform.doctrine.orm.order_filter')
        ->args([[]]);

    $services->set('api_platform.doctrine.orm.range_filter', 'ApiPlatform\Doctrine\Orm\Filter\RangeFilter')
        ->abstract()
        ->arg(0, service('doctrine'))
        ->arg(1, service('logger')->ignoreOnInvalid())
        ->arg('$nameConverter', service('api_platform.name_converter')->ignoreOnInvalid());

    $services->alias('ApiPlatform\Doctrine\Orm\Filter\RangeFilter', 'api_platform.doctrine.orm.range_filter');

    $services->set('api_platform.doctrine.orm.range_filter.instance')
        ->parent('api_platform.doctrine.orm.range_filter')
        ->args([[]]);

    $services->set('api_platform.doctrine.orm.date_filter', 'ApiPlatform\Doctrine\Orm\Filter\DateFilter')
        ->abstract()
        ->arg(0, service('doctrine'))
        ->arg(1, service('logger')->ignoreOnInvalid())
        ->arg('$nameConverter', service('api_platform.name_converter')->ignoreOnInvalid());

    $services->alias('ApiPlatform\Doctrine\Orm\Filter\DateFilter', 'api_platform.doctrine.orm.date_filter');

    $services->set('api_platform.doctrine.orm.date_filter.instance')
        ->parent('api_platform.doctrine.orm.date_filter')
        ->args([[]]);

    $services->set('api_platform.doctrine.orm.backed_enum_filter', 'ApiPlatform\Doctrine\Orm\Filter\BackedEnumFilter')
        ->abstract()
        ->arg(0, service('doctrine'))
        ->arg(1, service('logger')->ignoreOnInvalid())
        ->arg('$nameConverter', service('api_platform.name_converter')->ignoreOnInvalid());

    $services->alias('ApiPlatform\Doctrine\Orm\Filter\BackedEnumFilter', 'api_platform.doctrine.orm.backed_enum_filter');

    $services->set('api_platform.doctrine.orm.backed_enum_filter.instance')
        ->parent('api_platform.doctrine.orm.backed_enum_filter')
        ->args([[]]);

    $services->set('api_platform.doctrine.orm.boolean_filter', 'ApiPlatform\Doctrine\Orm\Filter\BooleanFilter')
        ->abstract()
        ->arg(0, service('doctrine'))
        ->arg(1, service('logger')->ignoreOnInvalid())
        ->arg('$nameConverter', service('api_platform.name_converter')->ignoreOnInvalid());

    $services->alias('ApiPlatform\Doctrine\Orm\Filter\BooleanFilter', 'api_platform.doctrine.orm.boolean_filter');

    $services->set('api_platform.doctrine.orm.boolean_filter.instance')
        ->parent('api_platform.doctrine.orm.boolean_filter')
        ->args([[]]);

    $services->set('api_platform.doctrine.orm.numeric_filter', 'ApiPlatform\Doctrine\Orm\Filter\NumericFilter')
        ->abstract()
        ->arg(0, service('doctrine'))
        ->arg(1, service('logger')->ignoreOnInvalid())
        ->arg('$nameConverter', service('api_platform.name_converter')->ignoreOnInvalid());

    $services->alias('ApiPlatform\Doctrine\Orm\Filter\NumericFilter', 'api_platform.doctrine.orm.numeric_filter');

    $services->set('api_platform.doctrine.orm.numeric_filter.instance')
        ->parent('api_platform.doctrine.orm.numeric_filter')
        ->args([[]]);

    $services->set('api_platform.doctrine.orm.exists_filter', 'ApiPlatform\Doctrine\Orm\Filter\ExistsFilter')
        ->abstract()
        ->arg(0, service('doctrine'))
        ->arg(1, service('logger')->ignoreOnInvalid())
        ->arg('$existsParameterName', '%api_platform.collection.exists_parameter_name%')
        ->arg('$nameConverter', service('api_platform.name_converter')->ignoreOnInvalid());

    $services->alias('ApiPlatform\Doctrine\Orm\Filter\ExistsFilter', 'api_platform.doctrine.orm.exists_filter');

    $services->set('api_platform.doctrine.orm.exists_filter.instance')
        ->parent('api_platform.doctrine.orm.exists_filter')
        ->args([[]]);

    $services->set('api_platform.doctrine.orm.query_extension.eager_loading', 'ApiPlatform\Doctrine\Orm\Extension\EagerLoadingExtension')
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

    $services->alias('ApiPlatform\Doctrine\Orm\Extension\EagerLoadingExtension', 'api_platform.doctrine.orm.query_extension.eager_loading');

    $services->set('api_platform.doctrine.orm.query_extension.filter', 'ApiPlatform\Doctrine\Orm\Extension\FilterExtension')
        ->args([service('api_platform.filter_locator')])
        ->tag('api_platform.doctrine.orm.query_extension.collection', ['priority' => -16]);

    $services->alias('ApiPlatform\Doctrine\Orm\Extension\FilterExtension', 'api_platform.doctrine.orm.query_extension.filter');

    $services->set('api_platform.doctrine.orm.query_extension.filter_eager_loading', 'ApiPlatform\Doctrine\Orm\Extension\FilterEagerLoadingExtension')
        ->args([
            '%api_platform.eager_loading.force_eager%',
            service('api_platform.resource_class_resolver'),
        ])
        ->tag('api_platform.doctrine.orm.query_extension.collection', ['priority' => -17]);

    $services->alias('ApiPlatform\Doctrine\Orm\Extension\FilterEagerLoadingExtension', 'api_platform.doctrine.orm.query_extension.filter_eager_loading');

    $services->set('api_platform.doctrine.orm.query_extension.pagination', 'ApiPlatform\Doctrine\Orm\Extension\PaginationExtension')
        ->args([
            service('doctrine'),
            service('api_platform.pagination'),
        ])
        ->tag('api_platform.doctrine.orm.query_extension.collection', ['priority' => -64]);

    $services->alias('ApiPlatform\Doctrine\Orm\Extension\PaginationExtension', 'api_platform.doctrine.orm.query_extension.pagination');

    $services->set('api_platform.doctrine.orm.query_extension.order', 'ApiPlatform\Doctrine\Orm\Extension\OrderExtension')
        ->args(['%api_platform.collection.order%'])
        ->tag('api_platform.doctrine.orm.query_extension.collection', ['priority' => -32]);

    $services->alias('ApiPlatform\Doctrine\Orm\Extension\OrderExtension', 'api_platform.doctrine.orm.query_extension.order');

    $services->set('api_platform.doctrine.orm.extension.parameter_extension', 'ApiPlatform\Doctrine\Orm\Extension\ParameterExtension')
        ->args([
            service('api_platform.filter_locator'),
            service('doctrine')->nullOnInvalid(),
            service('logger')->nullOnInvalid(),
        ])
        ->tag('api_platform.doctrine.orm.query_extension.collection', ['priority' => -16])
        ->tag('api_platform.doctrine.orm.query_extension.item', ['priority' => -9]);

    $services->alias('ApiPlatform\Doctrine\Orm\Extension\ParameterExtension', 'api_platform.doctrine.orm.extension.parameter_extension');

    $services->set('api_platform.doctrine.orm.metadata.property.metadata_factory', 'ApiPlatform\Doctrine\Orm\Metadata\Property\DoctrineOrmPropertyMetadataFactory')
        ->decorate('api_platform.metadata.property.metadata_factory', null, 40)
        ->args([
            service('doctrine'),
            service('api_platform.doctrine.orm.metadata.property.metadata_factory.inner'),
        ]);

    $services->set('api_platform.doctrine.orm.state.collection_provider', 'ApiPlatform\Doctrine\Orm\State\CollectionProvider')
        ->args([
            service('api_platform.metadata.resource.metadata_collection_factory'),
            service('doctrine'),
            tagged_iterator('api_platform.doctrine.orm.query_extension.collection'),
            tagged_locator('api_platform.doctrine.orm.links_handler', 'key'),
        ])
        ->tag('api_platform.state_provider', ['priority' => -100, 'key' => 'ApiPlatform\Doctrine\Orm\State\CollectionProvider'])
        ->tag('api_platform.state_provider', ['priority' => -100, 'key' => 'api_platform.doctrine.orm.state.collection_provider']);

    $services->alias('ApiPlatform\Doctrine\Orm\State\CollectionProvider', 'api_platform.doctrine.orm.state.collection_provider');

    $services->set('api_platform.doctrine.orm.state.item_provider', 'ApiPlatform\Doctrine\Orm\State\ItemProvider')
        ->args([
            service('api_platform.metadata.resource.metadata_collection_factory'),
            service('doctrine'),
            tagged_iterator('api_platform.doctrine.orm.query_extension.item'),
            tagged_locator('api_platform.doctrine.orm.links_handler', 'key'),
        ])
        ->tag('api_platform.state_provider', ['priority' => -100, 'key' => 'ApiPlatform\Doctrine\Orm\State\ItemProvider'])
        ->tag('api_platform.state_provider', ['priority' => -100, 'key' => 'api_platform.doctrine.orm.state.item_provider']);

    $services->alias('ApiPlatform\Doctrine\Orm\State\ItemProvider', 'api_platform.doctrine.orm.state.item_provider');

    $services->alias('api_platform.state.item_provider', 'ApiPlatform\Doctrine\Orm\State\ItemProvider');

    $services->set('api_platform.doctrine.orm.search_filter', 'ApiPlatform\Doctrine\Orm\Filter\SearchFilter')
        ->abstract()
        ->arg(0, service('doctrine'))
        ->arg(1, service('api_platform.iri_converter'))
        ->arg(2, service('api_platform.property_accessor'))
        ->arg(3, service('logger')->ignoreOnInvalid())
        ->arg('$identifiersExtractor', service('api_platform.identifiers_extractor')->ignoreOnInvalid())
        ->arg('$nameConverter', service('api_platform.name_converter')->ignoreOnInvalid());

    $services->alias('ApiPlatform\Doctrine\Orm\Filter\SearchFilter', 'api_platform.doctrine.orm.search_filter');

    $services->set('api_platform.doctrine.orm.search_filter.instance')
        ->parent('api_platform.doctrine.orm.search_filter')
        ->args([[]]);

    $services->set('api_platform.doctrine.orm.metadata.resource.metadata_collection_factory', 'ApiPlatform\Doctrine\Orm\Metadata\Resource\DoctrineOrmResourceCollectionMetadataFactory')
        ->decorate('api_platform.metadata.resource.metadata_collection_factory', null, 40)
        ->args([
            service('doctrine'),
            service('api_platform.doctrine.orm.metadata.resource.metadata_collection_factory.inner'),
        ]);

    $services->set('api_platform.doctrine.orm.metadata.resource.link_factory', 'ApiPlatform\Doctrine\Orm\Metadata\Resource\DoctrineOrmLinkFactory')
        ->decorate('api_platform.metadata.resource.link_factory', null, 40)
        ->args([
            service('doctrine'),
            service('api_platform.metadata.property.name_collection_factory'),
            service('api_platform.resource_class_resolver'),
            service('api_platform.doctrine.orm.metadata.resource.link_factory.inner'),
        ]);

    $services->set('api_platform.doctrine.orm.links_handler', 'ApiPlatform\Doctrine\Orm\State\LinksHandler')
        ->args([
            service('api_platform.metadata.resource.metadata_collection_factory'),
            service('doctrine'),
        ])
        ->tag('api_platform.doctrine.orm.links_handler', ['key' => 'api_platform.doctrine.orm.links_handler']);
};
