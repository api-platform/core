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

    $services->set('api_platform.metadata.resource_extractor.xml', 'ApiPlatform\Metadata\Extractor\XmlResourceExtractor')
        ->args([
            [],
            service('service_container'),
        ]);

    $services->set('api_platform.metadata.property_extractor.xml', 'ApiPlatform\Metadata\Extractor\XmlPropertyExtractor')
        ->args([
            [],
            service('service_container'),
        ]);
};
