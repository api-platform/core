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

use ApiPlatform\Mcp\Factory\McpCapabilityFactoryInterface;
use Mcp\Server;
use Mcp\Server\Builder as McpBuilder;
use Mcp\Server\Handler\Request\RequestHandlerInterface;

/**
 * Decorates the original MCP Server Builder to add capabilities from API Platform.
 *
 * @internal
 */
final class Builder
{
    /**
     * @param iterable<McpCapabilityFactoryInterface> $factories
     * @param iterable<RequestHandlerInterface>       $requestHandlers
     */
    public function __construct(
        private readonly McpBuilder $decorated,
        private readonly iterable $factories,
        private readonly iterable $requestHandlers,
    ) {
    }

    public function build(): Server
    {
        foreach ($this->requestHandlers as $requestHandler) {
            $this->decorated->addRequestHandler($requestHandler);
        }

        foreach ($this->factories as $factory) {
            foreach ($factory->create() as ['type' => $type, 'definition' => $definition]) {
                $definition['handler'] = fn () => null; // dummy handler we call it inside our CallToolHandler
                match ($type) {
                    'tool' => $this->decorated->addTool(...$definition),
                    'resource' => $this->decorated->addResource(...$definition),
                    'resource_template' => $this->decorated->addResourceTemplate(...$definition),
                    default => null,
                };
            }
        }

        return $this->decorated->build();
    }

    public function __call(string $name, array $arguments): mixed
    {
        return $this->decorated->{$name}(...$arguments);
    }
}
