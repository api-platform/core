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

use ApiPlatform\Symfony\Validator\State\ValidateProvider;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('api_platform.graphql.state_provider.validate', ValidateProvider::class)
        ->decorate('api_platform.graphql.state_provider', null, 200)
        ->args([
            service('api_platform.graphql.state_provider.validate.inner'),
            service('api_platform.validator'),
        ]);

    $services->set('api_platform.graphql.state_provider.validate_after_resolver', ValidateProvider::class)
        ->decorate('api_platform.graphql.state_provider', null, 180)
        ->args([
            service('api_platform.graphql.state_provider.validate_after_resolver.inner'),
            service('api_platform.validator'),
            'canValidateAfterResolver',
        ]);
};
