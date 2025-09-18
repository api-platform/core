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

use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\State\Util\OperationRequestInitiatorTrait;
use ApiPlatform\State\Util\RequestAttributesExtractor;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;

/**
 * Serializes data using JSON Streamer.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class JsonStreamerSerializeListener
{
    use OperationRequestInitiatorTrait;

    /**
     * @param ProcessorInterface<mixed,Response> $jsonStreamerProcessor
     */
    public function __construct(private readonly ProcessorInterface $jsonStreamerProcessor, private readonly string $format, ?ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory = null)
    {
        $this->resourceMetadataCollectionFactory = $resourceMetadataFactory;
    }

    /**
     * Creates a Response to send to the client according to the requested format.
     */
    public function onKernelView(ViewEvent $event): void
    {
        $request = $event->getRequest();
        $operation = $this->initializeOperation($request);

        $attributes = RequestAttributesExtractor::extractAttributes($request);
        if (!($attributes['respond'] ?? $request->attributes->getBoolean('_api_respond')) || !$operation || !$operation->getJsonStream() || $this->format !== $request->getRequestFormat()) {
            return;
        }

        $uriVariables = $request->attributes->get('_api_uri_variables') ?? [];
        $response = $this->jsonStreamerProcessor->process($event->getControllerResult(), $operation, $uriVariables, [
            'request' => $request,
            'uri_variables' => $uriVariables,
            'resource_class' => $operation->getClass(),
            'original_data' => $request->attributes->get('original_data'),
        ]);

        $event->setResponse($response);
    }
}
