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

use ApiPlatform\Metadata\Error;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\State\Util\OperationRequestInitiatorTrait;
use ApiPlatform\State\Util\RequestAttributesExtractor;
use Symfony\Component\HttpKernel\Event\ViewEvent;

/**
 * Serializes data.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class SerializeListener
{
    use OperationRequestInitiatorTrait;

    /**
     * @param ProcessorInterface<mixed,mixed> $processor
     */
    public function __construct(private readonly ProcessorInterface $processor, ?ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory = null)
    {
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

        if (!($attributes['respond'] ?? $request->attributes->getBoolean('_api_respond', false)) || !$operation) {
            return;
        }

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
    }
}
