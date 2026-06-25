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
            $resourceMetadata = $resourceMetadata->withInputClass($resourceMetadata->getInputClass());
            $resourceMetadata = $resourceMetadata->withOutputClass($resourceMetadata->getOutputClass());

            if ($resourceMetadata->getOperations()) {
                $resourceMetadata = $resourceMetadata->withOperations($this->getTransformedOperations($resourceMetadata->getOperations(), $resourceMetadata));
            }

            if ($resourceMetadata->getGraphQlOperations()) {
                $resourceMetadata = $resourceMetadata->withGraphQlOperations($this->getTransformedOperations($resourceMetadata->getGraphQlOperations(), $resourceMetadata));
            }

            if ($resourceMetadata->getMcp()) {
                $resourceMetadata = $resourceMetadata->withMcp($this->getTransformedOperations($resourceMetadata->getMcp(), $resourceMetadata));
            }

            $resourceMetadataCollection[$key] = $resourceMetadata;
        }

        return $resourceMetadataCollection;
    }

    private function getTransformedOperations(Operations|array $operations, ApiResource $resourceMetadata): Operations|array
    {
        foreach ($operations as $key => $operation) {
            $resolvedInputClass = $operation->hasExplicitInputClass() ? $operation->getInputClass() : $resourceMetadata->getInputClass();
            $operation = $operation->withInputClass($resolvedInputClass);

            $hasExplicit = $operation->hasExplicitOutputClass();
            $resolvedOutputClass = $hasExplicit ? $operation->getOutputClass() : $resourceMetadata->getOutputClass();
            $ref = new \ReflectionObject($operation);
            $outputProp = $ref->getProperty('output'); $outputProp->setAccessible(true);
            $outputClassProp = $ref->getProperty('outputClass'); $outputClassProp->setAccessible(true);
            error_log("DEBUG [$key] hasExplicit=$hasExplicit raw_output=" . var_export($outputProp->getValue($operation), true) . " outputClass=" . var_export($outputClassProp->getValue($operation), true) . " resolved=" . var_export($resolvedOutputClass, true));
            $operation = $operation->withOutputClass($resolvedOutputClass);

            if (null === $resolvedInputClass) {
                $operation = $operation->withDeserialize(null === $operation->canDeserialize() ? false : $operation->canDeserialize());
                $operation = $operation->withValidate(null === $operation->canValidate() ? false : $operation->canValidate());
            }

            if (
                $operation instanceof HttpOperation
                && null === $resolvedOutputClass
                && null === $operation->getStatus()
            ) {
                $operation = $operation->withStatus(204);
            }

            $operations instanceof Operations ? $operations->add($key, $operation) : $operations[$key] = $operation;
        }

        return $operations;
    }
}
