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

    $services->alias('api_platform.metadata.resource.metadata_collection_factory', 'api_platform.metadata.resource.metadata_collection_factory.attributes');

    $services->alias('ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface', 'api_platform.metadata.resource.metadata_collection_factory');

    $services->set('api_platform.metadata.resource.metadata_collection_factory.attributes', 'ApiPlatform\Metadata\Resource\Factory\AttributesResourceMetadataCollectionFactory')
        ->args([
            null,
            service('logger')->nullOnInvalid(),
            '%api_platform.defaults%',
            '%api_platform.graphql.enabled%',
        ]);

    $services->set('api_platform.metadata.resource.metadata_collection_factory.xml', 'ApiPlatform\Metadata\Resource\Factory\ExtractorResourceMetadataCollectionFactory')
        ->decorate('api_platform.metadata.resource.metadata_collection_factory', null, 800)
        ->args([
            service('api_platform.metadata.resource_extractor.xml'),
            service('api_platform.metadata.resource.metadata_collection_factory.xml.inner'),
            '%api_platform.defaults%',
            service('logger')->nullOnInvalid(),
            '%api_platform.graphql.enabled%',
        ]);

    $services->set('api_platform.metadata.resource.metadata_collection_factory.php_file', 'ApiPlatform\Metadata\Resource\Factory\PhpFileResourceMetadataCollectionFactory')
        ->decorate('api_platform.metadata.resource.metadata_collection_factory', null, 800)
        ->args([
            service('api_platform.metadata.resource_extractor.php_file'),
            service('api_platform.metadata.resource.metadata_collection_factory.php_file.inner'),
            service('logger')->nullOnInvalid(),
            '%api_platform.defaults%',
        ]);

    $services->set('api_platform.metadata.resource.metadata_collection_factory.mutator', 'ApiPlatform\Metadata\Resource\Factory\MutatorResourceMetadataCollectionFactory')
        ->decorate('api_platform.metadata.resource.metadata_collection_factory', null, 800)
        ->args([
            service('api_platform.metadata.mutator_collection.resource'),
            service('api_platform.metadata.mutator_collection.operation'),
            service('api_platform.metadata.resource.metadata_collection_factory.mutator.inner'),
        ]);

    $services->set('api_platform.metadata.resource.metadata_collection_factory.concerns', 'ApiPlatform\Metadata\Resource\Factory\ConcernsResourceMetadataCollectionFactory')
        ->decorate('api_platform.metadata.resource.metadata_collection_factory', null, 800)
        ->args([service('api_platform.metadata.resource.metadata_collection_factory.concerns.inner')]);

    $services->set('api_platform.metadata.resource.metadata_collection_factory.not_exposed_operation', 'ApiPlatform\Metadata\Resource\Factory\NotExposedOperationResourceMetadataCollectionFactory')
        ->decorate('api_platform.metadata.resource.metadata_collection_factory', null, 700)
        ->args([
            service('api_platform.metadata.resource.link_factory'),
            service('api_platform.metadata.resource.metadata_collection_factory.not_exposed_operation.inner'),
        ]);

    $services->set('api_platform.metadata.resource.metadata_collection_factory.backed_enum', 'ApiPlatform\Metadata\Resource\Factory\BackedEnumResourceMetadataCollectionFactory')
        ->decorate('api_platform.metadata.resource.metadata_collection_factory', null, 500)
        ->args([service('api_platform.metadata.resource.metadata_collection_factory.backed_enum.inner')]);

    $services->set('api_platform.metadata.resource.metadata_collection_factory.uri_template', 'ApiPlatform\Metadata\Resource\Factory\UriTemplateResourceMetadataCollectionFactory')
        ->decorate('api_platform.metadata.resource.metadata_collection_factory', null, 500)
        ->args([
            service('api_platform.metadata.resource.link_factory'),
            service('api_platform.path_segment_name_generator'),
            service('api_platform.metadata.resource.metadata_collection_factory.uri_template.inner'),
        ]);

    $services->set('api_platform.metadata.resource.metadata_collection_factory.main_controller', 'ApiPlatform\Metadata\Resource\Factory\MainControllerResourceMetadataCollectionFactory')
        ->decorate('api_platform.metadata.resource.metadata_collection_factory', null, 500)
        ->args([
            service('api_platform.metadata.resource.metadata_collection_factory.main_controller.inner'),
            '%api_platform.use_symfony_listeners%',
        ]);

    $services->set('api_platform.metadata.resource.metadata_collection_factory.link', 'ApiPlatform\Metadata\Resource\Factory\LinkResourceMetadataCollectionFactory')
        ->decorate('api_platform.metadata.resource.metadata_collection_factory', null, 500)
        ->args([
            service('api_platform.metadata.resource.link_factory'),
            service('api_platform.metadata.resource.metadata_collection_factory.link.inner'),
            '%api_platform.graphql.enabled%',
        ]);

    $services->set('api_platform.metadata.resource.metadata_collection_factory.operation_name', 'ApiPlatform\Metadata\Resource\Factory\OperationNameResourceMetadataCollectionFactory')
        ->decorate('api_platform.metadata.resource.metadata_collection_factory', null, 200)
        ->args([service('api_platform.metadata.resource.metadata_collection_factory.operation_name.inner')]);

    $services->set('api_platform.metadata.resource.metadata_collection_factory.input_output', 'ApiPlatform\Metadata\Resource\Factory\InputOutputResourceMetadataCollectionFactory')
        ->decorate('api_platform.metadata.resource.metadata_collection_factory', null, 200)
        ->args([service('api_platform.metadata.resource.metadata_collection_factory.input_output.inner')]);

    $services->set('api_platform.metadata.resource.metadata_collection_factory.formats', 'ApiPlatform\Metadata\Resource\Factory\FormatsResourceMetadataCollectionFactory')
        ->decorate('api_platform.metadata.resource.metadata_collection_factory', null, 200)
        ->args([
            service('api_platform.metadata.resource.metadata_collection_factory.formats.inner'),
            '%api_platform.formats%',
            '%api_platform.patch_formats%',
            '%api_platform.error_formats%',
        ]);

    $services->set('api_platform.metadata.resource.metadata_collection_factory.filters', 'ApiPlatform\Metadata\Resource\Factory\FiltersResourceMetadataCollectionFactory')
        ->decorate('api_platform.metadata.resource.metadata_collection_factory', null, 200)
        ->args([service('api_platform.metadata.resource.metadata_collection_factory.filters.inner')]);

    $services->set('api_platform.metadata.resource.metadata_collection_factory.alternate_uri', 'ApiPlatform\Metadata\Resource\Factory\AlternateUriResourceMetadataCollectionFactory')
        ->decorate('api_platform.metadata.resource.metadata_collection_factory', null, 200)
        ->args([service('api_platform.metadata.resource.metadata_collection_factory.alternate_uri.inner')]);

    $services->set('api_platform.metadata.resource.metadata_collection_factory.parameter', 'ApiPlatform\Metadata\Resource\Factory\ParameterResourceMetadataCollectionFactory')
        ->decorate('api_platform.metadata.resource.metadata_collection_factory', null, 1000)
        ->args([
            service('api_platform.metadata.property.name_collection_factory'),
            service('api_platform.metadata.property.metadata_factory'),
            service('api_platform.metadata.resource.metadata_collection_factory.parameter.inner'),
            service('api_platform.filter_locator')->ignoreOnInvalid(),
            service('api_platform.name_converter')->ignoreOnInvalid(),
            service('logger')->ignoreOnInvalid(),
        ]);

    $services->set('api_platform.metadata.resource.metadata_collection_factory.cached', 'ApiPlatform\Metadata\Resource\Factory\CachedResourceMetadataCollectionFactory')
        ->decorate('api_platform.metadata.resource.metadata_collection_factory', null, -10)
        ->args([
            service('api_platform.cache.metadata.resource_collection'),
            service('api_platform.metadata.resource.metadata_collection_factory.cached.inner'),
        ]);

    $services->set('api_platform.cache.metadata.resource_collection')
        ->parent('cache.system')
        ->tag('cache.pool');
};
