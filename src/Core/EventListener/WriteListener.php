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

use ApiPlatform\Api\IriConverterInterface;
use ApiPlatform\Core\Api\IriConverterInterface as LegacyIriConverterInterface;
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ToggleableOperationAttributeTrait;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Util\OperationRequestInitiatorTrait;
use ApiPlatform\Util\RequestAttributesExtractor;
use ApiPlatform\Util\ResourceClassInfoTrait;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;

/**
 * Bridges persistence and the API system.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 *
 * @deprecated
 */
final class WriteListener
{
    use OperationRequestInitiatorTrait;
    use ResourceClassInfoTrait;
    use ToggleableOperationAttributeTrait;

    public const OPERATION_ATTRIBUTE_KEY = 'write';

    private $dataPersister;
    /** @var LegacyIriConverterInterface|IriConverterInterface|null */
    private $iriConverter;
    private $metadataBackwardCompatibilityLayer;

    public function __construct(DataPersisterInterface $dataPersister, $iriConverter = null, ResourceMetadataFactoryInterface $resourceMetadataFactory = null, ResourceClassResolverInterface $resourceClassResolver = null, ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory = null, ?bool $metadataBackwardCompatibilityLayer = null)
    {
        $this->dataPersister = $dataPersister;
        $this->iriConverter = $iriConverter;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->resourceClassResolver = $resourceClassResolver;
        $this->resourceMetadataCollectionFactory = $resourceMetadataCollectionFactory;
        $this->metadataBackwardCompatibilityLayer = $metadataBackwardCompatibilityLayer;

        if ($metadataBackwardCompatibilityLayer || null === $metadataBackwardCompatibilityLayer) {
            trigger_deprecation('api-platform/core', '2.7', sprintf('The listener "%s" is deprecated and will be removed in 3.0, use "%s" instead', __CLASS__, \ApiPlatform\Symfony\EventListener\WriteListener::class));
        }
    }

    /**
     * Persists, updates or delete data return by the controller if applicable.
     */
    public function onKernelView(ViewEvent $event): void
    {
        $controllerResult = $event->getControllerResult();
        $request = $event->getRequest();
        $operation = $this->initializeOperation($request);

        if (
            $controllerResult instanceof Response
            || $request->isMethodSafe()
            || !($attributes = RequestAttributesExtractor::extractAttributes($request))
            || !$attributes['persist']
            || ($operation && !($operation->getExtraProperties()['is_legacy_resource_metadata'] ?? false))
            || $this->isOperationAttributeDisabled($attributes, self::OPERATION_ATTRIBUTE_KEY)
        ) {
            return;
        }

        if (false === $this->metadataBackwardCompatibilityLayer) {
            trigger_deprecation('api-platform/core', '2.7', 'The operation you requested uses legacy metadata, switch to #[ApiPlatform\Metadata\ApiResource].');
        }

        if (!$this->dataPersister->supports($controllerResult, $attributes)) {
            return;
        }

        switch ($request->getMethod()) {
            case 'PUT':
            case 'PATCH':
            case 'POST':
                $persistResult = $this->dataPersister->persist($controllerResult, $attributes);

                if (!\is_object($persistResult)) {
                    @trigger_error(sprintf('Not returning an object from %s::persist() is deprecated since API Platform 2.3 and will not be supported in API Platform 3.', DataPersisterInterface::class), \E_USER_DEPRECATED);
                } else {
                    $controllerResult = $persistResult;
                    $event->setControllerResult($controllerResult);
                }

                if ($controllerResult instanceof Response) {
                    break;
                }

                $hasOutput = true;
                if ($this->resourceMetadataFactory instanceof ResourceMetadataFactoryInterface) {
                    $resourceMetadata = $this->resourceMetadataFactory->create($attributes['resource_class']);
                    $outputMetadata = $resourceMetadata->getOperationAttribute($attributes, 'output', [
                        'class' => $attributes['resource_class'],
                    ], true);

                    $hasOutput = \array_key_exists('class', $outputMetadata) && null !== $outputMetadata['class'];
                }

                if (!$hasOutput) {
                    break;
                }

                if ($this->isResourceClass($this->getObjectClass($controllerResult))) {
                    $request->attributes->set('_api_write_item_iri', $this->iriConverter instanceof LegacyIriConverterInterface ? $this->iriConverter->getIriFromItem($controllerResult) : $this->iriConverter->getIriFromResource($controllerResult));
                }

                break;
            case 'DELETE':
                $this->dataPersister->remove($controllerResult, $attributes);
                $event->setControllerResult(null);
                break;
        }
    }
}
