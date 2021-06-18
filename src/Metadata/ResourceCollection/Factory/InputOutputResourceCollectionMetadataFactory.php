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

namespace ApiPlatform\Core\Metadata\ResourceCollection\Factory;

use ApiPlatform\Core\Metadata\ResourceCollection\ResourceCollection;
use ApiPlatform\Metadata\Resource;

/**
 * Transforms the given input/output metadata to a normalized one.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 * @experimental
 */
final class InputOutputResourceCollectionMetadataFactory implements ResourceCollectionMetadataFactoryInterface
{
    private $decorated;

    public function __construct(ResourceCollectionMetadataFactoryInterface $decorated)
    {
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass): ResourceCollection
    {
        $resourceMetadataCollection = $this->decorated->create($resourceClass);

        foreach ($resourceMetadataCollection as $key => $resourceMetadata) {
            $resourceMetadata = $resourceMetadata->withInput($this->transformInputOutput($resourceMetadata->getInput()));
            $resourceMetadata = $resourceMetadata->withOutput($this->transformInputOutput($resourceMetadata->getOutput()));

            if (\count($resourceMetadata->getOperations())) {
                $resourceMetadata = $resourceMetadata->withOperations($this->getTransformedOperations(iterator_to_array($resourceMetadata->getOperations()), $resourceMetadata));
            }

            // TODO: GraphQL operations as an object?
            // if ($graphQlAttributes = $resourceMetadata->graphQl) {
            //     $resourceMetadata->graphQl = $this->getTransformedOperations($resourceMetadata->graphQl, $resourceMetadata);
            // }

            $resourceMetadataCollection[$key] = $resourceMetadata;
        }

        return $resourceMetadataCollection;
    }

    private function getTransformedOperations(array $operations, Resource $resourceMetadata): array
    {
        foreach ($operations as $key => &$operation) {
            $operation = $operation->withInput($operation->getInput() ? $this->transformInputOutput($operation->getInput()) : $resourceMetadata->getInput());
            $operation = $operation->withOutput($operation->getOutput() ? $this->transformInputOutput($operation->getOutput()) : $resourceMetadata->getOutput());

            if (
                $operation->getInput()
                && \array_key_exists('class', $operation->getInput())
                && null === $operation->getInput()['class']
            ) {
                $operation = $operation->withDeserialize($operation->getDeserialize() ?? false);
                $operation = $operation->withValidate($operation->getValidate() ?? false);
            }

            if (
                $operation->getOutput()
                && \array_key_exists('class', $operation->getOutput())
                && null === $operation->getOutput()['class']
            ) {
                $operation = $operation->withStatus($operation->getStatus() ?? 204);
            }
        }

        return $operations;
    }

    private function transformInputOutput($attribute): ?array
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
