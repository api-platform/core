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
use ApiPlatform\Metadata\Exception\StatusAwareExceptionInterface;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\IdentifiersExtractorInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Metadata\Util\ContentNegotiationTrait;
use ApiPlatform\State\Util\OperationRequestInitiatorTrait;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Container\Container;
use Illuminate\Foundation\Exceptions\Handler as ExceptionsHandler;
use Negotiation\Negotiator;
use Symfony\Component\HttpFoundation\Exception\RequestExceptionInterface;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface as SymfonyHttpExceptionInterface;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

class ErrorHandler extends ExceptionsHandler
{
    use ContentNegotiationTrait;
    use OperationRequestInitiatorTrait;

    public static mixed $error;

    /**
     * @param array<class-string, int> $exceptionToStatus
     * @param array<string, string[]>  $errorFormats
     */
    public function __construct(
        Container $container,
        ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory,
        private readonly ApiPlatformController $apiPlatformController,
        private readonly ?IdentifiersExtractorInterface $identifiersExtractor = null,
        private readonly ?ResourceClassResolverInterface $resourceClassResolver = null,
        ?Negotiator $negotiator = null,
        private readonly ?array $exceptionToStatus = null,
        private readonly ?bool $debug = false,
        private readonly ?array $errorFormats = null,
    ) {
        $this->resourceMetadataCollectionFactory = $resourceMetadataCollectionFactory;
        $this->negotiator = $negotiator;
        parent::__construct($container);
    }

    public function render($request, \Throwable $exception)
    {
        $apiOperation = $this->initializeOperation($request);

        if (!$apiOperation) {
            return parent::render($request, $exception);
        }

        $formats = $this->errorFormats ?? ['jsonproblem' => ['application/problem+json']];
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
                if ($errorResource instanceof StatusAwareExceptionInterface) {
                    $errorResource->setStatus($statusCode);
                }
            }
        } else {
            // Create a generic, rfc7807 compatible error according to the wanted format
            $operation = $this->resourceMetadataCollectionFactory->create(Error::class)->getOperation($this->getFormatOperation($format));
            // status code may be overridden by the exceptionToStatus option
            $statusCode = 500;
            if ($operation instanceof HttpOperation) {
                $statusCode = $this->getStatusCode($apiOperation, $operation, $exception);
                $operation = $operation->withStatus($statusCode);
            }

            $errorResource = Error::createFromException($exception, $statusCode);
        }

        /** @var HttpOperation $operation */
        if (!$operation->getProvider()) {
            static::$error = $errorResource;
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

        $normalizationContext = $operation->getNormalizationContext() ?? [];
        if (!($normalizationContext['api_error_resource'] ?? false)) {
            $normalizationContext += ['api_error_resource' => true];
        }

        if (!isset($normalizationContext[AbstractObjectNormalizer::IGNORED_ATTRIBUTES])) {
            $normalizationContext[AbstractObjectNormalizer::IGNORED_ATTRIBUTES] = true === $this->debug ? [] : ['originalTrace'];
        }

        $operation = $operation->withNormalizationContext($normalizationContext);

        $dup = $request->duplicate(null, null, []);
        $dup->setMethod('GET');
        $dup->attributes->set('_api_resource_class', $operation->getClass());
        $dup->attributes->set('_api_previous_operation', $apiOperation);
        $dup->attributes->set('_api_operation', $operation);
        $dup->attributes->set('_api_operation_name', $operation->getName());
        $dup->attributes->set('exception', $exception);
        // These are for swagger
        $dup->attributes->set('_api_original_route', $request->attributes->get('_route'));
        $dup->attributes->set('_api_original_uri_variables', $request->attributes->get('_api_uri_variables'));
        $dup->attributes->set('_api_original_route_params', $request->attributes->get('_route_params'));
        $dup->attributes->set('_api_requested_operation', $request->attributes->get('_api_requested_operation'));

        foreach ($identifiers as $name => $value) {
            $dup->attributes->set($name, $value);
        }

        try {
            return $this->apiPlatformController->__invoke($dup);
        } catch (\Throwable $e) {
            return parent::render($dup, $e);
        }
    }

    private function getStatusCode(?HttpOperation $apiOperation, ?HttpOperation $errorOperation, \Throwable $exception): int
    {
        $exceptionToStatus = array_merge(
            $apiOperation ? $apiOperation->getExceptionToStatus() ?? [] : [],
            $errorOperation ? $errorOperation->getExceptionToStatus() ?? [] : [],
            $this->exceptionToStatus ?? []
        );

        foreach ($exceptionToStatus as $class => $status) {
            if (is_a($exception::class, $class, true)) {
                return $status;
            }
        }

        if ($exception instanceof AuthenticationException) {
            return 401;
        }

        if ($exception instanceof AuthorizationException) {
            return 403;
        }

        if ($exception instanceof SymfonyHttpExceptionInterface) {
            return $exception->getStatusCode();
        }

        if ($exception instanceof SymfonyHttpExceptionInterface) {
            return $exception->getStatusCode();
        }

        if ($exception instanceof RequestExceptionInterface) {
            return 400;
        }

        // if ($exception instanceof ValidationException) {
        //     return 422;
        // }

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
            default => '_api_errors_problem',
        };
    }

    public static function provide(): mixed
    {
        if ($data = static::$error) {
            return $data;
        }

        throw new \LogicException(\sprintf('We could not find the thrown exception in the %s.', self::class));
    }
}
