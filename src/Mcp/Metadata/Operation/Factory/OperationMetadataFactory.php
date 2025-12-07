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

namespace ApiPlatform\Mcp\Metadata\Operation\Factory;

use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\McpResource;
use ApiPlatform\Metadata\McpTool;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Operation\Factory\OperationMetadataFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;

final class OperationMetadataFactory implements OperationMetadataFactoryInterface
{
    public function __construct(
        private readonly ResourceNameCollectionFactoryInterface $resourceNameCollectionFactory,
        private readonly ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory,
    ) {
    }

    /**
     * @throws RuntimeException
     *
     * @return HttpOperation
     */
    public function create(string $operationName, array $context = []): Operation
    {
        foreach ($this->resourceNameCollectionFactory->create() as $resourceClass) {
            foreach ($this->resourceMetadataCollectionFactory->create($resourceClass) as $resource) {
                if (null === $mcp = $resource->getMcp()) {
                    continue;
                }

                foreach ($mcp as $operation) {
                    if (!($operation instanceof McpTool || $operation instanceof McpResource)) {
                        continue;
                    }

                    if ($operation->getName() === $operationName) {
                        return $operation;
                    }

                    if ($operation instanceof McpResource && $operation->getUri() === $operationName) {
                        return $operation;
                    }
                }
            }
        }

        throw new RuntimeException(\sprintf('MCP operation "%s" not found.', $operationName));
    }
}
