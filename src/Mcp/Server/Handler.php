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

namespace ApiPlatform\Mcp\Server;

use ApiPlatform\Mcp\JsonSchema\ToolInputSchemaFactory;
use ApiPlatform\Mcp\State\ToolProvider;
use ApiPlatform\Metadata\Exception\AccessDeniedException;
use ApiPlatform\Metadata\Exception\HttpExceptionInterface;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\McpTool;
use ApiPlatform\Metadata\Operation\Factory\OperationMetadataFactoryInterface;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\State\ProviderInterface;
use Mcp\Capability\Discovery\SchemaValidator;
use Mcp\Schema\JsonRpc\Error;
use Mcp\Schema\JsonRpc\Request;
use Mcp\Schema\JsonRpc\Response;
use Mcp\Schema\Request\CallToolRequest;
use Mcp\Schema\Request\ReadResourceRequest;
use Mcp\Schema\Result\CallToolResult;
use Mcp\Schema\Result\ReadResourceResult;
use Mcp\Server\Handler\Request\RequestHandlerInterface;
use Mcp\Server\Session\SessionInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface as SymfonyHttpExceptionInterface;

/**
 * @experimental
 *
 * @implements RequestHandlerInterface<CallToolResult|ReadResourceResult>
 */
final class Handler implements RequestHandlerInterface
{
    private readonly SchemaValidator $schemaValidator;

    /**
     * @param array<class-string, int> $exceptionToStatus
     */
    public function __construct(
        private readonly OperationMetadataFactoryInterface $operationMetadataFactory,
        private readonly ProviderInterface $provider,
        private readonly ProcessorInterface $processor,
        private readonly RequestStack $requestStack,
        private readonly ToolInputSchemaFactory $inputSchemaFactory,
        private readonly LoggerInterface $logger = new NullLogger(),
        private readonly array $exceptionToStatus = [],
        ?SchemaValidator $schemaValidator = null,
    ) {
        $this->schemaValidator = $schemaValidator ?? new SchemaValidator(logger: $logger);
    }

    public function supports(Request $request): bool
    {
        if ($request instanceof CallToolRequest) {
            return null !== $this->operationMetadataFactory->create($request->name);
        }

        if ($request instanceof ReadResourceRequest) {
            return null !== $this->operationMetadataFactory->create($request->uri);
        }

        return false;
    }

    /**
     * @return Response<CallToolResult|ReadResourceResult>|Error
     */
    public function handle(Request $request, SessionInterface $session): Response|Error
    {
        $isResource = $request instanceof ReadResourceRequest;

        if ($isResource) {
            $operationNameOrUri = $request->uri;
            $arguments = [];
            $this->logger->debug('Reading resource', ['uri' => $operationNameOrUri]);
        } else {
            \assert($request instanceof CallToolRequest);
            $operationNameOrUri = $request->name;
            $arguments = $request->arguments;
            $this->logger->debug('Executing tool', ['name' => $operationNameOrUri, 'arguments' => $arguments]);
        }

        /** @var HttpOperation|null $operation */
        $operation = $this->operationMetadataFactory->create($operationNameOrUri);

        if (null === $operation) {
            return Error::forMethodNotFound(\sprintf('MCP operation "%s" not found.', $operationNameOrUri), $request->getId());
        }

        if (!$isResource && $operation instanceof McpTool) {
            $validationErrors = $this->schemaValidator->validateAgainstJsonSchema([] === $arguments ? new \stdClass() : $arguments, $this->inputSchemaFactory->create($operation));
            if ([] !== $validationErrors) {
                if (\in_array('internal', array_column($validationErrors, 'keyword'), true)) {
                    $this->logger->error('Tool input schema validation failed internally.', ['tool' => $operationNameOrUri, 'validation_errors' => $validationErrors]);

                    return Error::forInternalError('Internal server error.', $request->getId());
                }

                return Error::forInvalidParams(
                    "Invalid parameters for tool '{$operationNameOrUri}': ".self::summarizeValidationErrors($validationErrors),
                    $request->getId(),
                    ['validation_errors' => $validationErrors]
                );
            }
        }

        $uriVariables = [];
        if (!$isResource) {
            foreach ($operation->getUriVariables() ?? [] as $key => $link) {
                if (isset($arguments[$key])) {
                    $uriVariables[$key] = $arguments[$key];
                }
            }
        }

        $context = [
            'request' => $this->requestStack->getCurrentRequest(),
            'mcp_request' => $request,
            'mcp_session' => $session,
            'uri_variables' => $uriVariables,
            'resource_class' => $operation->getClass(),
        ];

        if (!$isResource) {
            $context['mcp_data'] = $arguments;
        }

        $operation = $operation->withExtraProperties(
            array_merge($operation->getExtraProperties(), ['_api_disable_swagger_provider' => true])
        );

        // MCP has its own transport (JSON-RPC) — HTTP content negotiation is irrelevant.
        if (null === $operation->canNegotiateContent()) {
            $operation = $operation->withContentNegotiation(false);
        }

        if (null === $operation->canValidate()) {
            $operation = $operation->withValidate(false);
        }

        if (null === $operation->canRead()) {
            $operation = $operation->withRead(true);
        }

        if (null === $operation->getProvider()) {
            $operation = $operation->withProvider(ToolProvider::class);
        }

        if (null === $operation->canDeserialize()) {
            $operation = $operation->withDeserialize(false);
        }

        // The MCP transport has no HTTP response to carry a status code, so a caller-facing
        // HTTP exception (e.g. access denied, validation, not found) is converted into a JSON-RPC
        // error carrying its message. Both API Platform's and Symfony's HttpExceptionInterface
        // qualify: throwing Symfony's NotFoundHttpException from a state provider is a documented
        // idiom, and API Platform has no 404 equivalent of its own. So is any exception mapped to
        // a client error status (< 500) through exception_to_status. Anything else is rethrown and
        // reaches the SDK's own generic handler, which does not leak arbitrary exception messages
        // to the client.
        try {
            $body = $this->provider->provide($operation, $uriVariables, $context);
        } catch (\Throwable $e) {
            return $this->toError($e, $operation, $request);
        }

        if (!$isResource && null !== ($httpRequest = $context['request'] ?? null)) {
            $context['previous_data'] = $httpRequest->attributes->get('previous_data');
            $context['data'] = $httpRequest->attributes->get('data');
            $context['read_data'] = $httpRequest->attributes->get('read_data');
            $context['mapped_data'] = $httpRequest->attributes->get('mapped_data');
        }

        if (null === $operation->canWrite()) {
            $operation = $operation->withWrite(true);
        }

        if (null === $operation->canSerialize()) {
            $operation = $operation->withSerialize(false);
        }

        try {
            return $this->processor->process($body, $operation, $uriVariables, $context);
        } catch (\Throwable $e) {
            return $this->toError($e, $operation, $request);
        }
    }

    private function toError(\Throwable $e, HttpOperation $operation, Request $request): Error
    {
        if (!$e instanceof HttpExceptionInterface && !$e instanceof SymfonyHttpExceptionInterface) {
            $status = $this->getMappedStatus($e, $operation);
            if (null === $status || $status >= 500) {
                throw $e;
            }
        }

        return Error::forInternalError($this->getPublicErrorMessage($e), $request->getId());
    }

    private function getMappedStatus(\Throwable $e, HttpOperation $operation): ?int
    {
        $exceptionToStatus = array_merge($this->exceptionToStatus, $operation->getExceptionToStatus() ?? []);

        foreach ($exceptionToStatus as $class => $status) {
            if (is_a($e::class, $class, true)) {
                return $status;
            }
        }

        return null;
    }

    private function getPublicErrorMessage(\Throwable $exception): string
    {
        $current = $exception;
        while (null !== $current) {
            if ($current instanceof AccessDeniedException) {
                return $current->getDetail() ?? 'Access Denied.';
            }

            $current = $current->getPrevious();
        }

        return $exception->getMessage();
    }

    /**
     * @param list<array{pointer?: string, message?: string}> $validationErrors
     */
    private static function summarizeValidationErrors(array $validationErrors): string
    {
        $errorMessages = [];

        foreach ($validationErrors as $errorDetail) {
            $pointer = $errorDetail['pointer'] ?? '';
            $message = $errorDetail['message'] ?? 'Unknown validation error';
            $errorMessages[] = ('/' !== $pointer && '' !== $pointer ? "Property '{$pointer}': " : '').$message;
        }

        $summary = implode('; ', \array_slice($errorMessages, 0, 3));
        if (\count($errorMessages) > 3) {
            $summary .= '; ...and more errors.';
        }

        return $summary;
    }
}
