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
        ->args([
            null,
            service('api_platform.validator'),
        ]);

    $services->set('api_platform.listener.view.validate', 'ApiPlatform\Symfony\EventListener\ValidateListener')
        ->args([
            service('api_platform.state_provider.validate'),
            service('api_platform.metadata.resource.metadata_collection_factory'),
        ])
        ->tag('kernel.event_listener', ['event' => 'kernel.view', 'method' => 'onKernelView', 'priority' => 64]);

    $services->set('api_platform.state_provider.parameter_validator', 'ApiPlatform\Symfony\Validator\State\ParameterValidatorProvider')
        ->public()
        ->decorate('api_platform.state_provider.read', null, 110)
        ->args([
            service('validator'),
            service('api_platform.state_provider.parameter_validator.inner'),
        ]);
};
