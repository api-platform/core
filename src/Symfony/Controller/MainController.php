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

namespace ApiPlatform\Symfony\Controller;

use ApiPlatform\Metadata\Error;
use ApiPlatform\Metadata\Exception\InvalidIdentifierException;
use ApiPlatform\Metadata\Exception\InvalidUriVariableException;
use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\UriVariablesConverterInterface;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\State\UriVariablesResolverTrait;
use ApiPlatform\State\Util\OperationRequestInitiatorTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class MainController
{
    use OperationRequestInitiatorTrait;
    use UriVariablesResolverTrait;

    public function __construct(
        ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory,
        private readonly ProviderInterface $provider,
        private readonly ProcessorInterface $processor,
        ?UriVariablesConverterInterface $uriVariablesConverter = null,
        private readonly ?LoggerInterface $logger = null,
    ) {
        $this->resourceMetadataCollectionFactory = $resourceMetadataCollectionFactory;
        $this->uriVariablesConverter = $uriVariablesConverter;
    }

    public function __invoke(Request $request): Response
    {
        $operation = $this->initializeOperation($request);
        if (!$operation) {
            throw new RuntimeException('Not an API operation.');
        }

        $uriVariables = [];
        if (!$operation instanceof Error) {
            try {
                $uriVariables = $this->getOperationUriVariables($operation, $request->attributes->all(), $operation->getClass());
                $request->attributes->set('_api_uri_variables', $uriVariables);
            } catch (InvalidIdentifierException|InvalidUriVariableException $e) {
                throw new NotFoundHttpException('Invalid uri variables.', $e);
            }
        }

        $context = [
            'request' => $request,
            'uri_variables' => $uriVariables,
            'resource_class' => $operation->getClass(),
        ];

        if (null === $operation->canValidate()) {
            $operation = $operation->withValidate(!$request->isMethodSafe() && !$request->isMethod('DELETE'));
        }

        if (null === $operation->canRead() && $operation instanceof HttpOperation) {
            $operation = $operation->withRead($operation->getUriVariables() || $request->isMethodSafe());
        }

        if (null === $operation->canDeserialize() && $operation instanceof HttpOperation) {
            $operation = $operation->withDeserialize(\in_array($operation->getMethod(), ['POST', 'PUT', 'PATCH'], true));
        }

        if (null === $operation->canWrite()) {
            $operation = $operation->withWrite(!$request->isMethodSafe());
        }

        if (null === $operation->canSerialize()) {
            $operation = $operation->withSerialize(true);
        }

        $body = $this->provider->provide($operation, $uriVariables, $context);

        // The provider can change the Operation, extract it again from the Request attributes
        if ($request->attributes->get('_api_operation') !== $operation) {
            $operation = $this->initializeOperation($request);

            if (!$operation instanceof Error) {
                try {
                    $uriVariables = $this->getOperationUriVariables($operation, $request->attributes->all(), $operation->getClass());
                } catch (InvalidIdentifierException|InvalidUriVariableException $e) {
                    // if this occurs with our base operation we throw above so log instead of throw here
                    if ($this->logger) {
                        $this->logger->error($e->getMessage(), ['operation' => $operation]);
                    }
                }
            }
        }

        $context['previous_data'] = $request->attributes->get('previous_data');
        $context['data'] = $request->attributes->get('data');

        return $this->processor->process($body, $operation, $uriVariables, $context);
    }
}
