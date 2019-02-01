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
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Util\RequestAttributesExtractor;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

/**
 * Bridges persistense and the API system.
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
     */
    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        $request = $event->getRequest();
        if ($request->isMethodSafe(false) || !$request->attributes->getBoolean('_api_persist', true) || !$attributes = RequestAttributesExtractor::extractAttributes($request)) {
            return;
        }

        $controllerResult = $event->getControllerResult();
        if (!$this->dataPersister->supports($controllerResult)) {
            return;
        }

        switch ($request->getMethod()) {
            case 'PUT':
            case 'PATCH':
            case 'POST':
                $persistResult = $this->dataPersister->persist($controllerResult);

                if (null === $persistResult) {
                    @trigger_error(sprintf('Returning void from %s::persist() is deprecated since API Platform 2.3 and will not be supported in API Platform 3, an object should always be returned.', DataPersisterInterface::class), E_USER_DEPRECATED);
                }

                $event->setControllerResult($persistResult ?? $controllerResult);

                // Controller result must be immutable for _api_write_item_iri
                // if it's class changed compared to the base class let's avoid calling the IriConverter
                // especially that the Output class could be a DTO that's not referencing any route
                if (null === $this->iriConverter) {
                    return;
                }

                $hasOutput = true;
                if (null !== $this->resourceMetadataFactory) {
                    $resourceMetadata = $this->resourceMetadataFactory->create($attributes['resource_class']);
                    $hasOutput = false !== $resourceMetadata->getOperationAttribute($attributes, 'output_class', null, true);
                }

                $class = \get_class($controllerResult);
                if ($hasOutput && $attributes['resource_class'] === $class && $class === \get_class($event->getControllerResult())) {
                    $request->attributes->set('_api_write_item_iri', $this->iriConverter->getIriFromItem($controllerResult));
                }
            break;
            case 'DELETE':
                $this->dataPersister->remove($controllerResult);
                $event->setControllerResult(null);
                break;
        }
    }
}
