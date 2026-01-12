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

    $services->set('api_platform.doctrine_mongodb.odm.default_document_manager.property_info_extractor', 'ApiPlatform\Doctrine\Odm\PropertyInfo\DoctrineExtractor')
        ->args([service('doctrine_mongodb.odm.default_document_manager')])
        ->tag('property_info.list_extractor', ['priority' => -1001])
        ->tag('property_info.type_extractor', ['priority' => -999])
        ->tag('property_info.access_extractor', ['priority' => -999]);

    $services->set('api_platform.doctrine.metadata_factory', 'Doctrine\Persistence\Mapping\ClassMetadataFactory')->factory([service('doctrine_mongodb.odm.default_document_manager'), 'getMetadataFactory']);

    $services->set('api_platform.doctrine_mongodb.odm.state.remove_processor', 'ApiPlatform\Doctrine\Common\State\RemoveProcessor')
        ->args([service('doctrine_mongodb')])
        ->tag('api_platform.state_processor', ['priority' => -100, 'key' => 'api_platform.doctrine_mongodb.odm.state.remove_processor'])
        ->tag('api_platform.state_processor', ['priority' => -100, 'key' => 'ApiPlatform\Doctrine\Common\State\RemoveProcessor']);

    $services->alias('ApiPlatform\Doctrine\Common\State\RemoveProcessor', 'api_platform.doctrine_mongodb.odm.state.remove_processor');

    $services->set('api_platform.doctrine_mongodb.odm.state.persist_processor', 'ApiPlatform\Doctrine\Common\State\PersistProcessor')
        ->args([service('doctrine_mongodb')])
        ->tag('api_platform.state_processor', ['priority' => -100, 'key' => 'api_platform.doctrine_mongodb.odm.state.persist_processor'])
        ->tag('api_platform.state_processor', ['priority' => -100, 'key' => 'ApiPlatform\Doctrine\Common\State\PersistProcessor']);

    $services->alias('ApiPlatform\Doctrine\Common\State\PersistProcessor', 'api_platform.doctrine_mongodb.odm.state.persist_processor');

    $services->set('api_platform.doctrine_mongodb.odm.search_filter', 'ApiPlatform\Doctrine\Odm\Filter\SearchFilter')
        ->abstract()
        ->arg(0, service('doctrine_mongodb'))
        ->arg(1, service('api_platform.iri_converter'))
        ->arg(2, service('api_platform.identifiers_extractor')->ignoreOnInvalid())
        ->arg(3, service('api_platform.property_accessor'))
        ->arg(4, service('logger')->ignoreOnInvalid())
        ->arg('$nameConverter', service('api_platform.name_converter')->ignoreOnInvalid());

    $services->alias('ApiPlatform\Doctrine\Odm\Filter\SearchFilter', 'api_platform.doctrine_mongodb.odm.search_filter');

    $services->set('api_platform.doctrine_mongodb.odm.search_filter.instance')
        ->parent('api_platform.doctrine_mongodb.odm.search_filter')
        ->args([[]]);

    $services->set('api_platform.doctrine_mongodb.odm.boolean_filter', 'ApiPlatform\Doctrine\Odm\Filter\BooleanFilter')
        ->abstract()
        ->arg(0, service('doctrine_mongodb'))
        ->arg(1, service('logger')->ignoreOnInvalid())
        ->arg('$nameConverter', service('api_platform.name_converter')->ignoreOnInvalid());

    $services->alias('ApiPlatform\Doctrine\Odm\Filter\BooleanFilter', 'api_platform.doctrine_mongodb.odm.boolean_filter');

    $services->set('api_platform.doctrine_mongodb.odm.boolean_filter.instance')
        ->parent('api_platform.doctrine_mongodb.odm.boolean_filter')
        ->args([[]]);

    $services->set('api_platform.doctrine_mongodb.odm.date_filter', 'ApiPlatform\Doctrine\Odm\Filter\DateFilter')
        ->abstract()
        ->arg(0, service('doctrine_mongodb'))
        ->arg(1, service('logger')->ignoreOnInvalid())
        ->arg('$nameConverter', service('api_platform.name_converter')->ignoreOnInvalid());

    $services->alias('ApiPlatform\Doctrine\Odm\Filter\DateFilter', 'api_platform.doctrine_mongodb.odm.date_filter');

    $services->set('api_platform.doctrine_mongodb.odm.date_filter.instance')
        ->parent('api_platform.doctrine_mongodb.odm.date_filter')
        ->args([[]]);

    $services->set('api_platform.doctrine_mongodb.odm.exists_filter', 'ApiPlatform\Doctrine\Odm\Filter\ExistsFilter')
        ->abstract()
        ->arg(0, service('doctrine_mongodb'))
        ->arg(1, service('logger')->ignoreOnInvalid())
        ->arg('$existsParameterName', '%api_platform.collection.exists_parameter_name%')
        ->arg('$nameConverter', service('api_platform.name_converter')->ignoreOnInvalid());

    $services->alias('ApiPlatform\Doctrine\Odm\Filter\ExistsFilter', 'api_platform.doctrine_mongodb.odm.exists_filter');

    $services->set('api_platform.doctrine_mongodb.odm.exists_filter.instance')
        ->parent('api_platform.doctrine_mongodb.odm.exists_filter')
        ->args([[]]);

    $services->set('api_platform.doctrine_mongodb.odm.numeric_filter', 'ApiPlatform\Doctrine\Odm\Filter\NumericFilter')
        ->abstract()
        ->arg(0, service('doctrine_mongodb'))
        ->arg(1, service('logger')->ignoreOnInvalid())
        ->arg('$nameConverter', service('api_platform.name_converter')->ignoreOnInvalid());

    $services->alias('ApiPlatform\Doctrine\Odm\Filter\NumericFilter', 'api_platform.doctrine_mongodb.odm.numeric_filter');

    $services->set('api_platform.doctrine_mongodb.odm.numeric_filter.instance')
        ->parent('api_platform.doctrine_mongodb.odm.numeric_filter')
        ->args([[]]);

    $services->set('api_platform.doctrine_mongodb.odm.order_filter', 'ApiPlatform\Doctrine\Odm\Filter\OrderFilter')
        ->abstract()
        ->arg(0, service('doctrine_mongodb'))
        ->arg(1, '%api_platform.collection.order_parameter_name%')
        ->arg(2, service('logger')->ignoreOnInvalid())
        ->arg('$nameConverter', service('api_platform.name_converter')->ignoreOnInvalid());

    $services->alias('ApiPlatform\Doctrine\Odm\Filter\OrderFilter', 'api_platform.doctrine_mongodb.odm.order_filter');

    $services->set('api_platform.doctrine_mongodb.odm.order_filter.instance')
        ->parent('api_platform.doctrine_mongodb.odm.order_filter')
        ->args([[]]);

    $services->set('api_platform.doctrine_mongodb.odm.range_filter', 'ApiPlatform\Doctrine\Odm\Filter\RangeFilter')
        ->abstract()
        ->arg(0, service('doctrine_mongodb'))
        ->arg(1, service('logger')->ignoreOnInvalid())
        ->arg('$nameConverter', service('api_platform.name_converter')->ignoreOnInvalid());

    $services->alias('ApiPlatform\Doctrine\Odm\Filter\RangeFilter', 'api_platform.doctrine_mongodb.odm.range_filter');

    $services->set('api_platform.doctrine_mongodb.odm.range_filter.instance')
        ->parent('api_platform.doctrine_mongodb.odm.range_filter')
        ->args([[]]);

    $services->set('api_platform.doctrine_mongodb.odm.aggregation_extension.filter', 'ApiPlatform\Doctrine\Odm\Extension\FilterExtension')
        ->args([service('api_platform.filter_locator')])
        ->tag('api_platform.doctrine_mongodb.odm.aggregation_extension.collection', ['priority' => 32]);

    $services->alias('ApiPlatform\Doctrine\Odm\Extension\FilterExtension', 'api_platform.doctrine_mongodb.odm.aggregation_extension.filter');

    $services->set('api_platform.doctrine_mongodb.odm.aggregation_extension.pagination', 'ApiPlatform\Doctrine\Odm\Extension\PaginationExtension')
        ->args([
            service('doctrine_mongodb'),
            service('api_platform.pagination'),
        ])
        ->tag('api_platform.doctrine_mongodb.odm.aggregation_extension.collection');

    $services->alias('ApiPlatform\Doctrine\Odm\Extension\PaginationExtension', 'api_platform.doctrine_mongodb.odm.aggregation_extension.pagination');

    $services->set('api_platform.doctrine_mongodb.odm.aggregation_extension.order', 'ApiPlatform\Doctrine\Odm\Extension\OrderExtension')
        ->args([
            '%api_platform.collection.order%',
            service('doctrine_mongodb'),
        ])
        ->tag('api_platform.doctrine_mongodb.odm.aggregation_extension.collection', ['priority' => 16]);

    $services->alias('ApiPlatform\Doctrine\Odm\Extension\OrderExtension', 'api_platform.doctrine_mongodb.odm.aggregation_extension.order');

    $services->set('api_platform.doctrine_mongodb.odm.extension.parameter_extension', 'ApiPlatform\Doctrine\Odm\Extension\ParameterExtension')
        ->args([
            service('api_platform.filter_locator'),
            service('doctrine_mongodb')->nullOnInvalid(),
            service('logger')->nullOnInvalid(),
        ])
        ->tag('api_platform.doctrine_mongodb.odm.aggregation_extension.collection', ['priority' => 32])
        ->tag('api_platform.doctrine_mongodb.odm.aggregation_extension.item');

    $services->alias('ApiPlatform\Doctrine\Odm\Extension\ParameterExtension', 'api_platform.doctrine_mongodb.odm.extension.parameter_extension');

    $services->set('api_platform.doctrine_mongodb.odm.metadata.property.metadata_factory', 'ApiPlatform\Doctrine\Odm\Metadata\Property\DoctrineMongoDbOdmPropertyMetadataFactory')
        ->decorate('api_platform.metadata.property.metadata_factory', null, 40)
        ->args([
            service('doctrine_mongodb'),
            service('api_platform.doctrine_mongodb.odm.metadata.property.metadata_factory.inner'),
        ]);

    $services->set('api_platform.doctrine_mongodb.odm.state.collection_provider', 'ApiPlatform\Doctrine\Odm\State\CollectionProvider')
        ->args([
            service('api_platform.metadata.resource.metadata_collection_factory'),
            service('doctrine_mongodb'),
            tagged_iterator('api_platform.doctrine_mongodb.odm.aggregation_extension.collection'),
            tagged_locator('api_platform.doctrine.odm.links_handler', 'key'),
        ])
        ->tag('api_platform.state_provider', ['priority' => -100, 'key' => 'ApiPlatform\Doctrine\Odm\State\CollectionProvider'])
        ->tag('api_platform.state_provider', ['priority' => -100, 'key' => 'api_platform.doctrine_mongodb.odm.state.collection_provider']);

    $services->alias('ApiPlatform\Doctrine\Odm\State\CollectionProvider', 'api_platform.doctrine_mongodb.odm.state.collection_provider');

    $services->set('api_platform.doctrine_mongodb.odm.state.item_provider', 'ApiPlatform\Doctrine\Odm\State\ItemProvider')
        ->args([
            service('api_platform.metadata.resource.metadata_collection_factory'),
            service('doctrine_mongodb'),
            tagged_iterator('api_platform.doctrine_mongodb.odm.aggregation_extension.item'),
            tagged_locator('api_platform.doctrine.odm.links_handler', 'key'),
        ])
        ->tag('api_platform.state_provider', ['priority' => -100, 'key' => 'ApiPlatform\Doctrine\Odm\State\ItemProvider'])
        ->tag('api_platform.state_provider', ['priority' => -100, 'key' => 'api_platform.doctrine_mongodb.odm.state.item_provider']);

    $services->alias('ApiPlatform\Doctrine\Odm\State\ItemProvider', 'api_platform.doctrine_mongodb.odm.state.item_provider');

    $services->alias('api_platform.state.item_provider', 'ApiPlatform\Doctrine\Odm\State\ItemProvider');

    $services->set('api_platform.doctrine.odm.metadata.resource.metadata_collection_factory', 'ApiPlatform\Doctrine\Odm\Metadata\Resource\DoctrineMongoDbOdmResourceCollectionMetadataFactory')
        ->decorate('api_platform.metadata.resource.metadata_collection_factory', null, 40)
        ->args([
            service('doctrine_mongodb'),
            service('api_platform.doctrine.odm.metadata.resource.metadata_collection_factory.inner'),
        ]);

    $services->set('api_platform.doctrine.odm.links_handler', 'ApiPlatform\Doctrine\Odm\State\LinksHandler')
        ->args([
            service('api_platform.metadata.resource.metadata_collection_factory'),
            service('doctrine_mongodb'),
        ])
        ->tag('api_platform.doctrine.odm.links_handler', ['key' => 'api_platform.doctrine.odm.links_handler']);
};
