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

use ApiPlatform\Api\IdentifiersExtractorInterface as LegacyIdentifiersExtractorInterface;
use ApiPlatform\Api\ResourceClassResolverInterface as LegacyResourceClassResolverInterface;
use ApiPlatform\ApiResource\Error;
use ApiPlatform\Metadata\Error as ErrorOperation;
use ApiPlatform\Metadata\Exception\ProblemExceptionInterface;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\IdentifiersExtractorInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Metadata\Util\ContentNegotiationTrait;
use ApiPlatform\State\Util\OperationRequestInitiatorTrait;
use ApiPlatform\Symfony\Util\RequestAttributesExtractor;
use ApiPlatform\Symfony\Validator\Exception\ConstraintViolationListAwareExceptionInterface;
use ApiPlatform\Validator\Exception\ValidationException;
use Negotiation\Negotiator;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Exception\RequestExceptionInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\EventListener\ErrorListener as SymfonyErrorListener;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface as SymfonyHttpExceptionInterface;

/**
 * This error listener extends the Symfony one in order to add
 * the `_api_operation` attribute when the request is duplicated.
 * It will later be used to retrieve the exceptionToStatus from the operation ({@see ApiPlatform\Action\ExceptionAction}).
 *
 * @internal since API Platform 3.2
 */
final class ErrorListener extends SymfonyErrorListener
{
    use ContentNegotiationTrait;
    use OperationRequestInitiatorTrait;
    private static mixed $error;

    public function __construct(
        object|array|string|null $controller,
        LoggerInterface $logger = null,
        bool $debug = false,
        array $exceptionsMapping = [],
        ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory = null,
        private readonly array $errorFormats = [],
        private readonly array $exceptionToStatus = [],
        private readonly null|IdentifiersExtractorInterface|LegacyIdentifiersExtractorInterface $identifiersExtractor = null,
        private readonly null|ResourceClassResolverInterface|LegacyResourceClassResolverInterface $resourceClassResolver = null,
        Negotiator $negotiator = null,
        private readonly bool $problemCompliantErrors = true
    ) {
        parent::__construct($controller, $logger, $debug, $exceptionsMapping);
        $this->resourceMetadataCollectionFactory = $resourceMetadataCollectionFactory;
        $this->negotiator = $negotiator ?? new Negotiator();
    }

    protected function duplicateRequest(\Throwable $exception, Request $request): Request
    {
        $apiOperation = $this->initializeOperation($request);
        if (false === $this->problemCompliantErrors) {
            // TODO: deprecate in API Platform 3.3
            $this->controller = 'api_platform.action.exception';
            $dup = parent::duplicateRequest($exception, $request);
            $dup->attributes->set('_api_operation', $apiOperation);

            return $dup;
        }

        if ($this->debug) {
            $this->logger?->error('An exception occured, transforming to an Error resource.', ['exception' => $exception, 'operation' => $apiOperation]);
        }

        $format = $this->getRequestFormat($request, $this->errorFormats, false);

        // Let the error handler take this we don't handle HTML
        if ('html' === $format) {
            $this->controller = 'error_controller';
            $dup = parent::duplicateRequest($exception, $request);

            return $dup;
        }

        $dup = parent::duplicateRequest($exception, $request);
        if ($this->resourceMetadataCollectionFactory) {
            if ($this->resourceClassResolver?->isResourceClass($exception::class)) {
                $resourceCollection = $this->resourceMetadataCollectionFactory->create($exception::class);

                $operation = null;
                foreach ($resourceCollection as $resource) {
                    foreach ($resource->getOperations() as $op) {
                        foreach ($op->getOutputFormats() as $key => $value) {
                            if ($key === $format) {
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
                if ($errorResource instanceof ProblemExceptionInterface && $operation instanceof HttpOperation) {
                    $statusCode = $this->getStatusCode($apiOperation, $request, $operation, $exception);
                    $operation = $operation->withStatus($statusCode);
                    $errorResource->setStatus($statusCode);
                }
            } else {
                // Create a generic, rfc7807 compatible error according to the wanted format
                $operation = $this->resourceMetadataCollectionFactory->create(Error::class)->getOperation($this->getFormatOperation($format));
                // status code may be overriden by the exceptionToStatus option
                $statusCode = 500;
                if ($operation instanceof HttpOperation) {
                    $statusCode = $this->getStatusCode($apiOperation, $request, $operation, $exception);
                    $operation = $operation->withStatus($statusCode);
                }

                $errorResource = Error::createFromException($exception, $statusCode);
            }
        } else {
            /** @var HttpOperation $operation */
            $operation = new ErrorOperation(name: '_api_errors_problem', class: Error::class, outputFormats: ['jsonld' => ['application/problem+json']], normalizationContext: ['groups' => ['jsonld'], 'skip_null_values' => true]);
            $operation = $operation->withStatus($this->getStatusCode($apiOperation, $request, $operation, $exception));
            $errorResource = Error::createFromException($exception, $operation->getStatus());
        }

        if (!$operation->getProvider()) {
            static::$error = 'jsonapi' === $format && $errorResource instanceof ConstraintViolationListAwareExceptionInterface ? $errorResource->getConstraintViolationList() : $errorResource;
            $operation = $operation->withProvider([self::class, 'provide']);
        }

        /** @var HttpOperation $operation */
        if (!$this->debug && $operation->getStatus() >= 500 && $errorResource instanceof Error) {
            $errorResource->setDetail('Internal Server Error');
        }

        $identifiers = [];
        try {
            $identifiers = $this->identifiersExtractor?->getIdentifiersFromItem($errorResource, $operation) ?? [];
        } catch (\Exception $e) {
        }

        if ($exception instanceof ValidationException && !($apiOperation?->getExtraProperties()['rfc_7807_compliant_errors'] ?? false)) {
            $operation = $operation->withNormalizationContext([
                'groups' => ['legacy_'.$format],
                'force_iri_generation' => false,
            ]);
        }

        if ($apiOperation && 'jsonld' === $format && !($apiOperation->getExtraProperties()['rfc_7807_compliant_errors'] ?? false)) {
            $operation = $operation->withOutputFormats(['jsonld' => ['application/ld+json']])
                                   ->withLinks([])
                                   ->withExtraProperties(['rfc_7807_compliant_errors' => false] + $operation->getExtraProperties());
        }

        $dup->attributes->set('_api_resource_class', $operation->getClass());
        $dup->attributes->set('_api_previous_operation', $apiOperation);
        $dup->attributes->set('_api_operation', $operation);
        $dup->attributes->set('_api_operation_name', $operation->getName());
        $dup->attributes->set('exception', $exception);
        // These are for swagger
        $dup->attributes->set('_api_original_route', $request->attributes->get('_route'));
        $dup->attributes->set('_api_original_route_params', $request->attributes->get('_route_params'));
        $dup->attributes->set('_api_requested_operation', $request->attributes->get('_api_requested_operation'));

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

    private function getFormatOperation(?string $format): string
    {
        return match ($format) {
            'json' => '_api_errors_problem',
            'jsonproblem' => '_api_errors_problem',
            'jsonld' => '_api_errors_hydra',
            'jsonapi' => '_api_errors_jsonapi',
            'html' => '_api_errors_problem', // This will be intercepted by the SwaggerUiProvider
            default => '_api_errors_problem'
        };
    }

    public static function provide(): mixed
    {
        if ($data = static::$error) {
            return $data;
        }

        throw new \LogicException(sprintf('We could not find the thrown exception in the %s.', self::class));
    }
}
