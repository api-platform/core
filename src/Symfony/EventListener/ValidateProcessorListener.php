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
use Symfony\Component\HttpKernel\Event\ViewEvent;

/**
 * Validates data after ObjectMapper transformation (entity-level validation).
 */
final class ValidateProcessorListener
{
    use OperationRequestInitiatorTrait;

    /**
     * @param ProcessorInterface<mixed, mixed> $processor
     */
    public function __construct(
        private readonly ProcessorInterface $processor,
        ?ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory = null,
    ) {
        $this->resourceMetadataCollectionFactory = $resourceMetadataCollectionFactory;
    }

    public function onKernelView(ViewEvent $event): void
    {
        $request = $event->getRequest();
        $operation = $this->initializeOperation($request);

        if (!$operation || !($attributes = RequestAttributesExtractor::extractAttributes($request)) || !$attributes['persist']) {
            return;
        }

        // Only validate at the processor level when ObjectMapper is used (canMap() is true).
        // Without ObjectMapper, the validate listener already handles validation,
        // so running it again here would cause duplicate validation.
        if (!$operation->canMap()) {
            return;
        }

        if (null === $operation->canWrite()) {
            $operation = $operation->withWrite(!$request->isMethodSafe());
        }

        if (null === $operation->canValidate()) {
            $operation = $operation->withValidate(!$request->isMethodSafe() && !$request->isMethod('DELETE'));
        }

        $this->processor->process($event->getControllerResult(), $operation, $request->attributes->get('_api_uri_variables') ?? [], [
            'request' => $request,
        ]);
    }
}
