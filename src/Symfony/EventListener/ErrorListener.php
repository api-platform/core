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
use ApiPlatform\Metadata\Error as ErrorOperation;
use ApiPlatform\Metadata\Exception\HttpExceptionInterface;
use ApiPlatform\Metadata\Exception\ProblemExceptionInterface;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\IdentifiersExtractorInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Metadata\Util\ContentNegotiationTrait;
use ApiPlatform\State\ApiResource\Error;
use ApiPlatform\State\Util\OperationRequestInitiatorTrait;
use ApiPlatform\Symfony\Util\RequestAttributesExtractor;
use ApiPlatform\Validator\Exception\ValidationException;
use Negotiation\Negotiator;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Exception\RequestExceptionInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\EventListener\ErrorListener as SymfonyErrorListener;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface as SymfonyHttpExceptionInterface;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

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

    public function __construct(
        object|array|string|null $controller,
        ?LoggerInterface $logger = null,
        bool $debug = false,
        array $exceptionsMapping = [],
        ?ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory = null,
        private readonly array $errorFormats = [],
        private readonly array $exceptionToStatus = [],
        /** @phpstan-ignore-next-line we're not using this anymore but keeping for bc layer */
        private readonly IdentifiersExtractorInterface|LegacyIdentifiersExtractorInterface|null $identifiersExtractor = null,
        private readonly ResourceClassResolverInterface|LegacyResourceClassResolverInterface|null $resourceClassResolver = null,
        ?Negotiator $negotiator = null,
        private readonly bool $problemCompliantErrors = true,
    ) {
        parent::__construct($controller, $logger, $debug, $exceptionsMapping);
        $this->resourceMetadataCollectionFactory = $resourceMetadataCollectionFactory;
        $this->negotiator = $negotiator ?? new Negotiator();
    }

    protected function duplicateRequest(\Throwable $exception, Request $request): Request
    {
        $format = $this->getRequestFormat($request, $this->errorFormats, false);
        // Because ErrorFormatGuesser is buggy in some cases
        $request->setRequestFormat($format);
        $apiOperation = $this->initializeOperation($request);

        // TODO: add configuration flag to:
        //   - always use symfony error handler (skips this listener)
        //   - use symfony error handler if it's not an api error, ie apiOperation is null
        //   - use api platform to handle errors (the default behavior we handle firewall errors for example but they're out of our scope)

        // Let the error handler take this we don't handle HTML nor non-api platform requests
        if ('html' === $format) {
            $this->controller = 'error_controller';

            return parent::duplicateRequest($exception, $request);
        }

        $legacy = $apiOperation ? ($apiOperation->getExtraProperties()['rfc_7807_compliant_errors'] ?? false) : $this->problemCompliantErrors;

        if (!$this->problemCompliantErrors || !$legacy) {
            // TODO: deprecate in API Platform 3.3
            $this->controller = 'api_platform.action.exception';
            $dup = parent::duplicateRequest($exception, $request);
            $dup->attributes->set('_api_operation', $apiOperation);
            $dup->attributes->set('_api_exception_action', true);

            return $dup;
        }

        if ($this->debug) {
            $this->logger?->error('An exception occured, transforming to an Error resource.', ['exception' => $exception, 'operation' => $apiOperation]);
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
                if ($exception instanceof ProblemExceptionInterface && $operation instanceof HttpOperation) {
                    $statusCode = $this->getStatusCode($apiOperation, $request, $operation, $exception);
                    $operation = $operation->withStatus($statusCode);
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
            }
        } else {
            /** @var HttpOperation $operation */
            $operation = new ErrorOperation(name: '_api_errors_problem', class: Error::class, outputFormats: ['jsonld' => ['application/problem+json']], normalizationContext: ['groups' => ['jsonld'], 'skip_null_values' => true]);
            $operation = $operation->withStatus($this->getStatusCode($apiOperation, $request, $operation, $exception));
        }

        if (null === $operation->getProvider()) {
            $operation = $operation->withProvider('api_platform.state.error_provider');
        }

        $normalizationContext = $operation->getNormalizationContext() ?? [];
        if (!($normalizationContext['_api_error_resource'] ?? false)) {
            $normalizationContext += ['api_error_resource' => true];
        }

        if (!isset($normalizationContext[AbstractObjectNormalizer::IGNORED_ATTRIBUTES])) {
            $normalizationContext[AbstractObjectNormalizer::IGNORED_ATTRIBUTES] = ['trace', 'file', 'line', 'code', 'message', 'traceAsString'];
        }

        $operation = $operation->withNormalizationContext($normalizationContext);

        $dup->attributes->set('_api_resource_class', $operation->getClass());
        $dup->attributes->set('_api_previous_operation', $apiOperation);
        $dup->attributes->set('_api_operation', $operation);
        $dup->attributes->set('_api_operation_name', $operation->getName());
        $dup->attributes->set('exception', $exception);
        // These are for swagger
        $dup->attributes->set('_api_original_route', $request->attributes->get('_route'));
        $dup->attributes->set('_api_original_route_params', $request->attributes->get('_route_params'));
        $dup->attributes->set('_api_requested_operation', $request->attributes->get('_api_requested_operation'));
        $dup->attributes->set('_api_platform_disable_listeners', true);

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
            /* @var \ApiPlatform\Metadata\ApiResource; $resourceMetadata */
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

        if ($exception instanceof ProblemExceptionInterface && $status = $exception->getStatus()) {
            return $status;
        }

        if ($exception instanceof HttpExceptionInterface) {
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
}
