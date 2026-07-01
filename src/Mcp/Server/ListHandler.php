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

use Mcp\Capability\Registry\Loader\LoaderInterface;
use Mcp\Capability\RegistryInterface;
use Mcp\Schema\JsonRpc\Request;
use Mcp\Schema\JsonRpc\Response;
use Mcp\Schema\Request\ListResourcesRequest;
use Mcp\Schema\Request\ListToolsRequest;
use Mcp\Schema\Result\ListResourcesResult;
use Mcp\Schema\Result\ListToolsResult;
use Mcp\Server\Handler\Request\RequestHandlerInterface;
use Mcp\Server\Session\SessionInterface;

/**
 * Serves tools/list and resources/list from the MCP registry, loading API Platform elements
 * into it on first use.
 *
 * The SDK populates the registry once, when mcp.server is built. Under a persistent runtime
 * (e.g. FrankenPHP worker mode) that single build can capture an empty registry (cold metadata
 * cache) and stays empty for the whole process, so tools/list returns [] while tools/call keeps
 * working through the request-time {@see Handler}. Loading the API Platform elements lazily here
 * heals that: it runs once per process (registrations are idempotent by name) and reads back
 * through the shared registry, so runtime registrations and registry decorators are preserved.
 *
 * Tagged mcp.request_handler, it takes precedence over the SDK's registry-backed list handlers.
 *
 * @experimental
 * TODO: remove once php-sdk:^0.7 has https://github.com/modelcontextprotocol/php-sdk/pull/389/changes
 *
 * @implements RequestHandlerInterface<ListToolsResult|ListResourcesResult>
 */
final class ListHandler implements RequestHandlerInterface
{
    private bool $loaded = false;

    public function __construct(
        private readonly RegistryInterface $registry,
        private readonly LoaderInterface $loader,
        private readonly int $pageSize = 20,
    ) {
    }

    public function supports(Request $request): bool
    {
        return $request instanceof ListToolsRequest || $request instanceof ListResourcesRequest;
    }

    /**
     * @return Response<ListToolsResult|ListResourcesResult>
     */
    public function handle(Request $request, SessionInterface $session): Response
    {
        if (!$this->loaded) {
            $this->loader->load($this->registry);
            $this->loaded = true;
        }

        if ($request instanceof ListResourcesRequest) {
            $page = $this->registry->getResources($this->pageSize, $request->cursor);
            $result = new ListResourcesResult($page->references, $page->nextCursor);
        } else {
            \assert($request instanceof ListToolsRequest);
            $page = $this->registry->getTools($this->pageSize, $request->cursor);
            $result = new ListToolsResult($page->references, $page->nextCursor);
        }

        return new Response($request->getId(), $result);
    }
}
