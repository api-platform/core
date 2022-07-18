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

use ApiPlatform\Api\IriConverterInterface;
use ApiPlatform\Api\ResourceClassResolverInterface;
use ApiPlatform\Api\UriVariablesConverterInterface;
use ApiPlatform\Exception\InvalidIdentifierException;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\State\UriVariablesResolverTrait;
use ApiPlatform\Util\OperationRequestInitiatorTrait;
use ApiPlatform\Util\RequestAttributesExtractor;
use ApiPlatform\Util\ResourceClassInfoTrait;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Bridges persistence and the API system.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class WriteListener
{
    use OperationRequestInitiatorTrait;
    use ResourceClassInfoTrait;

    use UriVariablesResolverTrait;

    public const OPERATION_ATTRIBUTE_KEY = 'write';

    private $processor;
    private $iriConverter;

    public function __construct(ProcessorInterface $processor, IriConverterInterface $iriConverter, ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory, ResourceClassResolverInterface $resourceClassResolver, ?UriVariablesConverterInterface $uriVariablesConverter = null)
    {
        $this->processor = $processor;
        $this->iriConverter = $iriConverter;
        $this->resourceMetadataCollectionFactory = $resourceMetadataCollectionFactory;
        // TODO 3.0: see ResourceClassInfoTrait
        $this->resourceMetadataFactory = $resourceMetadataCollectionFactory;
        $this->resourceClassResolver = $resourceClassResolver;
        $this->uriVariablesConverter = $uriVariablesConverter;
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
        ) {
            return;
        }

        if (!$operation || ($operation->getExtraProperties()['is_legacy_resource_metadata'] ?? false) || !($operation->canWrite() ?? true) || !$attributes['persist']) {
            return;
        }

        if (!$operation->getProcessor()) {
            return;
        }

        $context = ['operation' => $operation, 'resource_class' => $attributes['resource_class'], 'previous_data' => $attributes['previous_data'] ?? null];
        try {
            $uriVariables = $this->getOperationUriVariables($operation, $request->attributes->all(), $attributes['resource_class']);
        } catch (InvalidIdentifierException $e) {
            throw new NotFoundHttpException('Invalid identifier value or configuration.', $e);
        }

        switch ($request->getMethod()) {
            case 'PUT':
            case 'PATCH':
            case 'POST':
                $persistResult = $this->processor->process($controllerResult, $operation, $uriVariables, $context);

                if ($persistResult) {
                    $controllerResult = $persistResult;
                    $event->setControllerResult($controllerResult);
                }

                if ($controllerResult instanceof Response) {
                    break;
                }

                $outputMetadata = $operation->getOutput() ?? ['class' => $attributes['resource_class']];
                $hasOutput = \is_array($outputMetadata) && \array_key_exists('class', $outputMetadata) && null !== $outputMetadata['class'];
                if (!$hasOutput) {
                    break;
                }

                if ($this->isResourceClass($this->getObjectClass($controllerResult))) {
                    $request->attributes->set('_api_write_item_iri', $this->iriConverter->getIriFromResource($controllerResult));
                }

                break;
            case 'DELETE':
                $this->processor->process($controllerResult, $operation, $uriVariables, $context);
                $event->setControllerResult(null);
                break;
        }
    }
}
