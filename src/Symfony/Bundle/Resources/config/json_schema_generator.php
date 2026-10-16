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

use ApiPlatform\JsonSchema\Generator\ApiPlatformDefinitionPolicy;
use ApiPlatform\JsonSchema\Generator\ConfigurationFactory;
use ApiPlatform\JsonSchema\Generator\GeneratorSchemaFactory;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('api_platform.json_schema.generator.definition_policy', ApiPlatformDefinitionPolicy::class)
        ->args([
            service('api_platform.metadata.resource.metadata_collection_factory'),
            service('api_platform.resource_class_resolver'),
            service('api_platform.metadata.property.metadata_factory'),
        ]);

    $services->set('api_platform.json_schema.generator.configuration_factory', ConfigurationFactory::class)
        ->args([
            service('api_platform.metadata.resource.metadata_collection_factory'),
            service('api_platform.resource_class_resolver'),
            service('api_platform.json_schema.generator.definition_policy'),
        ]);

    $services->set('api_platform.json_schema.schema_factory', GeneratorSchemaFactory::class)
        ->args([
            service('api_platform.json_schema.generator.configuration_factory'),
            service('api_platform.json_schema.generator.definition_policy'),
            service('api_platform.metadata.property.name_collection_factory'),
            service('api_platform.metadata.property.metadata_factory'),
            service('api_platform.resource_class_resolver'),
            service('api_platform.name_converter')->ignoreOnInvalid(),
            tagged_iterator('api_platform.json_schema.definition_processor'),
        ]);
};
