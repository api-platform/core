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
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\State\Util\OperationRequestInitiatorTrait;
use ApiPlatform\State\Util\RequestAttributesExtractor;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Deserializes the data sent in the requested format using JSON Streamer.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class JsonStreamerDeserializeListener
{
    use OperationRequestInitiatorTrait;

    /**
     * @param ProviderInterface<object> $jsonStreamerProvider
     */
    public function __construct(
        private ProviderInterface $jsonStreamerProvider,
        private readonly string $format,
        ?ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory = null,
    ) {
        $this->resourceMetadataCollectionFactory = $resourceMetadataFactory;
    }

    /**
     * Deserializes the data sent in the requested format.
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $operation = $this->initializeOperation($request);

        if (
            !($attributes = RequestAttributesExtractor::extractAttributes($request))
            || !$attributes['receive']
            || !$operation
            || !$operation->getJsonStream()
            || $this->format !== $request->attributes->get('input_format')
        ) {
            return;
        }

        $data = $this->jsonStreamerProvider->provide($operation, $request->attributes->get('_api_uri_variables') ?? [], [
            'request' => $request,
            'uri_variables' => $request->attributes->get('_api_uri_variables') ?? [],
            'resource_class' => $operation->getClass(),
        ]);

        $request->attributes->set('data', $data);
    }
}
