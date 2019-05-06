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
use ApiPlatform\Core\Metadata\Resource\ToggleableOperationAttributeTrait;
use ApiPlatform\Core\Util\RequestAttributesExtractor;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

/**
 * Bridges persistense and the API system.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class WriteListener
{
    use ToggleableOperationAttributeTrait;

    public const OPERATION_ATTRIBUTE_KEY = 'write';

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
    public function onKernelView(GetResponseForControllerResultEvent $event): void
    {
        $controllerResult = $event->getControllerResult();
        $request = $event->getRequest();

        if (
            $controllerResult instanceof Response
            || $request->isMethodSafe(false)
            || !($attributes = RequestAttributesExtractor::extractAttributes($request))
            || !$attributes['persist']
            || $this->isOperationAttributeDisabled($attributes, self::OPERATION_ATTRIBUTE_KEY)
        ) {
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
                } else {
                    $controllerResult = $persistResult;
                    $event->setControllerResult($controllerResult);
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
                $event->setControllerResult(null);
                break;
        }
    }
}
