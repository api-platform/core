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

    $services->set('api_platform.symfony.main_controller', 'ApiPlatform\Symfony\Controller\MainController')
        ->public()
        ->args([
            service('api_platform.metadata.resource.metadata_collection_factory'),
            service('api_platform.state_provider.main'),
            service('api_platform.state_processor.main'),
            service('api_platform.uri_variables.converter')->ignoreOnInvalid(),
            service('logger')->ignoreOnInvalid(),
        ]);

    $services->set('api_platform.action.entrypoint', 'ApiPlatform\Symfony\Action\EntrypointAction')
        ->public()
        ->args([
            service('api_platform.metadata.resource.name_collection_factory'),
            service('api_platform.state_provider.main'),
            service('api_platform.state_processor.main'),
            '%api_platform.docs_formats%',
        ]);

    $services->set('api_platform.action.documentation', 'ApiPlatform\Symfony\Action\DocumentationAction')
        ->public()
        ->args([
            service('api_platform.metadata.resource.name_collection_factory'),
            '%api_platform.title%',
            '%api_platform.description%',
            '%api_platform.version%',
            service('api_platform.openapi.factory')->nullOnInvalid(),
            service('api_platform.state_provider.main'),
            service('api_platform.state_processor.main'),
            service('api_platform.negotiator')->nullOnInvalid(),
            '%api_platform.docs_formats%',
            '%api_platform.enable_swagger_ui%',
        ]);
};
