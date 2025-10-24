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

namespace ApiPlatform\Mcp\Metadata\Factory\Operation;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Operation\Factory\OperationMetadataFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;

/**
 * Finds an operation by its sanitized MCP name.
 *
 * @internal
 */
final readonly class McpOperationMetadataFactory implements OperationMetadataFactoryInterface
{
    public function __construct(
        private ResourceNameCollectionFactoryInterface $resourceNameCollectionFactory,
        private ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory,
    ) {
    }

    public function create(string $mcpName, array $context = []): ?Operation
    {
        foreach ($this->resourceNameCollectionFactory->create() as $resourceClass) {
            $resourceMetadataCollection = $this->resourceMetadataCollectionFactory->create($resourceClass);
            foreach ($resourceMetadataCollection as $resource) {
                foreach ($resource->getOperations() as $operation) {
                    $candidateMcpName = $operation->getExtraProperties()['mcp_name'] ?? null;
                    if ($candidateMcpName === $mcpName) {
                        return $operation;
                    }
                }
            }
        }

        return null;
    }
}
