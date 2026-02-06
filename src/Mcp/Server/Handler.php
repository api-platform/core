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

use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operation\Factory\OperationMetadataFactoryInterface;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\State\ProviderInterface;
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

/**
 * @experimental
 *
 * @implements RequestHandlerInterface<CallToolResult|ReadResourceResult>
 */
final class Handler implements RequestHandlerInterface
{
    public function __construct(
        private readonly OperationMetadataFactoryInterface $operationMetadataFactory,
        private readonly ProviderInterface $provider,
        private readonly ProcessorInterface $processor,
        private readonly RequestStack $requestStack,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public function supports(Request $request): bool
    {
        return $request instanceof CallToolRequest || $request instanceof ReadResourceRequest;
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
            $arguments = $request->arguments ?? [];
            $this->logger->debug('Executing tool', ['name' => $operationNameOrUri, 'arguments' => $arguments]);
        }

        /** @var HttpOperation $operation */
        $operation = $this->operationMetadataFactory->create($operationNameOrUri);

        $uriVariables = [];
        if (!$isResource) {
            foreach ($operation->getUriVariables() ?? [] as $key => $link) {
                if (isset($arguments[$key])) {
                    $uriVariables[$key] = $arguments[$key];
                }
            }
        }

        $context = [
            'request' => ($httpRequest = $this->requestStack->getCurrentRequest()),
            'mcp_request' => $request,
            'uri_variables' => $uriVariables,
            'resource_class' => $operation->getClass(),
        ];

        if (!$isResource) {
            $context['mcp_data'] = $arguments;
        }

        if (null === $operation->canValidate()) {
            $operation = $operation->withValidate(false);
        }

        if (null === $operation->canRead()) {
            $operation = $operation->withRead(true);
        }

        if (null === $operation->getProvider()) {
            $operation = $operation->withProvider('api_platform.mcp.state.tool_provider');
        }

        if (null === $operation->canDeserialize()) {
            $operation = $operation->withDeserialize(false);
        }

        $body = $this->provider->provide($operation, $uriVariables, $context);

        if (!$isResource) {
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

        return $this->processor->process($body, $operation, $uriVariables, $context);
    }
}
