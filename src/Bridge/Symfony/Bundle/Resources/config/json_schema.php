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

use ApiPlatform\Core\JsonSchema\Command\JsonSchemaGenerateCommand;
use ApiPlatform\Core\JsonSchema\SchemaFactory;
use ApiPlatform\Core\JsonSchema\SchemaFactoryInterface;
use ApiPlatform\Core\JsonSchema\TypeFactory;
use ApiPlatform\Core\JsonSchema\TypeFactoryInterface;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set('api_platform.json_schema.type_factory', TypeFactory::class)
            ->args([ref('api_platform.resource_class_resolver')])
            ->call('setSchemaFactory', [ref('api_platform.json_schema.schema_factory')])
        ->alias(TypeFactoryInterface::class, 'api_platform.json_schema.type_factory')

        ->set('api_platform.json_schema.schema_factory', SchemaFactory::class)
            ->args([ref('api_platform.json_schema.type_factory'), ref('api_platform.metadata.resource.metadata_factory'), ref('api_platform.metadata.property.name_collection_factory'), ref('api_platform.metadata.property.metadata_factory'), ref('api_platform.name_converter')->ignoreOnInvalid(), ref('api_platform.resource_class_resolver')])
        ->alias(SchemaFactoryInterface::class, 'api_platform.json_schema.schema_factory')

        ->set('api_platform.json_schema.json_schema_generate_command', JsonSchemaGenerateCommand::class)
            ->args([ref('api_platform.json_schema.schema_factory'), param('api_platform.formats')])
            ->tag('console.command');
};
