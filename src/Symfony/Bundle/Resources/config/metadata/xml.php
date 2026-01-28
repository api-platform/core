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

use ApiPlatform\Metadata\Extractor\XmlPropertyExtractor;
use ApiPlatform\Metadata\Extractor\XmlResourceExtractor;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('api_platform.metadata.resource_extractor.xml', XmlResourceExtractor::class)
        ->args([
            [],
            service('service_container'),
        ]);

    $services->set('api_platform.metadata.property_extractor.xml', XmlPropertyExtractor::class)
        ->args([
            [],
            service('service_container'),
        ]);
};
