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

use ApiPlatform\Symfony\Doctrine\GraphQl\ClearManagersResolverFactory;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('api_platform.doctrine.orm.graphql.resolver_factory.clear_managers', ClearManagersResolverFactory::class)
        ->decorate('api_platform.graphql.resolver.factory', null, 0)
        ->args([
            service('api_platform.doctrine.orm.graphql.resolver_factory.clear_managers.inner'),
            service('doctrine'),
        ]);
};
