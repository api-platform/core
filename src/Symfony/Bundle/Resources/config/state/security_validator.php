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

    $services->set('api_platform.state_provider.access_checker.post_validate', 'ApiPlatform\Symfony\Security\State\AccessCheckerProvider')
        ->decorate('api_platform.state_provider.validate', null, 0)
        ->args([
            service('api_platform.state_provider.access_checker.post_validate.inner'),
            service('api_platform.security.resource_access_checker'),
            'post_validate',
        ]);
};
