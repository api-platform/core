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
use ApiPlatform\Metadata\Exception\InvalidIdentifierException;
use ApiPlatform\Metadata\Exception\InvalidUriVariableException;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\UriVariablesConverterInterface;
use ApiPlatform\Metadata\Util\ClassInfoTrait;
use ApiPlatform\Metadata\Util\CloneTrait;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\State\UriVariablesResolverTrait;
use ApiPlatform\State\Util\OperationRequestInitiatorTrait;
use ApiPlatform\State\Util\RequestAttributesExtractor;
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
    use ClassInfoTrait;
    use CloneTrait;
    use OperationRequestInitiatorTrait;
    use UriVariablesResolverTrait;

    /**
     * @param ProcessorInterface<mixed, mixed> $processor
     */
    public function __construct(
        private readonly ProcessorInterface $processor,
        ?ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory = null,
        ?UriVariablesConverterInterface $uriVariablesConverter = null,
    ) {
        $this->uriVariablesConverter = $uriVariablesConverter;
        $this->resourceMetadataCollectionFactory = $resourceMetadataCollectionFactory;
    }

    /**
     * Persists, updates or delete data return by the controller if applicable.
     */
    public function onKernelView(ViewEvent $event): void
    {
        $request = $event->getRequest();
        $operation = $this->initializeOperation($request);

        if (!($attributes = RequestAttributesExtractor::extractAttributes($request)) || !$attributes['persist'] || !$operation) {
            return;
        }

        if (null === $operation->canWrite()) {
            $operation = $operation->withWrite(!$request->isMethodSafe());
        }

        $uriVariables = $request->attributes->get('_api_uri_variables') ?? [];
        if (!$uriVariables && !$operation instanceof Error) {
            try {
                $uriVariables = $this->getOperationUriVariables($operation, $request->attributes->all(), $operation->getClass());
            } catch (InvalidIdentifierException|InvalidUriVariableException $e) {
                throw new NotFoundHttpException('Invalid identifier value or configuration.', $e);
            }
        }

        $data = $this->processor->process($event->getControllerResult(), $operation, $uriVariables, [
            'request' => $request,
            'uri_variables' => $uriVariables,
            'resource_class' => $operation->getClass(),
            'previous_data' => false === $operation->canRead() ? null : $request->attributes->get('previous_data'),
        ]);

        if ($data) {
            $request->attributes->set('original_data', $data);
        }

        $event->setControllerResult($data);
    }
}
