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

use ApiPlatform\Core\Bridge\Symfony\Bundle\Action\SwaggerUiAction;
use ApiPlatform\Core\Bridge\Symfony\Bundle\EventListener\SwaggerUiListener;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set('api_platform.swagger.listener.ui', SwaggerUiListener::class)
            ->tag('kernel.event_listener', ['event' => 'kernel.request', 'method' => 'onKernelRequest'])
        ->set('api_platform.swagger.action.ui', SwaggerUiAction::class)
            ->args([service('api_platform.metadata.resource.name_collection_factory'), service('api_platform.metadata.resource.metadata_factory'), service('api_platform.serializer'), service('twig'), service('router'), param('api_platform.title'), param('api_platform.description'), param('api_platform.version'), param('api_platform.formats'), param('api_platform.oauth.enabled'), param('api_platform.oauth.clientId'), param('api_platform.oauth.clientSecret'), param('api_platform.oauth.type'), param('api_platform.oauth.flow'), param('api_platform.oauth.tokenUrl'), param('api_platform.oauth.authorizationUrl'), param('api_platform.oauth.scopes'), param('api_platform.show_webby'), param('api_platform.enable_swagger_ui'), param('api_platform.enable_re_doc'), param('api_platform.graphql.enabled'), param('api_platform.graphql.graphiql.enabled'), param('api_platform.graphql.graphql_playground.enabled'), param('api_platform.swagger.versions')])
            ->public();
};
