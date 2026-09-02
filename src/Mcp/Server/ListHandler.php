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

use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\Operation\Factory\OperationMetadataFactoryInterface;
use ApiPlatform\Metadata\ResourceAccessCheckerInterface;
use Mcp\Capability\Registry\Loader\LoaderInterface;
use Mcp\Capability\RegistryInterface;
use Mcp\Schema\JsonRpc\Request;
use Mcp\Schema\JsonRpc\Response;
use Mcp\Schema\Request\ListResourcesRequest;
use Mcp\Schema\Request\ListToolsRequest;
use Mcp\Schema\ResourceDefinition;
use Mcp\Schema\Result\ListResourcesResult;
use Mcp\Schema\Result\ListToolsResult;
use Mcp\Schema\Tool;
use Mcp\Server\Handler\Request\RequestHandlerInterface;
use Mcp\Server\Session\SessionInterface;
use Symfony\Component\ExpressionLanguage\SyntaxError;
use Symfony\Component\HttpFoundation\RequestStack;

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
 * Elements whose operation-level "security" expression denies the current caller are omitted from
 * the listings, so a caller cannot discover the name, description and input schema of a tool it is
 * not allowed to invoke.
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
        private readonly ?OperationMetadataFactoryInterface $operationMetadataFactory = null,
        private readonly ?ResourceAccessCheckerInterface $resourceAccessChecker = null,
        private readonly ?RequestStack $requestStack = null,
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
            $references = $this->filterGranted($page->references, static fn (ResourceDefinition $resource): string => $resource->uri);
            $result = new ListResourcesResult($references, $page->nextCursor);
        } else {
            \assert($request instanceof ListToolsRequest);
            $page = $this->registry->getTools($this->pageSize, $request->cursor);
            $references = $this->filterGranted($page->references, static fn (Tool $tool): string => $tool->name);
            $result = new ListToolsResult($references, $page->nextCursor);
        }

        return new Response($request->getId(), $result);
    }

    /**
     * Filtering happens after paging, so a page may hold fewer elements than the page size. The
     * cursor still walks the whole registry, so no element is skipped.
     *
     * @template T of Tool|ResourceDefinition
     *
     * @param list<T>             $references
     * @param callable(T): string $identify   returns the operation name the reference maps to
     *
     * @return list<T>
     */
    private function filterGranted(array $references, callable $identify): array
    {
        if (null === $this->operationMetadataFactory || null === $this->resourceAccessChecker) {
            return $references;
        }

        $granted = [];

        foreach ($references as $reference) {
            if ($this->isGranted($identify($reference))) {
                $granted[] = $reference;
            }
        }

        return $granted;
    }

    /**
     * Only the operation-level "security" expression can be evaluated here: securityPostDenormalize
     * and securityPostValidation need arguments and an object that do not exist yet.
     */
    private function isGranted(string $operationName): bool
    {
        if (null === $this->operationMetadataFactory || null === $this->resourceAccessChecker) {
            throw new RuntimeException(\sprintf('Cannot evaluate the security of the "%s" operation without an operation metadata factory and a resource access checker.', $operationName));
        }

        $operation = $this->operationMetadataFactory->create($operationName);

        if (null === $operation || null === ($security = $operation->getSecurity())) {
            return true;
        }

        try {
            return $this->resourceAccessChecker->isGranted($operation->getClass() ?? '', $security, ['request' => $this->requestStack?->getCurrentRequest()]);
        } catch (SyntaxError) {
            // The expression reads variables that only exist once the element is called (object,
            // previous_object, uri variables). Listing cannot decide, so the element stays visible
            // and the expression is enforced on tools/call and resources/read, as
            // AccessCheckerProvider already defers the pre_read stage in that case.
            return true;
        }
    }
}
