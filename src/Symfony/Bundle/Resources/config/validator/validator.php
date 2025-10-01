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

    $services->set('api_platform.validator', 'ApiPlatform\Symfony\Validator\Validator')
        ->args([
            service('validator'),
            tagged_locator('api_platform.validation_groups_generator'),
        ]);

    $services->alias('ApiPlatform\Validator\ValidatorInterface', 'api_platform.validator');

    $services->set('api_platform.validator.state.error_provider', 'ApiPlatform\Symfony\Validator\State\ErrorProvider')
        ->tag('api_platform.state_provider', ['key' => 'api_platform.validator.state.error_provider']);

    $services->set('api_platform.validator.metadata.resource.metadata_collection_factory.parameter', 'ApiPlatform\Validator\Metadata\Resource\Factory\ParameterValidationResourceMetadataCollectionFactory')
        ->decorate('api_platform.metadata.resource.metadata_collection_factory', null, 1000)
        ->args([
            service('api_platform.validator.metadata.resource.metadata_collection_factory.parameter.inner'),
            service('api_platform.filter_locator'),
        ]);
};
