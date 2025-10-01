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

    $services->set('api_platform.mercure.processor.add_link_header', 'ApiPlatform\Symfony\State\MercureLinkProcessor')
        ->decorate('api_platform.state_processor.respond', null, 400)
        ->args([
            service('api_platform.mercure.processor.add_link_header.inner'),
            service('Symfony\Component\Mercure\Discovery')->ignoreOnInvalid(),
        ]);
};
