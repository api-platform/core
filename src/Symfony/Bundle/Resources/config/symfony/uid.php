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

    $services->set('api_platform.symfony.uri_variables.transformer.ulid', 'ApiPlatform\Symfony\UriVariableTransformer\UlidUriVariableTransformer')
        ->tag('api_platform.uri_variables.transformer');

    $services->set('api_platform.symfony.uri_variables.transformer.uuid', 'ApiPlatform\Symfony\UriVariableTransformer\UuidUriVariableTransformer')
        ->tag('api_platform.uri_variables.transformer');
};
