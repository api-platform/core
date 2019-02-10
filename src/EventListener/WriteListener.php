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

namespace ApiPlatform\Core\EventListener;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use ApiPlatform\Core\Event\EventInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Util\RequestAttributesExtractor;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

/**
 * Bridges persistence and the API system.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class WriteListener
{
    private $dataPersister;
    private $iriConverter;
    private $resourceMetadataFactory;

    public function __construct(DataPersisterInterface $dataPersister, IriConverterInterface $iriConverter = null, ResourceMetadataFactoryInterface $resourceMetadataFactory = null)
    {
        $this->dataPersister = $dataPersister;
        $this->iriConverter = $iriConverter;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
    }

    /**
     * Persists, updates or delete data return by the controller if applicable.
     *
     * @deprecated since version 2.5, to be removed in 3.0
     */
    public function onKernelView(GetResponseForControllerResultEvent $event): void
    {
        @trigger_error(sprintf('The method %s() is deprecated since 2.5 and will be removed in 3.0.', __METHOD__), E_USER_DEPRECATED);

        $this->handleEvent($event);
    }

    /**
     * Persists, updates or delete data return by the controller if applicable.
     */
    public function handleEvent(/*EventInterface */$event): void
    {
        if ($event instanceof EventInterface) {
            $request = $event->getContext()['request'];
        } elseif ($event instanceof GetResponseForControllerResultEvent) {
            @trigger_error(sprintf('Passing an instance of "%s" as argument of "%s" is deprecated since 2.5 and will not be possible anymore in 3.0. Pass an instance of "%s" instead.', GetResponseForControllerResultEvent::class, __METHOD__, EventInterface::class), E_USER_DEPRECATED);

            $request = $event->getRequest();
        } else {
            return;
        }

        if ($request->isMethodSafe(false) || !($attributes = RequestAttributesExtractor::extractAttributes($request)) || !$attributes['persist']) {
            return;
        }

        if ($event instanceof EventInterface) {
            $controllerResult = $event->getData();
        } elseif ($event instanceof GetResponseForControllerResultEvent) {
            $controllerResult = $event->getControllerResult();
        } else {
            return;
        }

        if (!$this->dataPersister->supports($controllerResult, $attributes)) {
            return;
        }

        switch ($request->getMethod()) {
            case 'PUT':
            case 'PATCH':
            case 'POST':
                $persistResult = $this->dataPersister->persist($controllerResult, $attributes);

                if (null === $persistResult) {
                    @trigger_error(sprintf('Returning void from %s::persist() is deprecated since API Platform 2.3 and will not be supported in API Platform 3, an object should always be returned.', DataPersisterInterface::class), E_USER_DEPRECATED);
                }
                $result = $persistResult ?? $controllerResult;

                if ($event instanceof EventInterface) {
                    $event->setData($result);
                } elseif ($event instanceof GetResponseForControllerResultEvent) {
                    $event->setControllerResult($result);
                }

                if (null === $this->iriConverter) {
                    return;
                }

                $hasOutput = true;
                if (null !== $this->resourceMetadataFactory) {
                    $resourceMetadata = $this->resourceMetadataFactory->create($attributes['resource_class']);
                    $outputMetadata = $resourceMetadata->getOperationAttribute($attributes, 'output', ['class' => $attributes['resource_class']], true);
                    $hasOutput = \array_key_exists('class', $outputMetadata) && null !== $outputMetadata['class'] && $controllerResult instanceof $outputMetadata['class'];
                }

                if ($hasOutput) {
                    $request->attributes->set('_api_write_item_iri', $this->iriConverter->getIriFromItem($controllerResult));
                }

                break;
            case 'DELETE':
                $this->dataPersister->remove($controllerResult);

                if ($event instanceof EventInterface) {
                    $event->setData(null);
                } elseif ($event instanceof GetResponseForControllerResultEvent) {
                    $event->setControllerResult(null);
                }

                break;
        }
    }
}
