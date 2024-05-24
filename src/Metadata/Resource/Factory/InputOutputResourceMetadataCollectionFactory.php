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

namespace ApiPlatform\Metadata\Resource\Factory;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;

/**
 * Transforms the given input/output metadata to a normalized one.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class InputOutputResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    public function __construct(private readonly ResourceMetadataCollectionFactoryInterface $decorated)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass): ResourceMetadataCollection
    {
        $resourceMetadataCollection = $this->decorated->create($resourceClass);

        foreach ($resourceMetadataCollection as $key => $resourceMetadata) {
            $resourceMetadata = $resourceMetadata->withInput($this->transformInputOutput($resourceMetadata->getInput()));
            $resourceMetadata = $resourceMetadata->withOutput($this->transformInputOutput($resourceMetadata->getOutput()));

            if ($resourceMetadata->getOperations()) {
                $resourceMetadata = $resourceMetadata->withOperations($this->getTransformedOperations($resourceMetadata->getOperations(), $resourceMetadata));
            }

            if ($resourceMetadata->getGraphQlOperations()) {
                $resourceMetadata = $resourceMetadata->withGraphQlOperations($this->getTransformedOperations($resourceMetadata->getGraphQlOperations(), $resourceMetadata));
            }

            $resourceMetadataCollection[$key] = $resourceMetadata;
        }

        return $resourceMetadataCollection;
    }

    private function getTransformedOperations(Operations|array $operations, ApiResource $resourceMetadata): Operations|array
    {
        foreach ($operations as $key => $operation) {
            $operation = $operation->withInput(null !== $operation->getInput() ? $this->transformInputOutput($operation->getInput()) : $resourceMetadata->getInput());
            $operation = $operation->withOutput(null !== $operation->getOutput() ? $this->transformInputOutput($operation->getOutput()) : $resourceMetadata->getOutput());

            if (
                $operation->getInput()
                && \array_key_exists('class', $operation->getInput())
                && null === $operation->getInput()['class']
            ) {
                $operation = $operation->withDeserialize(null === $operation->canDeserialize() ? false : $operation->canDeserialize());
                $operation = $operation->withValidate(null === $operation->canValidate() ? false : $operation->canValidate());
            }

            if (
                $operation instanceof HttpOperation
                && $operation->getOutput()
                && \array_key_exists('class', $operation->getOutput())
                && null === $operation->getOutput()['class']
                && null === $operation->getStatus()
            ) {
                $operation = $operation->withStatus(204);
            }

            $operations instanceof Operations ? $operations->add($key, $operation) : $operations[$key] = $operation;
        }

        return $operations;
    }

    private function transformInputOutput(mixed $attribute): ?array
    {
        if (false === $attribute) {
            return ['class' => null];
        }

        if (!$attribute) {
            return null;
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
