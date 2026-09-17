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

use ApiPlatform\State\Processor\PaginationLinkProcessor;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('api_platform.state_processor.pagination_link', PaginationLinkProcessor::class)
        ->decorate('api_platform.state_processor.respond', null, 420)
        ->args([
            service('api_platform.state_processor.pagination_link.inner'),
            service('api_platform.pagination'),
        ]);
};
