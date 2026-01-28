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

use ApiPlatform\State\ParameterProvider\ReadLinkParameterProvider;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('api_platform.state_provider.read_link', ReadLinkParameterProvider::class)
        ->args([
            service('api_platform.state_provider.locator'),
            service('api_platform.metadata.resource.metadata_collection_factory'),
        ])
        ->tag('api_platform.parameter_provider', ['key' => 'ApiPlatform\State\ParameterProvider\ReadLinkParameterProvider', 'priority' => -890]);
};
