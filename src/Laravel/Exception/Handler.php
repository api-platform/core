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

namespace ApiPlatform\Laravel\Exception;

use ApiPlatform\Laravel\ApiResource\Error;
use ApiPlatform\Laravel\Controller\ApiPlatformController;
use ApiPlatform\Metadata\Exception\ProblemExceptionInterface;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\IdentifiersExtractorInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Metadata\Util\ContentNegotiationTrait;
use ApiPlatform\State\Util\OperationRequestInitiatorTrait;
use ApiPlatform\Symfony\Validator\Exception\ConstraintViolationListAwareExceptionInterface;
use ApiPlatform\Validator\Exception\ValidationException;
use Illuminate\Contracts\Container\Container;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Negotiation\Negotiator;
use Symfony\Component\HttpFoundation\Exception\RequestExceptionInterface;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface as SymfonyHttpExceptionInterface;

class Handler extends ExceptionHandler
{
    use ContentNegotiationTrait;
    use OperationRequestInitiatorTrait;
    private static mixed $error;

    public function __construct(
        Container $container,
        ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory,
        private readonly ApiPlatformController $apiPlatformController,
        private readonly ?IdentifiersExtractorInterface $identifiersExtractor = null,
        private readonly ?ResourceClassResolverInterface $resourceClassResolver = null,
        Negotiator $negotiator = null
    ) {
        $this->container = $container;
        $this->resourceMetadataCollectionFactory = $resourceMetadataCollectionFactory;
        $this->negotiator = $negotiator;
        parent::__construct($container);
    }

    public function register(): void
    {
        $this->renderable(function (\Throwable $exception, Request $request) {
            $apiOperation = $this->initializeOperation($request);
            if (!$apiOperation) {
                return null;
            }

            $formats = config('api-platform.error_formats') ?? ['jsonproblem' => ['application/problem+json']];
            $format = $request->getRequestFormat() ?? $this->getRequestFormat($request, $formats, false);

            if ($this->resourceClassResolver->isResourceClass($exception::class)) {
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
                    $statusCode = $this->getStatusCode($apiOperation, $operation, $exception);
                    $operation = $operation->withStatus($statusCode);
                    $errorResource->setStatus($statusCode);
                }
            } else {
                // Create a generic, rfc7807 compatible error according to the wanted format
                $operation = $this->resourceMetadataCollectionFactory->create(Error::class)->getOperation($this->getFormatOperation($format));
                // status code may be overriden by the exceptionToStatus option
                $statusCode = 500;
                if ($operation instanceof HttpOperation) {
                    $statusCode = $this->getStatusCode($apiOperation, $operation, $exception);
                    $operation = $operation->withStatus($statusCode);
                }

                $errorResource = Error::createFromException($exception, $statusCode);
            }

            if (!$operation->getProvider()) {
                static::$error = 'jsonapi' === $format && $errorResource instanceof ConstraintViolationListAwareExceptionInterface ? $errorResource->getConstraintViolationList() : $errorResource;
                $operation = $operation->withProvider([self::class, 'provide']);
            }

            // For our swagger Ui errors
            if ('html' === $format) {
                $operation = $operation->withOutputFormats(['html' => ['text/html']]);
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

            if ('jsonld' === $format && !($apiOperation?->getExtraProperties()['rfc_7807_compliant_errors'] ?? false)) {
                $operation = $operation->withOutputFormats(['jsonld' => ['application/ld+json']])
                                       ->withLinks([])
                                       ->withExtraProperties(['rfc_7807_compliant_errors' => false] + $operation->getExtraProperties());
            }

            $dup = $request->duplicate(null, null, []);
            $dup->setMethod('GET');
            $dup->attributes->set('_api_resource_class', $operation->getClass());
            $dup->attributes->set('_api_previous_operation', $apiOperation);
            $dup->attributes->set('_api_operation', $operation);
            $dup->attributes->set('_api_operation_name', $operation->getName());
            $dup->attributes->remove('exception');
            // These are for swagger
            $dup->attributes->set('_api_original_route', $request->attributes->get('_route'));
            $dup->attributes->set('_api_original_route_params', $request->attributes->get('_route_params'));
            $dup->attributes->set('_api_requested_operation', $request->attributes->get('_api_requested_operation'));

            foreach ($identifiers as $name => $value) {
                $dup->attributes->set($name, $value);
            }

            return $this->apiPlatformController->__invoke($dup);
        });
    }

    private function getStatusCode(?HttpOperation $apiOperation, ?HttpOperation $errorOperation, \Throwable $exception): int
    {
        $exceptionToStatus = array_merge(
            $apiOperation ? $apiOperation->getExceptionToStatus() ?? [] : [],
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
