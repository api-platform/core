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

namespace ApiPlatform\Core\Metadata\Resource\Factory;

use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;

/**
 * Creates or completes operations.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class OperationResourceMetadataFactory implements ResourceMetadataFactoryInterface
{
    /**
     * @internal
     */
    public const SUPPORTED_COLLECTION_OPERATION_METHODS = [
        'GET' => true,
        'POST' => true,
    ];

    /**
     * @internal
     */
    public const SUPPORTED_ITEM_OPERATION_METHODS = [
        'GET' => true,
        'PUT' => true,
        // PATCH is automatically supported if at least one patch format has been configured
        'DELETE' => true,
    ];

    private $decorated;
    private $patchFormats;

    public function __construct(ResourceMetadataFactoryInterface $decorated, array $formats = [], array $patchFormats = [])
    {
        $this->decorated = $decorated;
        $this->patchFormats = $patchFormats;

        // Backward Compatibility layer
        if (isset($formats['jsonapi']) && !isset($patchFormats['jsonapi'])) {
            $this->patchFormats['jsonapi'] = ['application/vnd.api+json'];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass): ResourceMetadata
    {
        $resourceMetadata = $this->decorated->create($resourceClass);
        $isAbstract = (new \ReflectionClass($resourceClass))->isAbstract();

        $collectionOperations = $resourceMetadata->getCollectionOperations();
        if (null === $collectionOperations) {
            $resourceMetadata = $resourceMetadata->withCollectionOperations($this->createOperations(
                $isAbstract ? ['GET'] : ['GET', 'POST']
            ));
        } else {
            $resourceMetadata = $this->normalize(true, $resourceMetadata, $collectionOperations);
        }

        $itemOperations = $resourceMetadata->getItemOperations();
        if (null === $itemOperations) {
            $methods = ['GET', 'DELETE'];

            if (!$isAbstract) {
                $methods[] = 'PUT';

                if ($this->patchFormats) {
                    $methods[] = 'PATCH';
                }
            }

            $resourceMetadata = $resourceMetadata->withItemOperations($this->createOperations($methods));
        } else {
            $resourceMetadata = $this->normalize(false, $resourceMetadata, $itemOperations);
        }

        $graphql = $resourceMetadata->getGraphql();
        if (null === $graphql) {
            $resourceMetadata = $resourceMetadata->withGraphql(['query' => [], 'delete' => [], 'update' => [], 'create' => []]);
        } else {
            $resourceMetadata = $this->normalizeGraphQl($resourceMetadata, $graphql);
        }

        return $resourceMetadata;
    }

    private function createOperations(array $methods): array
    {
        $operations = [];
        foreach ($methods as $method) {
            $operations[strtolower($method)] = ['method' => $method];
        }

        return $operations;
    }

    private function normalize(bool $collection, ResourceMetadata $resourceMetadata, array $operations): ResourceMetadata
    {
        $newOperations = [];
        foreach ($operations as $operationName => $operation) {
            // e.g.: @ApiResource(itemOperations={"get"})
            if (\is_int($operationName) && \is_string($operation)) {
                $operationName = $operation;
                $operation = [];
            }

            $upperOperationName = strtoupper((string) $operationName);
            if ($collection) {
                $supported = isset(self::SUPPORTED_COLLECTION_OPERATION_METHODS[$upperOperationName]);
            } else {
                $supported = isset(self::SUPPORTED_ITEM_OPERATION_METHODS[$upperOperationName]) || ($this->patchFormats && 'PATCH' === $upperOperationName);
            }

            if (!isset($operation['method']) && !isset($operation['route_name'])) {
                $supported ? $operation['method'] = $upperOperationName : $operation['route_name'] = $operationName;
            }

            if (isset($operation['method'])) {
                $operation['method'] = strtoupper($operation['method']);

                if ('PATCH' === $operation['method'] && ($operation['formats'] ?? true)) {
                    $operation['formats'] = $this->patchFormats;
                }
            }

            $newOperations[$operationName] = $operation;
        }

        return $collection ? $resourceMetadata->withCollectionOperations($newOperations) : $resourceMetadata->withItemOperations($newOperations);
    }

    private function normalizeGraphQl(ResourceMetadata $resourceMetadata, array $operations): ResourceMetadata
    {
        foreach ($operations as $operationName => $operation) {
            if (\is_int($operationName) && \is_string($operation)) {
                unset($operations[$operationName]);
                $operations[$operation] = [];
            }
        }

        return $resourceMetadata->withGraphql($operations);
    }
}
