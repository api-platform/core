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

use ApiPlatform\RamseyUuid\Serializer\UuidDenormalizer;
use ApiPlatform\RamseyUuid\UriVariableTransformer\UuidUriVariableTransformer;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('api_platform.serializer.uuid_denormalizer', UuidDenormalizer::class)
        ->tag('serializer.normalizer');

    $services->set('api_platform.ramsey_uuid.uri_variables.transformer.uuid', UuidUriVariableTransformer::class)
        ->tag('api_platform.uri_variables.transformer', ['priority' => -100]);
};
