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

namespace ApiPlatform\Symfony\EventListener;

use ApiPlatform\Doctrine\Odm\State\Options as ODMOptions;
use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Exception\RuntimeException;
use ApiPlatform\Metadata\Error;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\State\ResourceList;
use ApiPlatform\State\SerializerContextBuilderInterface;
use ApiPlatform\State\Util\OperationRequestInitiatorTrait;
use ApiPlatform\Symfony\Util\RequestAttributesExtractor;
use ApiPlatform\Util\ErrorFormatGuesser;
use ApiPlatform\Validator\Exception\ValidationException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\WebLink\GenericLinkProvider;
use Symfony\Component\WebLink\Link;

/**
 * Serializes data.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class SerializeListener
{
    use OperationRequestInitiatorTrait;

    public const OPERATION_ATTRIBUTE_KEY = 'serialize';
    private ?SerializerInterface $serializer = null;
    private ?ProcessorInterface $processor = null;
    private ?SerializerContextBuilderInterface $serializerContextBuilder = null;

    public function __construct(
        SerializerInterface|ProcessorInterface $serializer,
        SerializerContextBuilderInterface|ResourceMetadataCollectionFactoryInterface|null $serializerContextBuilder = null,
        ?ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory = null,
        private readonly array $errorFormats = [],
        // @phpstan-ignore-next-line we don't need this anymore
        private readonly bool $debug = false,
    ) {
        if ($serializer instanceof ProcessorInterface) {
            $this->processor = $serializer;
        } else {
            $this->serializer = $serializer;
            trigger_deprecation('api-platform/core', '3.3', 'Use a "%s" as first argument in "%s" instead of "%s".', ProcessorInterface::class, self::class, SerializerInterface::class);
        }

        if ($serializerContextBuilder instanceof ResourceMetadataCollectionFactoryInterface) {
            $resourceMetadataFactory = $serializerContextBuilder;
        } else {
            $this->serializerContextBuilder = $serializerContextBuilder;
            trigger_deprecation('api-platform/core', '3.3', 'Use a "%s" as second argument in "%s" instead of "%s".', ResourceMetadataCollectionFactoryInterface::class, self::class, SerializerContextBuilderInterface::class);
        }

        $this->resourceMetadataCollectionFactory = $resourceMetadataFactory;
    }

    /**
     * Serializes the data to the requested format.
     */
    public function onKernelView(ViewEvent $event): void
    {
        $controllerResult = $event->getControllerResult();
        $request = $event->getRequest();
        $operation = $this->initializeOperation($request);

        $attributes = RequestAttributesExtractor::extractAttributes($request);

        if (!($attributes['respond'] ?? $request->attributes->getBoolean('_api_respond', false))) {
            return;
        }

        if ($operation && $this->processor instanceof ProcessorInterface) {
            if (null === $operation->canSerialize()) {
                $operation = $operation->withSerialize(true);
            }

            if ($operation instanceof Error) {
                // we don't want the FlattenException
                $controllerResult = $request->attributes->get('data') ?? $controllerResult;
            }

            $uriVariables = $request->attributes->get('_api_uri_variables') ?? [];
            $serialized = $this->processor->process($controllerResult, $operation, $uriVariables, [
                'request' => $request,
                'uri_variables' => $uriVariables,
                'resource_class' => $operation->getClass(),
            ]);

            $event->setControllerResult($serialized);

            return;
        }

        // TODO: the code below needs to be removed in 4.x
        if ($controllerResult instanceof Response) {
            return;
        }

        $attributes = RequestAttributesExtractor::extractAttributes($request);

        if (!($attributes['respond'] ?? $request->attributes->getBoolean('_api_respond', false))) {
            return;
        }

        if ('api_platform.symfony.main_controller' === $operation?->getController() || $request->attributes->get('_api_platform_disable_listeners')) {
            return;
        }

        if (!($operation?->canSerialize() ?? true)) {
            return;
        }

        if (!$attributes) {
            $this->serializeRawData($event, $request, $controllerResult);

            return;
        }

        $context = $this->serializerContextBuilder->createFromRequest($request, true, $attributes);
        if (isset($context['output']) && \array_key_exists('class', $context['output']) && null === $context['output']['class']) {
            $event->setControllerResult(null);

            return;
        }

        if ($controllerResult instanceof ValidationException) {
            $format = ErrorFormatGuesser::guessErrorFormat($request, $this->errorFormats);
            $previousOperation = $request->attributes->get('_api_previous_operation');
            if (!($previousOperation?->getExtraProperties()['rfc_7807_compliant_errors'] ?? false)) {
                $context['groups'] = ['legacy_'.$format['key']];
                $context['force_iri_generation'] = false;
            }
        }

        if ($included = $request->attributes->get('_api_included')) {
            $context['api_included'] = $included;
        }
        $resources = new ResourceList();
        $context['resources'] = &$resources;
        $context[AbstractObjectNormalizer::EXCLUDE_FROM_CACHE_KEY][] = 'resources';

        $resourcesToPush = new ResourceList();
        $context['resources_to_push'] = &$resourcesToPush;
        $context[AbstractObjectNormalizer::EXCLUDE_FROM_CACHE_KEY][] = 'resources_to_push';
        if (($options = $operation?->getStateOptions()) && (
            ($options instanceof Options && $options->getEntityClass())
            || ($options instanceof ODMOptions && $options->getDocumentClass())
        )) {
            $context['force_resource_class'] = $operation->getClass();
        }

        $request->attributes->set('_api_normalization_context', $context);
        $event->setControllerResult($this->serializer->serialize($controllerResult, $request->getRequestFormat(), $context));

        $request->attributes->set('_resources', $request->attributes->get('_resources', []) + (array) $resources);
        if (!\count($resourcesToPush)) {
            return;
        }

        $linkProvider = $request->attributes->get('_api_platform_links', new GenericLinkProvider());
        foreach ($resourcesToPush as $resourceToPush) {
            $linkProvider = $linkProvider->withLink((new Link('preload', $resourceToPush))->withAttribute('as', 'fetch'));
        }
        $request->attributes->set('_api_platform_links', $linkProvider);
    }

    /**
     * Tries to serialize data that are not API resources (e.g. the entrypoint or data returned by a custom controller).
     *
     * @throws RuntimeException
     */
    private function serializeRawData(ViewEvent $event, Request $request, $controllerResult): void
    {
        if (\is_object($controllerResult)) {
            $event->setControllerResult($this->serializer->serialize($controllerResult, $request->getRequestFormat(), $request->attributes->get('_api_normalization_context', [])));

            return;
        }

        if (!$this->serializer instanceof EncoderInterface) {
            throw new RuntimeException(sprintf('The serializer must implement the "%s" interface.', EncoderInterface::class));
        }

        $event->setControllerResult($this->serializer->encode($controllerResult, $request->getRequestFormat()));
    }
}
