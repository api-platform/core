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

use ApiPlatform\Core\Api\ContextAwareIriConverterInterface;
use ApiPlatform\Core\Api\DeprecateWrongIriConversionTrait;
use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Api\UrlGeneratorInterface;
use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ToggleableOperationAttributeTrait;
use ApiPlatform\Core\Util\RequestAttributesExtractor;
use ApiPlatform\Core\Util\ResourceClassInfoTrait;
use ApiPlatform\State\ProcessorInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;

/**
 * Bridges persistence and the API system.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class WriteListener
{
    use ResourceClassInfoTrait;
    use ToggleableOperationAttributeTrait;
    use DeprecateWrongIriConversionTrait;

    public const OPERATION_ATTRIBUTE_KEY = 'write';

    private $dataPersister;
    private $iriConverter;

    public function __construct($dataPersister, IriConverterInterface $iriConverter = null, $resourceMetadataFactory = null, ResourceClassResolverInterface $resourceClassResolver = null)
    {
        if ($dataPersister instanceof DataPersisterInterface) {
            @trigger_error(sprintf('The use of %s is deprecated since API Platform 2.7 and will be removed in 3.0, use %s instead.', DataPersisterInterface::class, ProcessorInterface::class), \E_USER_DEPRECATED);
        }
        $this->dataPersister = $dataPersister;
        $this->iriConverter = $iriConverter;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->resourceClassResolver = $resourceClassResolver;

        if ($this->resourceMetadataFactory) {
            @trigger_error(sprintf('The use of %s is deprecated since API Platform 2.7 and will be removed in 3.0.', ResourceMetadataFactoryInterface::class), \E_USER_DEPRECATED);
        }
    }

    /**
     * Persists, updates or delete data return by the controller if applicable.
     */
    public function onKernelView(ViewEvent $event): void
    {
        $controllerResult = $event->getControllerResult();
        $request = $event->getRequest();

        if (
            $controllerResult instanceof Response
            || $request->isMethodSafe()
            || !($attributes = RequestAttributesExtractor::extractAttributes($request))
            || !$attributes['persist']
            || $this->isOperationAttributeDisabled($attributes, self::OPERATION_ATTRIBUTE_KEY)
        ) {
            return;
        }

        if ($this->dataPersister instanceof DataPersisterInterface && !$this->dataPersister->supports($controllerResult, $attributes)) {
            return;
        }

        if ($this->dataPersister instanceof ProcessorInterface && !$this->dataPersister->supports($controllerResult, $attributes['identifiers'], $attributes)) {
            return;
        }

        $context = ($attributes['operation'] ?? []) + ['operation_name' => $attributes['operation_name'], 'resource_class' => $attributes['resource_class']];

        switch ($request->getMethod()) {
            case 'PUT':
            case 'PATCH':
            case 'POST':
                $persistResult = $this->dataPersister instanceof DataPersisterInterface ? $this->dataPersister->persist($controllerResult, $attributes) : $this->dataPersister->process($controllerResult, $attributes['identifiers'], $context);

                if (!\is_object($persistResult)) {
                    @trigger_error(sprintf('Not returning an object from %s::persist() is deprecated since API Platform 2.3 and will not be supported in API Platform 3.', DataPersisterInterface::class), \E_USER_DEPRECATED);
                } else {
                    $controllerResult = $persistResult;
                    $event->setControllerResult($controllerResult);
                }

                if ($controllerResult instanceof Response) {
                    break;
                }

                $outputMetadata = $attributes['operation']['output'] ?? ['class' => $attributes['resource_class']];
                if ($this->resourceMetadataFactory instanceof ResourceMetadataFactoryInterface) {
                    $resourceMetadata = $this->resourceMetadataFactory->create($attributes['resource_class']);
                    $outputMetadata = $resourceMetadata->getOperationAttribute($attributes, 'output', [
                        'class' => $attributes['resource_class'],
                    ], true);
                }

                $hasOutput = \array_key_exists('class', $outputMetadata) && null !== $outputMetadata['class'];
                if (!$hasOutput) {
                    break;
                }

                if ($this->iriConverter instanceof IriConverterInterface && $this->isResourceClass($resourceClass = $this->getObjectClass($controllerResult))) {
                    $request->attributes->set('_api_write_item_iri', $this->iriConverter->getIriFromItem($controllerResult));
                }

                break;
            case 'DELETE':
                $this->dataPersister instanceof DataPersisterInterface ? $this->dataPersister->remove($controllerResult, $attributes) : $this->dataPersister->process($controllerResult, $attributes, $context);
                $event->setControllerResult(null);
                break;
        }
    }
}
