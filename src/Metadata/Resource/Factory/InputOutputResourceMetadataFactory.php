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
 * Transforms the given input/output metadata to a normalized one.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class InputOutputResourceMetadataFactory implements ResourceMetadataFactoryInterface
{
    private $decorated;

    public function __construct(ResourceMetadataFactoryInterface $decorated)
    {
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass): ResourceMetadata
    {
        $resourceMetadata = $this->decorated->create($resourceClass);

        foreach ($resourceMetadata as $path => $operationCollectionMetadata) {
            $attributes = $operationCollectionMetadata->getAttributes() ?: [];
            $attributes['input'] = isset($attributes['input']) ? $this->transformInputOutput($attributes['input']) : null;
            $attributes['output'] = isset($attributes['output']) ? $this->transformInputOutput($attributes['output']) : null;

            if (null !== $collectionOperations = $operationCollectionMetadata->getCollectionOperations()) {
                $resourceMetadata[$path] = $operationCollectionMetadata->withCollectionOperations($this->getTransformedOperations($collectionOperations, $attributes));
            }

            if (null !== $itemOperations = $operationCollectionMetadata->getItemOperations()) {
                $resourceMetadata[$path] = $operationCollectionMetadata->withItemOperations($this->getTransformedOperations($itemOperations, $attributes));
            }

            if (null !== $graphQlAttributes = $operationCollectionMetadata->getGraphql()) {
                $resourceMetadata[$path] = $operationCollectionMetadata->withGraphql($this->getTransformedOperations($graphQlAttributes, $attributes));
            }

            $resourceMetadata[$path] = $operationCollectionMetadata->withAttributes($attributes);
        }

        return $resourceMetadata;
    }

    private function getTransformedOperations(array $operations, array $resourceAttributes): array
    {
        foreach ($operations as $key => &$operation) {
            if (!\is_array($operation)) {
                continue;
            }

            $operation['input'] = isset($operation['input']) ? $this->transformInputOutput($operation['input']) : $resourceAttributes['input'];
            $operation['output'] = isset($operation['output']) ? $this->transformInputOutput($operation['output']) : $resourceAttributes['output'];

            if (
                isset($operation['input'])
                && \array_key_exists('class', $operation['input'])
                && null === $operation['input']['class']
            ) {
                $operation['deserialize'] ?? $operation['deserialize'] = false;
                $operation['validate'] ?? $operation['validate'] = false;
            }

            if (
                isset($operation['output'])
                && \array_key_exists('class', $operation['output'])
                && null === $operation['output']['class']
            ) {
                $operation['status'] ?? $operation['status'] = 204;
            }
        }

        return $operations;
    }

    private function transformInputOutput($attribute): ?array
    {
        if (null === $attribute) {
            return null;
        }

        if (false === $attribute) {
            return ['class' => null];
        }

        if (\is_string($attribute)) {
            $attribute = ['class' => $attribute];
        }

        if (!isset($attribute['name']) && isset($attribute['class'])) {
            $attribute['name'] = (new \ReflectionClass($attribute['class']))->getShortName();
        }

        return $attribute;
    }
}
