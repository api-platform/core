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

use ApiPlatform\State\ParameterProvider\IriConverterParameterProvider;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('api_platform.state_provider.parameter.iri_converter', IriConverterParameterProvider::class)
        ->args([
            service('api_platform.iri_converter'),
            service('logger')->ignoreOnInvalid(),
        ])
        ->tag('api_platform.parameter_provider', ['key' => 'ApiPlatform\State\ParameterProvider\IriConverterParameterProvider', 'priority' => -895]);
};
