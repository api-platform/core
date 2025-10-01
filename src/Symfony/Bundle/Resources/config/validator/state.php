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

    $services->set('api_platform.state_provider.validate', 'ApiPlatform\Symfony\Validator\State\ValidateProvider')
        ->decorate('api_platform.state_provider.main', null, 200)
        ->args([
            service('api_platform.state_provider.validate.inner'),
            service('api_platform.validator'),
        ]);

    $services->set('api_platform.state_provider.parameter_validator', 'ApiPlatform\Symfony\Validator\State\ParameterValidatorProvider')
        ->public()
        ->decorate('api_platform.state_provider.main', null, 191)
        ->args([
            service('validator'),
            service('api_platform.state_provider.parameter_validator.inner'),
        ]);
};
