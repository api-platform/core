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

    $services->set('api_platform.openapi.normalizer', 'ApiPlatform\OpenApi\Serializer\OpenApiNormalizer')
        ->args([inline_service('Symfony\Component\Serializer\Serializer')->arg(0, [inline_service('Symfony\Component\Serializer\Normalizer\ObjectNormalizer')->arg(0, null)->arg(1, null)->arg(2, service('api_platform.property_accessor'))->arg(3, service('api_platform.property_info'))])->arg(1, [service('serializer.encoder.json')])])
        ->tag('serializer.normalizer', ['priority' => -795]);

    $services->alias('ApiPlatform\OpenApi\Serializer\OpenApiNormalizer', 'api_platform.openapi.normalizer');

    $services->set('api_platform.openapi.provider', 'ApiPlatform\OpenApi\State\OpenApiProvider')
        ->args([service('api_platform.openapi.factory')])
        ->tag('api_platform.state_provider', ['priority' => -100, 'key' => 'ApiPlatform\OpenApi\State\OpenApiProvider'])
        ->tag('api_platform.state_provider', ['priority' => -100, 'key' => 'api_platform.openapi.provider']);

    $services->set('api_platform.openapi.serializer_context_builder', 'ApiPlatform\OpenApi\Serializer\SerializerContextBuilder')
        ->decorate('api_platform.serializer.context_builder', null, 0)
        ->args([service('api_platform.openapi.serializer_context_builder.inner')]);

    $services->set('api_platform.openapi.options', 'ApiPlatform\OpenApi\Options')
        ->args([
            '%api_platform.title%',
            '%api_platform.description%',
            '%api_platform.version%',
            '%api_platform.oauth.enabled%',
            '%api_platform.oauth.type%',
            '%api_platform.oauth.flow%',
            '%api_platform.oauth.tokenUrl%',
            '%api_platform.oauth.authorizationUrl%',
            '%api_platform.oauth.refreshUrl%',
            '%api_platform.oauth.scopes%',
            '%api_platform.swagger.api_keys%',
            '%api_platform.openapi.contact.name%',
            '%api_platform.openapi.contact.url%',
            '%api_platform.openapi.contact.email%',
            '%api_platform.openapi.termsOfService%',
            '%api_platform.openapi.license.name%',
            '%api_platform.openapi.license.url%',
            '%api_platform.openapi.overrideResponses%',
            '%api_platform.swagger.persist_authorization%',
            '%api_platform.swagger.http_auth%',
            '%api_platform.openapi.tags%',
            '%api_platform.openapi.errorResourceClass%',
            '%api_platform.openapi.validationErrorResourceClass%',
            '%api_platform.openapi.license.identifier%',
        ]);

    $services->alias('ApiPlatform\OpenApi\Options', 'api_platform.openapi.options');

    $services->set('api_platform.openapi.command', 'ApiPlatform\OpenApi\Command\OpenApiCommand')
        ->args([
            service('api_platform.openapi.factory'),
            service('api_platform.serializer'),
        ])
        ->tag('console.command');

    $services->set('api_platform.openapi.normalizer.api_gateway', 'ApiPlatform\OpenApi\Serializer\ApiGatewayNormalizer')
        ->decorate('api_platform.openapi.normalizer', null, -1)
        ->args([service('api_platform.openapi.normalizer.api_gateway.inner')])
        ->tag('serializer.normalizer');

    $services->set('api_platform.openapi.normalizer.legacy', 'ApiPlatform\OpenApi\Serializer\LegacyOpenApiNormalizer')
        ->decorate('api_platform.openapi.normalizer.api_gateway', null, -2)
        ->args([service('api_platform.openapi.normalizer.legacy.inner')])
        ->tag('serializer.normalizer');

    $services->alias('ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface', 'api_platform.openapi.factory');

    $services->set('api_platform.openapi.factory', 'ApiPlatform\OpenApi\Factory\OpenApiFactory')
        ->args([
            service('api_platform.metadata.resource.name_collection_factory'),
            service('api_platform.metadata.resource.metadata_collection_factory'),
            service('api_platform.metadata.property.name_collection_factory'),
            service('api_platform.metadata.property.metadata_factory'),
            service('api_platform.json_schema.schema_factory'),
            service('api_platform.filter_locator'),
            '%api_platform.formats%',
            service('api_platform.openapi.options'),
            service('api_platform.pagination_options'),
            service('api_platform.router'),
            '%api_platform.error_formats%',
        ]);

    $services->set('api_platform.cache.openapi')
        ->parent('cache.system')
        ->tag('cache.pool');

    $services->set('api_platform.jsonopenapi.encoder', 'ApiPlatform\Serializer\JsonEncoder')
        ->args([
            'jsonopenapi',
            service('serializer.json.encoder')->nullOnInvalid(),
        ])
        ->tag('serializer.encoder');
};
