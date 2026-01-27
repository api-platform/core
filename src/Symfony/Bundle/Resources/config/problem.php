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

use ApiPlatform\Serializer\JsonEncoder;
use ApiPlatform\Symfony\Validator\Serializer\ValidationExceptionNormalizer;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('api_platform.problem.encoder', JsonEncoder::class)
        ->args(['jsonproblem'])
        ->tag('serializer.encoder');

    $services->set('api_platform.problem.normalizer.validation_exception', ValidationExceptionNormalizer::class)
        ->args([
            service('api_platform.serializer.normalizer.item'),
            service('api_platform.name_converter')->ignoreOnInvalid(),
        ])
        ->tag('serializer.normalizer', ['priority' => -800]);
};
