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

use ApiPlatform\State\Processor\ObjectMapperInputProcessor;
use ApiPlatform\State\Processor\ObjectMapperOutputProcessor;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('api_platform.state_processor.object_mapper_input', ObjectMapperInputProcessor::class)
        ->decorate('api_platform.state_processor.main', null, 50)
        ->args([
            service('api_platform.object_mapper')->nullOnInvalid(),
            service('api_platform.state_processor.object_mapper_input.inner'),
        ]);

    $services->set('api_platform.state_processor.object_mapper_output', ObjectMapperOutputProcessor::class)
        ->decorate('api_platform.state_processor.main', null, 150)
        ->args([
            service('api_platform.object_mapper')->nullOnInvalid(),
            service('api_platform.state_processor.object_mapper_output.inner'),
        ]);
};
