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
 * Builds the response object.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class RespondListener
{
    use OperationRequestInitiatorTrait;

    /**
     * @param ProcessorInterface<mixed,Response> $processor
     */
    public function __construct(private readonly ProcessorInterface $processor, ?ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory = null)
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
        if (!($attributes['respond'] ?? $request->attributes->getBoolean('_api_respond')) || !$operation) {
            return;
        }

        $uriVariables = $request->attributes->get('_api_uri_variables') ?? [];
        $response = $this->processor->process($event->getControllerResult(), $operation, $uriVariables, [
            'request' => $request,
            'uri_variables' => $uriVariables,
            'resource_class' => $operation->getClass(),
            'original_data' => $request->attributes->get('original_data'),
        ]);

        $event->setResponse($response);
    }
}
