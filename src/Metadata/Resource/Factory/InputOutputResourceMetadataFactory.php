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

        $attributes = $resourceMetadata->getAttributes() ?: [];
        $attributes['input'] = isset($attributes['input']) ? $this->transformInputOutput($attributes['input']) : null;
        $attributes['output'] = isset($attributes['output']) ? $this->transformInputOutput($attributes['output']) : null;

        if (null !== $collectionOperations = $resourceMetadata->getCollectionOperations()) {
            $resourceMetadata = $resourceMetadata->withCollectionOperations($this->getTransformedOperations($collectionOperations, $attributes));
        }

        if (null !== $itemOperations = $resourceMetadata->getItemOperations()) {
            $resourceMetadata = $resourceMetadata->withItemOperations($this->getTransformedOperations($itemOperations, $attributes));
        }

        return $resourceMetadata->withAttributes($attributes);
    }

    private function getTransformedOperations(array $operations, array $resourceAttributes): array
    {
        foreach ($operations as $key => &$operation) {
            if (!\is_array($operation)) {
                continue;
            }

            $operation['input'] = isset($operation['input']) ? $this->transformInputOutput($operation['input']) : $resourceAttributes['input'];
            $operation['output'] = isset($operation['output']) ? $this->transformInputOutput($operation['output']) : $resourceAttributes['output'];
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

        if (!isset($attribute['name'])) {
            $attribute['name'] = (new \ReflectionClass($attribute['class']))->getShortName();
        }

        return $attribute;
    }
}
