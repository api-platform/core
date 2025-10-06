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

    $services->set('api_platform.serializer.uuid_denormalizer', 'ApiPlatform\RamseyUuid\Serializer\UuidDenormalizer')
        ->tag('serializer.normalizer');

    $services->set('api_platform.ramsey_uuid.uri_variables.transformer.uuid', 'ApiPlatform\RamseyUuid\UriVariableTransformer\UuidUriVariableTransformer')
        ->tag('api_platform.uri_variables.transformer', ['priority' => -100]);
};
