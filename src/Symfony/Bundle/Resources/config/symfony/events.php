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

use ApiPlatform\State\Processor\AddLinkHeaderProcessor;
use ApiPlatform\State\Processor\RespondProcessor;
use ApiPlatform\State\Processor\SerializeProcessor;
use ApiPlatform\State\Processor\WriteProcessor;
use ApiPlatform\State\Provider\ContentNegotiationProvider;
use ApiPlatform\State\Provider\DeserializeProvider;
use ApiPlatform\State\Provider\ParameterProvider;
use ApiPlatform\State\Provider\ReadProvider;
use ApiPlatform\Symfony\Action\DocumentationAction;
use ApiPlatform\Symfony\Action\EntrypointAction;
use ApiPlatform\Symfony\Action\PlaceholderAction;
use ApiPlatform\Symfony\EventListener\AddFormatListener;
use ApiPlatform\Symfony\EventListener\DeserializeListener;
use ApiPlatform\Symfony\EventListener\ErrorListener;
use ApiPlatform\Symfony\EventListener\ReadListener;
use ApiPlatform\Symfony\EventListener\RespondListener;
use ApiPlatform\Symfony\EventListener\SerializeListener;
use ApiPlatform\Symfony\EventListener\WriteListener;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('api_platform.state_provider.content_negotiation', ContentNegotiationProvider::class)
        ->args([
            null,
            service('api_platform.negotiator'),
            '%api_platform.formats%',
            '%api_platform.error_formats%',
        ]);

    $services->set('api_platform.listener.request.add_format', AddFormatListener::class)
        ->args([
            service('api_platform.state_provider.content_negotiation'),
            service('api_platform.metadata.resource.metadata_collection_factory'),
        ])
        ->tag('kernel.event_listener', ['event' => 'kernel.request', 'method' => 'onKernelRequest', 'priority' => 28]);

    $services->set('api_platform.state_provider.read', ReadProvider::class)
        ->arg(0, service('api_platform.state_provider.locator'))
        ->arg(1, service('api_platform.serializer.context_builder'))
        ->arg('$logger', service('logger')->nullOnInvalid());

    $services->set('api_platform.state_provider.parameter', ParameterProvider::class)
        ->args([
            null,
            tagged_locator('api_platform.parameter_provider', 'key'),
        ]);

    $services->set('api_platform.listener.request.read', ReadListener::class)
        ->args([
            service('api_platform.state_provider.read'),
            service('api_platform.metadata.resource.metadata_collection_factory'),
            service('api_platform.uri_variables.converter'),
            service('api_platform.state_provider.parameter')->nullOnInvalid(),
        ])
        ->tag('kernel.event_listener', ['event' => 'kernel.request', 'method' => 'onKernelRequest', 'priority' => 4]);

    $services->set('api_platform.state_provider.deserialize', DeserializeProvider::class)
        ->args([
            null,
            service('api_platform.serializer'),
            service('api_platform.serializer.context_builder'),
            service('translator')->nullOnInvalid(),
        ]);

    $services->set('api_platform.listener.request.deserialize', DeserializeListener::class)
        ->args([
            service('api_platform.state_provider.deserialize'),
            service('api_platform.metadata.resource.metadata_collection_factory'),
        ])
        ->tag('kernel.event_listener', ['event' => 'kernel.request', 'method' => 'onKernelRequest', 'priority' => 2]);

    $services->set('api_platform.state_processor.serialize', SerializeProcessor::class)
        ->args([
            null,
            service('api_platform.serializer'),
            service('api_platform.serializer.context_builder'),
        ]);

    $services->set('api_platform.state_processor.write', WriteProcessor::class)
        ->args([
            null,
            service('api_platform.state_processor.locator'),
        ]);

    $services->set('api_platform.state_processor.respond', RespondProcessor::class)
        ->args([
            service('api_platform.iri_converter'),
            service('api_platform.resource_class_resolver'),
            service('api_platform.metadata.operation.metadata_factory'),
        ]);

    $services->set('api_platform.state_processor.add_link_header', AddLinkHeaderProcessor::class)
        ->decorate('api_platform.state_processor.respond', null, 0)
        ->args([service('api_platform.state_processor.add_link_header.inner')]);

    $services->set('api_platform.listener.view.write', WriteListener::class)
        ->args([
            service('api_platform.state_processor.write'),
            service('api_platform.metadata.resource.metadata_collection_factory'),
        ])
        ->tag('kernel.event_listener', ['event' => 'kernel.view', 'method' => 'onKernelView', 'priority' => 32]);

    $services->set('api_platform.listener.view.serialize', SerializeListener::class)
        ->args([
            service('api_platform.state_processor.serialize'),
            service('api_platform.metadata.resource.metadata_collection_factory'),
        ])
        ->tag('kernel.event_listener', ['event' => 'kernel.view', 'method' => 'onKernelView', 'priority' => 16]);

    $services->set('api_platform.listener.view.respond', RespondListener::class)
        ->args([
            service('api_platform.state_processor.respond'),
            service('api_platform.metadata.resource.metadata_collection_factory'),
        ])
        ->tag('kernel.event_listener', ['event' => 'kernel.view', 'method' => 'onKernelView', 'priority' => 8]);

    $services->set('api_platform.error_listener', ErrorListener::class)
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

    $services->set('api_platform.state_processor.documentation.serialize', SerializeProcessor::class)
        ->decorate('api_platform.state_processor.documentation', null, 200)
        ->args([
            service('api_platform.state_processor.documentation.serialize.inner'),
            service('api_platform.serializer'),
            service('api_platform.serializer.context_builder'),
        ]);

    $services->set('api_platform.state_processor.documentation.write', WriteProcessor::class)
        ->decorate('api_platform.state_processor.documentation', null, 100)
        ->args([
            service('api_platform.state_processor.documentation.write.inner'),
            service('api_platform.state_processor.locator'),
        ]);

    $services->alias('api_platform.state_provider.documentation', 'api_platform.state_provider.locator');

    $services->set('api_platform.state_provider.documentation.content_negotiation', ContentNegotiationProvider::class)
        ->decorate('api_platform.state_provider.documentation', null, 100)
        ->args([
            service('api_platform.state_provider.documentation.content_negotiation.inner'),
            service('api_platform.negotiator'),
            '%api_platform.formats%',
            '%api_platform.error_formats%',
        ]);

    $services->set('api_platform.state_provider.documentation.read', ReadProvider::class)
        ->decorate('api_platform.state_provider.documentation', null, 500)
        ->args([
            service('api_platform.state_provider.documentation.read.inner'),
            service('api_platform.serializer.context_builder'),
        ]);

    $services->set('api_platform.action.entrypoint', EntrypointAction::class)
        ->public()
        ->args([
            service('api_platform.metadata.resource.name_collection_factory'),
            service('api_platform.state_provider.documentation'),
            service('api_platform.state_processor.documentation'),
            '%api_platform.docs_formats%',
        ]);

    $services->set('api_platform.action.documentation', DocumentationAction::class)
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
            '%api_platform.enable_docs%',
            '%api_platform.enable_re_doc%',
        ]);

    $services->set('api_platform.action.placeholder', PlaceholderAction::class)
        ->public();

    $services->alias(PlaceholderAction::class, 'api_platform.action.placeholder')
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
