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

use ApiPlatform\Core\Bridge\Symfony\Bundle\Action\SwaggerUiAction as SwaggerUiActionDeprecated;
use ApiPlatform\Core\Bridge\Symfony\Bundle\EventListener\SwaggerUiListener;
use ApiPlatform\Core\Bridge\Symfony\Bundle\SwaggerUi\SwaggerUiAction;
use ApiPlatform\Core\Bridge\Symfony\Bundle\SwaggerUi\SwaggerUiContext;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set('api_platform.swagger.listener.ui', SwaggerUiListener::class)
            ->tag('kernel.event_listener', ['event' => 'kernel.request', 'method' => 'onKernelRequest'])
        ->alias('api_platform.swagger_ui.listener', 'api_platform.swagger.listener.ui')

        ->set('api_platform.swagger.action.ui', SwaggerUiActionDeprecated::class)
            ->args([
                ref('api_platform.metadata.resource.name_collection_factory'),
                ref('api_platform.metadata.resource.metadata_factory'),
                ref('api_platform.serializer'),
                ref('twig'),
                ref('router'),
                param('api_platform.title'),
                param('api_platform.description'),
                param('api_platform.version'),
                param('api_platform.formats'),
                param('api_platform.oauth.enabled'),
                param('api_platform.oauth.clientId'),
                param('api_platform.oauth.clientSecret'),
                param('api_platform.oauth.type'),
                param('api_platform.oauth.flow'),
                param('api_platform.oauth.tokenUrl'),
                param('api_platform.oauth.authorizationUrl'),
                param('api_platform.oauth.scopes'),
                param('api_platform.show_webby'),
                param('api_platform.enable_swagger_ui'),
                param('api_platform.enable_re_doc'),
                param('api_platform.graphql.enabled'),
                param('api_platform.graphql.graphiql.enabled'),
                param('api_platform.graphql.graphql_playground.enabled'),
                param('api_platform.swagger.versions'),
                ref('api_platform.swagger_ui.action'),
            ])
            ->public()

        ->set('api_platform.swagger_ui.context', SwaggerUiContext::class)
            ->args([
                param('api_platform.enable_swagger_ui'),
                param('api_platform.show_webby'),
                param('api_platform.enable_re_doc'),
                param('api_platform.graphql.enabled'),
                param('api_platform.graphql.graphiql.enabled'),
                param('api_platform.graphql.graphql_playground.enabled'),
            ])

        ->set('api_platform.swagger_ui.action', SwaggerUiAction::class)
            ->args([
                ref('api_platform.metadata.resource.metadata_factory'),
                ref('twig'),
                ref('router'),
                ref('api_platform.serializer'),
                ref('api_platform.openapi.factory'),
                ref('api_platform.openapi.options'),
                ref('api_platform.swagger_ui.context'),
                param('api_platform.formats'),
                param('api_platform.oauth.clientId'),
                param('api_platform.oauth.clientSecret'),
            ]);
};
