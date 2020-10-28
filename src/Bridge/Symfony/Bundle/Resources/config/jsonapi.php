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

use ApiPlatform\Core\JsonApi\EventListener\TransformFieldsetsParametersListener;
use ApiPlatform\Core\JsonApi\EventListener\TransformFilteringParametersListener;
use ApiPlatform\Core\JsonApi\EventListener\TransformPaginationParametersListener;
use ApiPlatform\Core\JsonApi\EventListener\TransformSortingParametersListener;
use ApiPlatform\Core\JsonApi\Serializer\CollectionNormalizer;
use ApiPlatform\Core\JsonApi\Serializer\ConstraintViolationListNormalizer;
use ApiPlatform\Core\JsonApi\Serializer\EntrypointNormalizer;
use ApiPlatform\Core\JsonApi\Serializer\ErrorNormalizer;
use ApiPlatform\Core\JsonApi\Serializer\ItemNormalizer;
use ApiPlatform\Core\JsonApi\Serializer\ObjectNormalizer;
use ApiPlatform\Core\JsonApi\Serializer\ReservedAttributeNameConverter;
use ApiPlatform\Core\Serializer\JsonEncoder;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set('api_platform.jsonapi.encoder', JsonEncoder::class)
            ->args(['jsonapi'])
            ->tag('serializer.encoder')

        ->set('api_platform.jsonapi.name_converter.reserved_attribute_name', ReservedAttributeNameConverter::class)
            ->args([ref('api_platform.name_converter')->ignoreOnInvalid()])

        ->set('api_platform.jsonapi.normalizer.entrypoint', EntrypointNormalizer::class)
            ->args([ref('api_platform.metadata.resource.metadata_factory'), ref('api_platform.iri_converter'), ref('api_platform.router')])
            ->tag('serializer.normalizer', ['priority' => -800])

        ->set('api_platform.jsonapi.normalizer.collection', CollectionNormalizer::class)
            ->args([ref('api_platform.resource_class_resolver'), param('api_platform.collection.pagination.page_parameter_name')])
            ->tag('serializer.normalizer', ['priority' => -985])

        ->set('api_platform.jsonapi.normalizer.item', ItemNormalizer::class)
            ->args([ref('api_platform.metadata.property.name_collection_factory'), ref('api_platform.metadata.property.metadata_factory'), ref('api_platform.iri_converter'), ref('api_platform.resource_class_resolver'), ref('api_platform.property_accessor'), ref('api_platform.jsonapi.name_converter.reserved_attribute_name'), ref('api_platform.metadata.resource.metadata_factory'), [], tagged_iterator('api_platform.data_transformer')->ignoreOnInvalid(), 'false'])
            ->tag('serializer.normalizer', ['priority' => -890])

        ->set('api_platform.jsonapi.normalizer.object', ObjectNormalizer::class)
            ->args([ref('serializer.normalizer.object'), ref('api_platform.iri_converter'), ref('api_platform.resource_class_resolver'), ref('api_platform.metadata.resource.metadata_factory')])
            ->tag('serializer.normalizer', ['priority' => -995])

        ->set('api_platform.jsonapi.normalizer.constraint_violation_list', ConstraintViolationListNormalizer::class)
            ->args([ref('api_platform.metadata.property.metadata_factory'), ref('api_platform.jsonapi.name_converter.reserved_attribute_name')])
            ->tag('serializer.normalizer', ['priority' => -780])

        ->set('api_platform.jsonapi.normalizer.error', ErrorNormalizer::class)
            ->args([param('kernel.debug')])
            ->tag('serializer.normalizer', ['priority' => -790])

        ->set('api_platform.jsonapi.listener.request.transform_pagination_parameters', TransformPaginationParametersListener::class)
            ->tag('kernel.event_listener', ['event' => 'kernel.request', 'method' => 'onKernelRequest', 'priority' => 5])

        ->set('api_platform.jsonapi.listener.request.transform_sorting_parameters', TransformSortingParametersListener::class)
            ->args([param('api_platform.collection.order_parameter_name')])
            ->tag('kernel.event_listener', ['event' => 'kernel.request', 'method' => 'onKernelRequest', 'priority' => 5])

        ->set('api_platform.jsonapi.listener.request.transform_fieldsets_parameters', TransformFieldsetsParametersListener::class)
            ->args([ref('api_platform.metadata.resource.metadata_factory')])
            ->tag('kernel.event_listener', ['event' => 'kernel.request', 'method' => 'onKernelRequest', 'priority' => 5])

        ->set('api_platform.jsonapi.listener.request.transform_filtering_parameters', TransformFilteringParametersListener::class)
            ->tag('kernel.event_listener', ['event' => 'kernel.request', 'method' => 'onKernelRequest', 'priority' => 5]);
};
