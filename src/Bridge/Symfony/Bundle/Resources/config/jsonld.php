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

use ApiPlatform\Core\JsonLd\Action\ContextAction;
use ApiPlatform\Core\JsonLd\ContextBuilder;
use ApiPlatform\Core\JsonLd\Serializer\ItemNormalizer;
use ApiPlatform\Core\JsonLd\Serializer\ObjectNormalizer;
use ApiPlatform\Core\Serializer\JsonEncoder;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set('api_platform.jsonld.context_builder', ContextBuilder::class)
            ->args([ref('api_platform.metadata.resource.name_collection_factory'), ref('api_platform.metadata.resource.metadata_factory'), ref('api_platform.metadata.property.name_collection_factory'), ref('api_platform.metadata.property.metadata_factory'), ref('api_platform.router')])

        ->set('api_platform.jsonld.normalizer.item', ItemNormalizer::class)
            ->args([ref('api_platform.metadata.resource.metadata_factory'), ref('api_platform.metadata.property.name_collection_factory'), ref('api_platform.metadata.property.metadata_factory'), ref('api_platform.iri_converter'), ref('api_platform.resource_class_resolver'), ref('api_platform.jsonld.context_builder'), ref('api_platform.property_accessor'), ref('api_platform.name_converter')->ignoreOnInvalid(), ref('serializer.mapping.class_metadata_factory')->ignoreOnInvalid(), [], tagged_iterator('api_platform.data_transformer'), ref('api_platform.security.resource_access_checker')->ignoreOnInvalid()])
            ->tag('serializer.normalizer', ['priority' => -890])

        ->set('api_platform.jsonld.normalizer.object', ObjectNormalizer::class)
            ->args([ref('serializer.normalizer.object'), ref('api_platform.iri_converter'), ref('api_platform.jsonld.context_builder')])
            ->tag('serializer.normalizer', ['priority' => -995])

        ->set('api_platform.jsonld.encoder', JsonEncoder::class)
            ->args(['jsonld'])
            ->tag('serializer.encoder')

        ->set('api_platform.jsonld.action.context', ContextAction::class)
            ->args([ref('api_platform.jsonld.context_builder'), ref('api_platform.metadata.resource.name_collection_factory'), ref('api_platform.metadata.resource.metadata_factory')])
            ->public();
};
