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

use ApiPlatform\Metadata\Exception\InvalidIdentifierException;
use ApiPlatform\Metadata\Exception\InvalidUriVariableException;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\UriVariablesConverterInterface;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\State\UriVariablesResolverTrait;
use ApiPlatform\State\Util\OperationRequestInitiatorTrait;
use ApiPlatform\State\Util\OperationStageDefaults;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Runs the state provider before, and the state processor after, a controller carrying an API operation
 * when the Symfony listeners are not in use.
 *
 * @internal
 */
final class ControllerApiOperationPipelineListener
{
    use OperationRequestInitiatorTrait;
    use UriVariablesResolverTrait;

    public function __construct(
        private readonly ProviderInterface $provider,
        private readonly ProcessorInterface $processor,
        ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory,
        ?UriVariablesConverterInterface $uriVariablesConverter = null,
    ) {
        $this->resourceMetadataCollectionFactory = $resourceMetadataCollectionFactory;
        $this->uriVariablesConverter = $uriVariablesConverter;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if (!($operation = $this->getOperation($request))) {
            return;
        }

        $uriVariables = $this->resolveUriVariables($operation, $request);
        $request->attributes->set('_api_uri_variables', $uriVariables);

        $this->provider->provide(
            OperationStageDefaults::forProvider($operation, $request),
            $uriVariables,
            ['request' => $request, 'uri_variables' => $uriVariables, 'resource_class' => $operation->getClass()],
        );
    }

    public function onKernelView(ViewEvent $event): void
    {
        $request = $event->getRequest();
        if (!($operation = $this->getOperation($request))) {
            return;
        }

        $uriVariables = $request->attributes->get('_api_uri_variables') ?? [];

        $response = $this->processor->process(
            $event->getControllerResult(),
            OperationStageDefaults::forProcessor($operation, $request),
            $uriVariables,
            [
                'request' => $request,
                'uri_variables' => $uriVariables,
                'resource_class' => $operation->getClass(),
                'previous_data' => $request->attributes->get('previous_data'),
                'data' => $request->attributes->get('data'),
                'read_data' => $request->attributes->get('read_data'),
                'mapped_data' => $request->attributes->get('mapped_data'),
            ],
        );

        if ($response instanceof Response) {
            $event->setResponse($response);
        }
    }

    private function getOperation(Request $request): ?HttpOperation
    {
        if (!$request->attributes->get(ControllerApiOperationListener::REQUEST_ATTRIBUTE)) {
            return null;
        }

        $operation = $this->initializeOperation($request);

        return $operation instanceof HttpOperation ? $operation : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveUriVariables(HttpOperation $operation, Request $request): array
    {
        try {
            return $this->getOperationUriVariables($operation, $request->attributes->all(), $operation->getClass());
        } catch (InvalidIdentifierException|InvalidUriVariableException $e) {
            throw new NotFoundHttpException('Invalid uri variables.', $e);
        }
    }
}
