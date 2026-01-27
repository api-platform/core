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

use ApiPlatform\Symfony\Bundle\SwaggerUi\SwaggerUiProvider;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('api_platform.swagger_ui.documentation.provider', SwaggerUiProvider::class)
        ->decorate('api_platform.state_provider.documentation.read', null, 0)
        ->args([
            service('api_platform.swagger_ui.documentation.provider.inner'),
            service('api_platform.openapi.factory'),
        ]);
};
