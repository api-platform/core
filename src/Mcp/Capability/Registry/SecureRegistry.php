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

namespace ApiPlatform\Mcp\Capability\Registry;

use ApiPlatform\Mcp\Security\ElementAccessCheckerInterface;
use Mcp\Capability\Registry\Loader\LoaderInterface;
use Mcp\Capability\Registry\PromptReference;
use Mcp\Capability\Registry\ResourceReference;
use Mcp\Capability\Registry\ResourceTemplateReference;
use Mcp\Capability\Registry\ToolReference;
use Mcp\Capability\RegistryInterface;
use Mcp\Schema\Page;
use Mcp\Schema\Prompt;
use Mcp\Schema\ResourceDefinition;
use Mcp\Schema\ResourceTemplate;
use Mcp\Schema\Tool;

/**
 * Decorates the SDK registry, loading API Platform elements into it on first read.
 *
 * The SDK populates the registry once, when mcp.server is built. Under a persistent runtime
 * (e.g. FrankenPHP worker mode) that single build can capture an empty registry (cold metadata
 * cache) and stays empty for the whole process, so tools/list returns [] while tools/call keeps
 * working through the request-time handler. Loading the API Platform elements lazily here heals
 * that: it runs once per process (registrations are idempotent by name) and reads back through
 * the shared registry, so runtime registrations and other registry decorators are preserved.
 *
 * Elements the configured ElementAccessCheckerInterface denies to the current caller are omitted from
 * getTools()/getResources(), so a caller cannot discover the name, description and input schema of
 * a tool it is not allowed to invoke. Nothing else is filtered: has*() is read by
 * Builder::detectCapabilities() at build time, where there is no request to check against, and
 * getTool()/getResource() are read by DiscoveryLoader during load for its identity check, plus
 * AccessCheckerProvider already enforces security on tools/call and resources/read.
 *
 * @experimental
 * TODO: drop the lazy load once the SDK can hand its loader to a registry passed to Builder::setRegistry()
 */
final class SecureRegistry implements RegistryInterface
{
    private bool $loaded = false;

    public function __construct(
        private readonly RegistryInterface $inner,
        private readonly LoaderInterface $loader,
        private readonly ?ElementAccessCheckerInterface $accessChecker = null,
    ) {
    }

    public function registerTool(Tool $tool, callable|array|string $handler): ToolReference
    {
        return $this->inner->registerTool($tool, $handler);
    }

    public function registerResource(ResourceDefinition $resource, callable|array|string $handler): ResourceReference
    {
        return $this->inner->registerResource($resource, $handler);
    }

    public function registerResourceTemplate(
        ResourceTemplate $template,
        callable|array|string $handler,
        array $completionProviders = [],
    ): ResourceTemplateReference {
        return $this->inner->registerResourceTemplate($template, $handler, $completionProviders);
    }

    public function registerPrompt(
        Prompt $prompt,
        callable|array|string $handler,
        array $completionProviders = [],
    ): PromptReference {
        return $this->inner->registerPrompt($prompt, $handler, $completionProviders);
    }

    public function unregisterTool(string $name): void
    {
        $this->inner->unregisterTool($name);
    }

    public function unregisterResource(string $uri): void
    {
        $this->inner->unregisterResource($uri);
    }

    public function unregisterResourceTemplate(string $uriTemplate): void
    {
        $this->inner->unregisterResourceTemplate($uriTemplate);
    }

    public function unregisterPrompt(string $name): void
    {
        $this->inner->unregisterPrompt($name);
    }

    public function hasTool(string $name): bool
    {
        $this->load();

        return $this->inner->hasTool($name);
    }

    public function hasResource(string $uri): bool
    {
        $this->load();

        return $this->inner->hasResource($uri);
    }

    public function hasResourceTemplate(string $uriTemplate): bool
    {
        $this->load();

        return $this->inner->hasResourceTemplate($uriTemplate);
    }

    public function hasPrompt(string $name): bool
    {
        $this->load();

        return $this->inner->hasPrompt($name);
    }

    public function hasTools(): bool
    {
        $this->load();

        return $this->inner->hasTools();
    }

    public function getTools(?int $limit = null, ?string $cursor = null): Page
    {
        $this->load();

        $page = $this->inner->getTools($limit, $cursor);
        $references = $this->filterGranted($page->references, static fn (Tool $tool): string => $tool->name);

        return new Page($references, $page->nextCursor);
    }

    public function getTool(string $name): ToolReference
    {
        $this->load();

        return $this->inner->getTool($name);
    }

    public function hasResources(): bool
    {
        $this->load();

        return $this->inner->hasResources();
    }

    public function getResources(?int $limit = null, ?string $cursor = null): Page
    {
        $this->load();

        $page = $this->inner->getResources($limit, $cursor);
        $references = $this->filterGranted($page->references, static fn (ResourceDefinition $resource): string => $resource->uri);

        return new Page($references, $page->nextCursor);
    }

    public function getResource(string $uri, bool $includeTemplates = true): ResourceReference|ResourceTemplateReference
    {
        $this->load();

        return $this->inner->getResource($uri, $includeTemplates);
    }

    public function hasResourceTemplates(): bool
    {
        $this->load();

        return $this->inner->hasResourceTemplates();
    }

    public function getResourceTemplates(?int $limit = null, ?string $cursor = null): Page
    {
        $this->load();

        return $this->inner->getResourceTemplates($limit, $cursor);
    }

    public function getResourceTemplate(string $uriTemplate): ResourceTemplateReference
    {
        $this->load();

        return $this->inner->getResourceTemplate($uriTemplate);
    }

    public function hasPrompts(): bool
    {
        $this->load();

        return $this->inner->hasPrompts();
    }

    public function getPrompts(?int $limit = null, ?string $cursor = null): Page
    {
        $this->load();

        return $this->inner->getPrompts($limit, $cursor);
    }

    public function getPrompt(string $name): PromptReference
    {
        $this->load();

        return $this->inner->getPrompt($name);
    }

    private function load(): void
    {
        if (!$this->loaded) {
            $this->loader->load($this->inner);
            $this->loaded = true;
        }
    }

    /**
     * Filtering happens after paging, so a page may hold fewer elements than the page size. The
     * cursor still walks the whole registry, so no element is skipped.
     *
     * @template T of Tool|ResourceDefinition
     *
     * @param array<int|string, T> $references
     * @param callable(T): string  $identify   returns the operation name the reference maps to
     *
     * @return list<T>
     */
    private function filterGranted(array $references, callable $identify): array
    {
        if (null === $this->accessChecker) {
            return array_values($references);
        }

        $granted = [];

        foreach ($references as $reference) {
            if ($this->accessChecker->isGranted($identify($reference))) {
                $granted[] = $reference;
            }
        }

        return $granted;
    }
}
