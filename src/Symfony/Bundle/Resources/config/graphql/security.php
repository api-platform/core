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

use ApiPlatform\Symfony\Security\State\AccessCheckerProvider;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('api_platform.graphql.state_provider.access_checker', AccessCheckerProvider::class)
        ->decorate('api_platform.graphql.state_provider.read', null, 0)
        ->args([
            service('api_platform.graphql.state_provider.access_checker.inner'),
            service('api_platform.security.resource_access_checker'),
        ]);

    $services->set('api_platform.graphql.state_provider.access_checker.post_deserialize', AccessCheckerProvider::class)
        ->decorate('api_platform.graphql.state_provider.denormalizer', null, 0)
        ->args([
            service('api_platform.graphql.state_provider.access_checker.post_deserialize.inner'),
            service('api_platform.security.resource_access_checker'),
            'post_denormalize',
        ]);

    $services->set('api_platform.graphql.state_provider.access_checker.post_validate', AccessCheckerProvider::class)
        ->decorate('api_platform.graphql.state_provider.validate', null, 0)
        ->args([
            service('api_platform.graphql.state_provider.access_checker.post_validate.inner'),
            service('api_platform.security.resource_access_checker'),
            'post_validate',
        ]);

    $services->set('api_platform.graphql.state_provider.access_checker.after_resolver', AccessCheckerProvider::class)
        ->decorate('api_platform.graphql.state_provider', null, 170)
        ->args([
            service('api_platform.graphql.state_provider.access_checker.after_resolver.inner'),
            service('api_platform.security.resource_access_checker'),
            'after_resolver',
        ]);
};
