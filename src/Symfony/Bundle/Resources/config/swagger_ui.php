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

    $services->set('api_platform.swagger_ui.context', 'ApiPlatform\Symfony\Bundle\SwaggerUi\SwaggerUiContext')
        ->args([
            '%api_platform.enable_swagger_ui%',
            '%api_platform.show_webby%',
            '%api_platform.enable_re_doc%',
            '%api_platform.graphql.enabled%',
            '%api_platform.graphql.graphiql.enabled%',
            '%api_platform.asset_package%',
            '%api_platform.swagger_ui.extra_configuration%',
        ]);

    $services->set('api_platform.swagger_ui.processor', 'ApiPlatform\Symfony\Bundle\SwaggerUi\SwaggerUiProcessor')
        ->args([
            service('twig')->nullOnInvalid(),
            service('router'),
            service('api_platform.serializer'),
            service('api_platform.openapi.options'),
            service('api_platform.swagger_ui.context'),
            '%api_platform.docs_formats%',
            '%api_platform.oauth.clientId%',
            '%api_platform.oauth.clientSecret%',
            '%api_platform.oauth.pkce%',
        ])
        ->tag('api_platform.state_processor', ['priority' => -100, 'key' => 'api_platform.swagger_ui.processor']);
};
