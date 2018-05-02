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
    const SUPPORTED_COLLECTION_OPERATION_METHODS = [
        'GET' => true,
        'POST' => true,
    ];

    /**
     * @internal
     */
    const SUPPORTED_ITEM_OPERATION_METHODS = [
        'GET' => true,
        'PUT' => true,
        'DELETE' => true,
    ];

    private $decorated;
    private $formats;

    public function __construct(ResourceMetadataFactoryInterface $decorated, array $formats = [])
    {
        $this->decorated = $decorated;
        $this->formats = $formats;
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

                if (isset($this->formats['jsonapi'])) {
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
            if (is_int($operationName) && is_string($operation)) {
                $operationName = $operation;
                $operation = [];
            }

            $upperOperationName = strtoupper($operationName);
            if ($collection) {
                $supported = isset(self::SUPPORTED_COLLECTION_OPERATION_METHODS[$upperOperationName]);
            } else {
                $supported = isset(self::SUPPORTED_ITEM_OPERATION_METHODS[$upperOperationName]) || (isset($this->formats['jsonapi']) && 'PATCH' === $upperOperationName);
            }

            if ($supported && !isset($operation['method']) && !isset($operation['route_name'])) {
                $operation['method'] = $upperOperationName;
            }

            $newOperations[$operationName] = $operation;
        }

        return $collection ? $resourceMetadata->withCollectionOperations($newOperations) : $resourceMetadata->withItemOperations($newOperations);
    }

    private function normalizeGraphQl(ResourceMetadata $resourceMetadata, array $operations)
    {
        foreach ($operations as $operationName => $operation) {
            if (is_int($operationName) && is_string($operation)) {
                unset($operations[$operationName]);
                $operations[$operation] = [];
            }
        }

        return $resourceMetadata->withGraphql($operations);
    }
}
