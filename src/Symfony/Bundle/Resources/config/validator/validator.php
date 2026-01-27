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

use ApiPlatform\Symfony\Validator\State\ErrorProvider;
use ApiPlatform\Symfony\Validator\Validator;
use ApiPlatform\Validator\Metadata\Resource\Factory\ParameterValidationResourceMetadataCollectionFactory;
use ApiPlatform\Validator\ValidatorInterface;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('api_platform.validator', Validator::class)
        ->args([
            service('validator'),
            tagged_locator('api_platform.validation_groups_generator'),
        ]);

    $services->alias(ValidatorInterface::class, 'api_platform.validator');

    $services->set('api_platform.validator.state.error_provider', ErrorProvider::class)
        ->tag('api_platform.state_provider', ['key' => 'api_platform.validator.state.error_provider']);

    $services->set('api_platform.validator.metadata.resource.metadata_collection_factory.parameter', ParameterValidationResourceMetadataCollectionFactory::class)
        ->decorate('api_platform.metadata.resource.metadata_collection_factory', null, 1000)
        ->args([
            service('api_platform.validator.metadata.resource.metadata_collection_factory.parameter.inner'),
            service('api_platform.filter_locator'),
        ]);
};
