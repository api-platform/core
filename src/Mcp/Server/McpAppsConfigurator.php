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

use ApiPlatform\Metadata\McpResource;
use ApiPlatform\Metadata\McpTool;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use Mcp\Schema\Extension\Apps\McpApps;
use Mcp\Server\Builder;

/**
 * @experimental
 */
final class McpAppsConfigurator
{
    public function __construct(
        private readonly ResourceNameCollectionFactoryInterface $resourceNameCollectionFactory,
        private readonly ResourceMetadataCollectionFactoryInterface $resourceMetadataCollection,
    ) {
    }

    public function __invoke(Builder $builder): void
    {
        foreach ($this->resourceNameCollectionFactory->create() as $resourceClass) {
            foreach ($this->resourceMetadataCollection->create($resourceClass) as $resource) {
                foreach ($resource->getMcp() ?? [] as $mcp) {
                    if ($this->isApp($mcp)) {
                        $builder->enableExtension(new McpApps());

                        return;
                    }
                }
            }
        }
    }

    private function isApp(mixed $mcp): bool
    {
        if ($mcp instanceof McpResource) {
            return McpApps::MIME_TYPE === $mcp->getMimeType();
        }

        if ($mcp instanceof McpTool) {
            $resourceUri = $mcp->getMeta()['ui']['resourceUri'] ?? null;

            return \is_string($resourceUri) && str_starts_with($resourceUri, McpApps::URI_SCHEME.'://');
        }

        return false;
    }
}
