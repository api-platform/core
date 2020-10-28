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

use ApiPlatform\Core\Bridge\Symfony\Bundle\Command\OpenApiCommand;
use ApiPlatform\Core\OpenApi\Factory\OpenApiFactory;
use ApiPlatform\Core\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\Core\OpenApi\Options;
use ApiPlatform\Core\OpenApi\Serializer\OpenApiNormalizer;
use ApiPlatform\Core\Swagger\Serializer\ApiGatewayNormalizer;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set('api_platform.openapi.normalizer', OpenApiNormalizer::class)
            ->args([ref('serializer.normalizer.object')])
            ->tag('serializer.normalizer', ['priority' => -785])

        ->alias(OpenApiNormalizer::class, 'api_platform.openapi.normalizer')

        ->set('api_platform.openapi.options', Options::class)
            ->args([param('api_platform.title'), param('api_platform.description'), param('api_platform.version'), param('api_platform.oauth.enabled'), param('api_platform.oauth.type'), param('api_platform.oauth.flow'), param('api_platform.oauth.tokenUrl'), param('api_platform.oauth.authorizationUrl'), param('api_platform.oauth.refreshUrl'), param('api_platform.oauth.scopes'), param('api_platform.swagger.api_keys')])

        ->alias(Options::class, 'api_platform.openapi.options')

        ->set('api_platform.openapi.factory', OpenApiFactory::class)
            ->args([ref('api_platform.metadata.resource.name_collection_factory'), ref('api_platform.metadata.resource.metadata_factory'), ref('api_platform.metadata.property.name_collection_factory'), ref('api_platform.metadata.property.metadata_factory'), ref('api_platform.json_schema.schema_factory'), ref('api_platform.json_schema.type_factory'), ref('api_platform.operation_path_resolver'), ref('api_platform.filter_locator'), ref('api_platform.subresource_operation_factory'), param('api_platform.formats'), ref('api_platform.openapi.options'), ref('api_platform.pagination_options')])

        ->alias(OpenApiFactoryInterface::class, 'api_platform.openapi.factory')

        ->set('api_platform.openapi.command', OpenApiCommand::class)
            ->args([ref('api_platform.openapi.factory'), ref('api_platform.serializer')])
            ->tag('console.command')

        ->set('api_platform.openapi.normalizer.api_gateway', ApiGatewayNormalizer::class)
            ->decorate('api_platform.openapi.normalizer', null, -1)
            ->args([ref('api_platform.openapi.normalizer.api_gateway.inner')])
            ->tag('serializer.normalizer', ['priority' => -780]);
};
