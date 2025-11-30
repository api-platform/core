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

    $services->set('api_platform.state_provider.content_negotiation', 'ApiPlatform\State\Provider\ContentNegotiationProvider')
        ->args([
            null,
            service('api_platform.negotiator'),
            '%api_platform.formats%',
            '%api_platform.error_formats%',
        ]);

    $services->set('api_platform.listener.request.add_format', 'ApiPlatform\Symfony\EventListener\AddFormatListener')
        ->args([
            service('api_platform.state_provider.content_negotiation'),
            service('api_platform.metadata.resource.metadata_collection_factory'),
        ])
        ->tag('kernel.event_listener', ['event' => 'kernel.request', 'method' => 'onKernelRequest', 'priority' => 28]);

    $services->set('api_platform.state_provider.read', 'ApiPlatform\State\Provider\ReadProvider')
        ->arg(0, service('api_platform.state_provider.locator'))
        ->arg(1, service('api_platform.serializer.context_builder'))
        ->arg('$logger', service('logger')->nullOnInvalid());

    $services->set('api_platform.state_provider.parameter', 'ApiPlatform\State\Provider\ParameterProvider')
        ->args([
            null,
            tagged_locator('api_platform.parameter_provider', 'key'),
        ]);

    $services->set('api_platform.listener.request.read', 'ApiPlatform\Symfony\EventListener\ReadListener')
        ->args([
            service('api_platform.state_provider.read'),
            service('api_platform.metadata.resource.metadata_collection_factory'),
            service('api_platform.uri_variables.converter'),
            service('api_platform.state_provider.parameter')->nullOnInvalid(),
        ])
        ->tag('kernel.event_listener', ['event' => 'kernel.request', 'method' => 'onKernelRequest', 'priority' => 4]);

    $services->set('api_platform.state_provider.deserialize', 'ApiPlatform\State\Provider\DeserializeProvider')
        ->args([
            null,
            service('api_platform.serializer'),
            service('api_platform.serializer.context_builder'),
            service('translator')->nullOnInvalid(),
        ]);

    $services->set('api_platform.listener.request.deserialize', 'ApiPlatform\Symfony\EventListener\DeserializeListener')
        ->args([
            service('api_platform.state_provider.deserialize'),
            service('api_platform.metadata.resource.metadata_collection_factory'),
        ])
        ->tag('kernel.event_listener', ['event' => 'kernel.request', 'method' => 'onKernelRequest', 'priority' => 2]);

    $services->set('api_platform.state_processor.serialize', 'ApiPlatform\State\Processor\SerializeProcessor')
        ->args([
            null,
            service('api_platform.serializer'),
            service('api_platform.serializer.context_builder'),
        ]);

    $services->set('api_platform.state_processor.write', 'ApiPlatform\State\Processor\WriteProcessor')
        ->args([
            null,
            service('api_platform.state_processor.locator'),
        ]);

    $services->set('api_platform.state_processor.respond', 'ApiPlatform\State\Processor\RespondProcessor')
        ->args([
            service('api_platform.iri_converter'),
            service('api_platform.resource_class_resolver'),
            service('api_platform.metadata.operation.metadata_factory'),
        ]);

    $services->set('api_platform.state_processor.add_link_header', 'ApiPlatform\State\Processor\AddLinkHeaderProcessor')
        ->decorate('api_platform.state_processor.respond', null, 0)
        ->args([service('api_platform.state_processor.add_link_header.inner')]);

    $services->set('api_platform.listener.view.write', 'ApiPlatform\Symfony\EventListener\WriteListener')
        ->args([
            service('api_platform.state_processor.write'),
            service('api_platform.metadata.resource.metadata_collection_factory'),
        ])
        ->tag('kernel.event_listener', ['event' => 'kernel.view', 'method' => 'onKernelView', 'priority' => 32]);

    $services->set('api_platform.listener.view.serialize', 'ApiPlatform\Symfony\EventListener\SerializeListener')
        ->args([
            service('api_platform.state_processor.serialize'),
            service('api_platform.metadata.resource.metadata_collection_factory'),
        ])
        ->tag('kernel.event_listener', ['event' => 'kernel.view', 'method' => 'onKernelView', 'priority' => 16]);

    $services->set('api_platform.listener.view.respond', 'ApiPlatform\Symfony\EventListener\RespondListener')
        ->args([
            service('api_platform.state_processor.respond'),
            service('api_platform.metadata.resource.metadata_collection_factory'),
        ])
        ->tag('kernel.event_listener', ['event' => 'kernel.view', 'method' => 'onKernelView', 'priority' => 8]);

    $services->set('api_platform.error_listener', 'ApiPlatform\Symfony\EventListener\ErrorListener')
        ->arg('$controller', 'api_platform.action.placeholder')
        ->arg('$logger', service('logger')->nullOnInvalid())
        ->arg('$debug', '%kernel.debug%')
        ->arg('$exceptionsMapping', [])
        ->arg('$resourceMetadataCollectionFactory', service('api_platform.metadata.resource.metadata_collection_factory'))
        ->arg('$errorFormats', '%api_platform.error_formats%')
        ->arg('$exceptionToStatus', '%api_platform.exception_to_status%')
        ->arg('$identifiersExtractor', null)
        ->arg('$resourceClassResolver', service('api_platform.resource_class_resolver'))
        ->arg('$negotiator', service('api_platform.negotiator'));

    $services->alias('api_platform.state_processor.documentation', 'api_platform.state_processor.respond');

    $services->set('api_platform.state_processor.documentation.serialize', 'ApiPlatform\State\Processor\SerializeProcessor')
        ->decorate('api_platform.state_processor.documentation', null, 200)
        ->args([
            service('api_platform.state_processor.documentation.serialize.inner'),
            service('api_platform.serializer'),
            service('api_platform.serializer.context_builder'),
        ]);

    $services->set('api_platform.state_processor.documentation.write', 'ApiPlatform\State\Processor\WriteProcessor')
        ->decorate('api_platform.state_processor.documentation', null, 100)
        ->args([
            service('api_platform.state_processor.documentation.write.inner'),
            service('api_platform.state_processor.locator'),
        ]);

    $services->alias('api_platform.state_provider.documentation', 'api_platform.state_provider.locator');

    $services->set('api_platform.state_provider.documentation.content_negotiation', 'ApiPlatform\State\Provider\ContentNegotiationProvider')
        ->decorate('api_platform.state_provider.documentation', null, 100)
        ->args([
            service('api_platform.state_provider.documentation.content_negotiation.inner'),
            service('api_platform.negotiator'),
            '%api_platform.formats%',
            '%api_platform.error_formats%',
        ]);

    $services->set('api_platform.state_provider.documentation.read', 'ApiPlatform\State\Provider\ReadProvider')
        ->decorate('api_platform.state_provider.documentation', null, 500)
        ->args([
            service('api_platform.state_provider.documentation.read.inner'),
            service('api_platform.serializer.context_builder'),
        ]);

    $services->set('api_platform.action.entrypoint', 'ApiPlatform\Symfony\Action\EntrypointAction')
        ->public()
        ->args([
            service('api_platform.metadata.resource.name_collection_factory'),
            service('api_platform.state_provider.documentation'),
            service('api_platform.state_processor.documentation'),
            '%api_platform.docs_formats%',
        ]);

    $services->set('api_platform.action.documentation', 'ApiPlatform\Symfony\Action\DocumentationAction')
        ->public()
        ->args([
            service('api_platform.metadata.resource.name_collection_factory'),
            '%api_platform.title%',
            '%api_platform.description%',
            '%api_platform.version%',
            service('api_platform.openapi.factory')->nullOnInvalid(),
            service('api_platform.state_provider.documentation'),
            service('api_platform.state_processor.documentation'),
            service('api_platform.negotiator')->nullOnInvalid(),
            '%api_platform.docs_formats%',
            '%api_platform.enable_swagger_ui%',
        ]);

    $services->set('api_platform.action.placeholder', 'ApiPlatform\Symfony\Action\PlaceholderAction')
        ->public();

    $services->alias('ApiPlatform\Symfony\Action\PlaceholderAction', 'api_platform.action.placeholder')
        ->public();

    $services->alias('api_platform.action.get_collection', 'api_platform.action.placeholder')
        ->public();

    $services->alias('api_platform.action.post_collection', 'api_platform.action.placeholder')
        ->public();

    $services->alias('api_platform.action.get_item', 'api_platform.action.placeholder')
        ->public();

    $services->alias('api_platform.action.patch_item', 'api_platform.action.placeholder')
        ->public();

    $services->alias('api_platform.action.put_item', 'api_platform.action.placeholder')
        ->public();

    $services->alias('api_platform.action.delete_item', 'api_platform.action.placeholder')
        ->public();
};
