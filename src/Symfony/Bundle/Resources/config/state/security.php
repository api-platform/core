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

use ApiPlatform\State\Provider\SecurityParameterProvider;
use ApiPlatform\Symfony\Security\State\AccessCheckerProvider;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('api_platform.state_provider.access_checker', AccessCheckerProvider::class)
        ->decorate('api_platform.state_provider.read', null, 0)
        ->args([
            service('api_platform.state_provider.access_checker.inner'),
            service('api_platform.security.resource_access_checker'),
        ]);

    $services->set('api_platform.state_provider.access_checker.post_deserialize', AccessCheckerProvider::class)
        ->decorate('api_platform.state_provider.deserialize', null, 0)
        ->args([
            service('api_platform.state_provider.access_checker.post_deserialize.inner'),
            service('api_platform.security.resource_access_checker'),
            'post_denormalize',
        ]);

    $services->set('api_platform.state_provider.security_parameter', SecurityParameterProvider::class)
        ->decorate('api_platform.state_provider.access_checker', null, 0)
        ->args([
            service('api_platform.state_provider.security_parameter.inner'),
            service('api_platform.security.resource_access_checker'),
        ]);

    $services->set('api_platform.state_provider.access_checker.pre_read', AccessCheckerProvider::class)
        ->decorate('api_platform.state_provider.read', null, 10)
        ->args([
            service('api_platform.state_provider.access_checker.pre_read.inner'),
            service('api_platform.security.resource_access_checker'),
            'pre_read',
        ]);
};
