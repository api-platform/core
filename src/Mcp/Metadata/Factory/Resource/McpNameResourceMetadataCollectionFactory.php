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

namespace ApiPlatform\Mcp\Metadata\Factory\Resource;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;

/**
 * Adds a sanitized, MCP-compliant name to each operation's metadata.
 *
 * @internal
 */
final readonly class McpNameResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    public function __construct(private ResourceMetadataCollectionFactoryInterface $decorated)
    {
    }

    public function create(string $resourceClass): ResourceMetadataCollection
    {
        $resourceMetadataCollection = $this->decorated->create($resourceClass);

        foreach ($resourceMetadataCollection as $i => $resource) {
            if (!$operations = $resource->getOperations()) {
                continue;
            }

            $newOperations = new Operations();
            foreach ($operations as $operation) {
                if (!$operation instanceof HttpOperation) {
                    continue;
                }

                if ($mcpName = $this->getMcpName($operation)) {
                    $operation = $operation->withExtraProperties(array_merge($operation->getExtraProperties(), ['mcp_name' => $mcpName]));
                }
                $newOperations->add($operation->getName(), $operation);
            }

            $resourceMetadataCollection[$i] = $resource->withOperations($newOperations);
        }

        return $resourceMetadataCollection;
    }

    private function getMcpName(HttpOperation $operation): string
    {
        return strtolower($operation->getShortName()).
            '_'.$this->getHttpMethodName($operation->getMethod()).
            ($operation instanceof CollectionOperationInterface ? '_list' : '').
            ($operation->getUriVariables() ? '_by_'.implode('_', array_keys($operation->getUriVariables())) : '');
    }

    private function getHttpMethodName(?string $method): string
    {
        return match (strtolower($method)) {
            'post' => 'create',
            'put' => 'upsert',
            'patch' => 'update',
            'delete' => 'delete',
            default => 'retrieve',
        };
    }
}
