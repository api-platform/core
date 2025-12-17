<?php

/*
 * This file is part of the official PHP MCP SDK.
 *
 * A collaboration between Symfony and the PHP Foundation.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Mcp\Server;

use ApiPlatform\Mcp\State\ToolProvider;
use ApiPlatform\Metadata\Operation\Factory\OperationMetadataFactoryInterface;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\State\ProviderInterface;
use Mcp\Capability\Registry\ReferenceHandlerInterface;
use Mcp\Capability\RegistryInterface;
use Mcp\Exception\ToolCallException;
use Mcp\Exception\ToolNotFoundException;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\JsonRpc\Error;
use Mcp\Schema\JsonRpc\Request;
use Mcp\Schema\JsonRpc\Response;
use Mcp\Schema\Request\CallToolRequest;
use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Handler\Request\RequestHandlerInterface;
use Mcp\Server\Session\SessionInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @implements RequestHandlerInterface<CallToolResult>
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
        return $request instanceof CallToolRequest;
    }

    /**
     * @return Response<CallToolResult>|Error
     */
    public function handle(Request $request, SessionInterface $session): Response|Error
    {
        \assert($request instanceof CallToolRequest);

        $toolName = $request->name;
        $arguments = $request->arguments ?? [];

        $this->logger->debug('Executing tool', ['name' => $toolName, 'arguments' => $arguments]);

        $operation = $this->operationMetadataFactory->create($toolName);

        $uriVariables = [];
        foreach ($operation->getUriVariables() ?? [] as $key => $link) {
            if (isset($arguments[$key])) {
                $uriVariables[$key] = $arguments[$key];
            }
        }

        $context = [
            'request' => ($httpRequest = $this->requestStack->getCurrentRequest()),
            'mcp_request' => $request,
            'uri_variables' => $uriVariables,
            'resource_class' => $operation->getClass(),
            'mcp_data' => $arguments,
        ];

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

        $context['previous_data'] = $httpRequest->attributes->get('previous_data');
        $context['data'] = $httpRequest->attributes->get('data');
        $context['read_data'] = $httpRequest->attributes->get('read_data');
        $context['mapped_data'] = $httpRequest->attributes->get('mapped_data');

        if (null === $operation->canWrite()) {
            $operation = $operation->withWrite(true);
        }

        if (null === $operation->canSerialize()) {
            $operation = $operation->withSerialize(false);
        }

        return $this->processor->process($body, $operation, $uriVariables, $context);
    }
}
