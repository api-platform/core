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

use ApiPlatform\Documentation\ApiCatalogFactory;
use ApiPlatform\Symfony\Action\ApiCatalogAction;
use ApiPlatform\Symfony\Action\DocumentationAction;
use ApiPlatform\Symfony\Action\EntrypointAction;
use ApiPlatform\Symfony\Controller\MainController;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('api_platform.symfony.main_controller', MainController::class)
        ->public()
        ->args([
            service('api_platform.metadata.resource.metadata_collection_factory'),
            service('api_platform.state_provider.main'),
            service('api_platform.state_processor.main'),
            service('api_platform.uri_variables.converter')->ignoreOnInvalid(),
            service('logger')->ignoreOnInvalid(),
        ]);

    $services->set('api_platform.action.entrypoint', EntrypointAction::class)
        ->public()
        ->args([
            service('api_platform.metadata.resource.name_collection_factory'),
            service('api_platform.state_provider.main'),
            service('api_platform.state_processor.main'),
            '%api_platform.entrypoint_formats%',
            service('api_platform.documentation.api_catalog_factory'),
        ]);

    $services->set('api_platform.documentation.api_catalog_factory', ApiCatalogFactory::class)
        ->args([
            service('api_platform.metadata.resource.name_collection_factory'),
            service('api_platform.metadata.resource.metadata_collection_factory'),
            service('api_platform.iri_converter'),
            service('api_platform.router'),
            '%api_platform.docs_formats%',
            '%api_platform.enable_docs%',
        ]);

    $services->set('api_platform.action.api_catalog', ApiCatalogAction::class)
        ->public()
        ->args([service('api_platform.documentation.api_catalog_factory')]);

    $services->set('api_platform.action.documentation', DocumentationAction::class)
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
            '%api_platform.enable_docs%',
            '%api_platform.enable_re_doc%',
            '%api_platform.enable_scalar%',
        ]);
};
