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
use ApiPlatform\State\Util\OperationRequestInitiatorTrait;
use ApiPlatform\State\Util\RequestAttributesExtractor;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

/**
 * Maps the persisted entity back to the API resource (DTO) after persistence.
 */
final class ObjectMapperOutputListener
{
    use OperationRequestInitiatorTrait;

    public function __construct(
        private readonly ObjectMapperInterface $objectMapper,
        ?ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory = null,
    ) {
        $this->resourceMetadataCollectionFactory = $resourceMetadataCollectionFactory;
    }

    public function onKernelView(ViewEvent $event): void
    {
        $data = $event->getControllerResult();
        $request = $event->getRequest();
        $operation = $this->initializeOperation($request);

        if (!$operation || !($attributes = RequestAttributesExtractor::extractAttributes($request)) || !$attributes['persist']) {
            return;
        }

        if (
            $data instanceof Response
            || !($operation->canWrite() ?? !$request->isMethodSafe())
            || null === $data
            || !$operation->canMap()
        ) {
            return;
        }

        $request->attributes->set('persisted_data', $data);
        $dto = $this->objectMapper->map($data, $operation->getClass());

        $event->setControllerResult($dto);
    }
}
