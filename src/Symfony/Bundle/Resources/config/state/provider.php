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

    $services->alias('api_platform.state_provider.main', 'api_platform.state_provider.locator');

    $services->set('api_platform.state_provider.content_negotiation', 'ApiPlatform\State\Provider\ContentNegotiationProvider')
        ->decorate('api_platform.state_provider.main', null, 100)
        ->args([
            service('api_platform.state_provider.content_negotiation.inner'),
            service('api_platform.negotiator'),
            '%api_platform.formats%',
            '%api_platform.error_formats%',
        ]);

    $services->set('api_platform.state_provider.read', 'ApiPlatform\State\Provider\ReadProvider')
        ->decorate('api_platform.state_provider.main', null, 500)
        ->args([
            service('api_platform.state_provider.read.inner'),
            service('api_platform.serializer.context_builder'),
        ]);

    $services->set('api_platform.state_provider.deserialize', 'ApiPlatform\State\Provider\DeserializeProvider')
        ->decorate('api_platform.state_provider.main', null, 300)
        ->args([
            service('api_platform.state_provider.deserialize.inner'),
            service('api_platform.serializer'),
            service('api_platform.serializer.context_builder'),
            service('translator')->nullOnInvalid(),
        ]);

    $services->set('api_platform.error_listener', 'ApiPlatform\Symfony\EventListener\ErrorListener')
        ->arg('$controller', 'api_platform.symfony.main_controller')
        ->arg('$logger', service('logger')->nullOnInvalid())
        ->arg('$debug', '%kernel.debug%')
        ->arg('$exceptionsMapping', [])
        ->arg('$resourceMetadataCollectionFactory', service('api_platform.metadata.resource.metadata_collection_factory'))
        ->arg('$errorFormats', '%api_platform.error_formats%')
        ->arg('$exceptionToStatus', '%api_platform.exception_to_status%')
        ->arg('$identifiersExtractor', null)
        ->arg('$resourceClassResolver', service('api_platform.resource_class_resolver'))
        ->arg('$negotiator', service('api_platform.negotiator'));

    $services->set('api_platform.state_provider.parameter', 'ApiPlatform\State\Provider\ParameterProvider')
        ->decorate('api_platform.state_provider.main', null, 180)
        ->args([
            service('api_platform.state_provider.parameter.inner'),
            tagged_locator('api_platform.parameter_provider', 'key'),
        ]);
};
