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

    $services->set('api_platform.json_schema.schema_factory', 'ApiPlatform\JsonSchema\SchemaFactory')
        ->args([
            service('api_platform.metadata.resource.metadata_collection_factory'),
            service('api_platform.metadata.property.name_collection_factory'),
            service('api_platform.metadata.property.metadata_factory'),
            service('api_platform.name_converter')->ignoreOnInvalid(),
            service('api_platform.resource_class_resolver'),
            [],
            service('api_platform.json_schema.definition_name_factory')->ignoreOnInvalid(),
        ]);

    $services->alias('ApiPlatform\JsonSchema\SchemaFactoryInterface', 'api_platform.json_schema.schema_factory');

    $services->set('api_platform.json_schema.json_schema_generate_command', 'ApiPlatform\JsonSchema\Command\JsonSchemaGenerateCommand')
        ->args([
            service('api_platform.json_schema.schema_factory'),
            '%api_platform.formats%',
        ])
        ->tag('console.command');

    $services->set('api_platform.json_schema.metadata.property.metadata_factory.schema', 'ApiPlatform\JsonSchema\Metadata\Property\Factory\SchemaPropertyMetadataFactory')
        ->decorate('api_platform.metadata.property.metadata_factory', null, 10)
        ->args([
            service('api_platform.resource_class_resolver'),
            service('api_platform.json_schema.metadata.property.metadata_factory.schema.inner'),
        ]);

    $services->set('api_platform.json_schema.backward_compatible_schema_factory', 'ApiPlatform\JsonSchema\BackwardCompatibleSchemaFactory')
        ->decorate('api_platform.json_schema.schema_factory', null, -2)
        ->args([service('api_platform.json_schema.backward_compatible_schema_factory.inner')]);

    $services->set('api_platform.json_schema.definition_name_factory', 'ApiPlatform\JsonSchema\DefinitionNameFactory');
};
