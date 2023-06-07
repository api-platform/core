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

use ApiPlatform\Api\IdentifiersExtractorInterface;
use ApiPlatform\ApiResource\Error;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Util\ErrorFormatGuesser;
use ApiPlatform\Util\OperationRequestInitiatorTrait;
use ApiPlatform\Util\RequestAttributesExtractor;
use ApiPlatform\Validator\Exception\ValidationException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Exception\RequestExceptionInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\EventListener\ErrorListener as SymfonyErrorListener;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface as SymfonyHttpExceptionInterface;

/**
 * This error listener extends the Symfony one in order to add
 * the `_api_operation` attribute when the request is duplicated.
 * It will later be used to retrieve the exceptionToStatus from the operation ({@see ExceptionAction}).
 */
final class ErrorListener extends SymfonyErrorListener
{
    use OperationRequestInitiatorTrait;

    public function __construct(
        object|array|string|null $controller,
        LoggerInterface $logger = null,
        bool $debug = false,
        array $exceptionsMapping = [],
        ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory = null,
        private readonly array $errorFormats = [],
        private readonly array $exceptionToStatus = [],
        private readonly ?IdentifiersExtractorInterface $identifiersExtractor = null,
        private readonly ?ResourceClassResolverInterface $resourceClassResolver = null
    ) {
        parent::__construct($controller, $logger, $debug, $exceptionsMapping);
        $this->resourceMetadataCollectionFactory = $resourceMetadataCollectionFactory;
    }

    protected function duplicateRequest(\Throwable $exception, Request $request): Request
    {
        $dup = parent::duplicateRequest($exception, $request);

        $apiOperation = $this->initializeOperation($request);

        $resourceClass = $exception::class;
        $format = ErrorFormatGuesser::guessErrorFormat($request, $this->errorFormats);

        if ($this->resourceClassResolver?->isResourceClass($exception::class)) {
            $resourceCollection = $this->resourceMetadataCollectionFactory->create($exception::class);

            $operation = null;
            foreach ($resourceCollection as $resource) {
                foreach ($resource->getOperations() as $op) {
                    foreach ($op->getOutputFormats() as $key => $value) {
                        if ($key === $format['key']) {
                            $operation = $op;
                            break 3;
                        }
                    }
                }
            }

            // No operation found for the requested format, we take the first available
            if (!$operation) {
                $operation = $resourceCollection->getOperation();
            }
            $errorResource = $exception;
        } elseif ($this->resourceMetadataCollectionFactory) {
            // Create a generic, rfc7807 compatible error according to the wanted format
            /** @var HttpOperation $operation */
            $operation = $this->resourceMetadataCollectionFactory->create(Error::class)->getOperation($this->getFormatOperation($format['key'] ?? null));
            $operation = $operation->withStatus($this->getStatusCode($apiOperation, $request, $operation, $exception));
            $errorResource = Error::createFromException($exception, $operation->getStatus());
            $resourceClass = Error::class;
        } else {
            $operation = new Get(name: '_api_errors_problem', class: Error::class, outputFormats: ['jsonld' => ['application/ld+json']], normalizationContext: ['groups' => ['jsonld'], 'skip_null_values' => true]);
            $operation = $operation->withStatus($this->getStatusCode($apiOperation, $request, $operation, $exception));
            $errorResource = Error::createFromException($exception, $operation->getStatus());
            $resourceClass = Error::class;
        }

        $identifiers = $this->identifiersExtractor?->getIdentifiersFromItem($errorResource, $operation) ?? [];

        $dup->attributes->set('_api_error', true);
        $dup->attributes->set('_api_resource_class', $resourceClass);
        $dup->attributes->set('_api_previous_operation', $apiOperation);
        $dup->attributes->set('_api_operation', $operation);
        $dup->attributes->set('_api_operation_name', $operation->getName());
        $dup->attributes->remove('exception');
        $dup->attributes->set('data', $errorResource);
        // Once we get rid of the SwaggerUiAction we'll be able to do this properly
        $dup->attributes->set('_api_exception_swagger_data', [
            '_route' => $request->attributes->get('_route'),
            '_route_params' => $request->attributes->get('_route_params'),
            '_api_resource_class' => $request->attributes->get('_api_resource_class'),
            '_api_operation_name' => $request->attributes->get('_api_operation_name'),
        ]);

        foreach ($identifiers as $name => $value) {
            $dup->attributes->set($name, $value);
        }

        return $dup;
    }

    private function getOperationExceptionToStatus(Request $request): array
    {
        $attributes = RequestAttributesExtractor::extractAttributes($request);

        if ([] === $attributes) {
            return [];
        }

        $resourceMetadataCollection = $this->resourceMetadataCollectionFactory->create($attributes['resource_class']);
        /** @var HttpOperation $operation */
        $operation = $resourceMetadataCollection->getOperation($attributes['operation_name'] ?? null);
        $exceptionToStatus = [$operation->getExceptionToStatus() ?: []];

        foreach ($resourceMetadataCollection as $resourceMetadata) {
            /* @var ApiResource $resourceMetadata */
            $exceptionToStatus[] = $resourceMetadata->getExceptionToStatus() ?: [];
        }

        return array_merge(...$exceptionToStatus);
    }

    private function getStatusCode(?HttpOperation $apiOperation, Request $request, ?HttpOperation $errorOperation, \Throwable $exception): int
    {
        $exceptionToStatus = array_merge(
            $this->exceptionToStatus,
            $apiOperation ? $apiOperation->getExceptionToStatus() ?? [] : $this->getOperationExceptionToStatus($request),
            $errorOperation ? $errorOperation->getExceptionToStatus() ?? [] : []
        );

        foreach ($exceptionToStatus as $class => $status) {
            if (is_a($exception::class, $class, true)) {
                return $status;
            }
        }

        if ($exception instanceof SymfonyHttpExceptionInterface) {
            return $exception->getStatusCode();
        }

        if ($exception instanceof RequestExceptionInterface) {
            return 400;
        }

        if ($exception instanceof ValidationException) {
            return 422;
        }

        if ($status = $errorOperation?->getStatus()) {
            return $status;
        }

        return 500;
    }

    private function getFormatOperation(string $format): ?string
    {
        return match ($format) {
            'jsonproblem' => '_api_errors_problem',
            'jsonld' => '_api_errors_hydra',
            'jsonapi' => '_api_errors_jsonapi',
            default => null
        };
    }
}
