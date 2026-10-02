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

namespace ApiPlatform\Hydra\Serializer;

use ApiPlatform\JsonLd\ContextBuilder;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\HttpOperation;

/**
 * Generates Hydra operations for JSON-LD responses.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
trait HydraOperationsTrait
{
    /**
     * Gets Hydra operations from all resource metadata.
     */
    private function getHydraOperationsFromResourceMetadatas(string $resourceClass, bool $collection, string $hydraPrefix = ContextBuilder::HYDRA_PREFIX): array
    {
        $allHydraOperations = [];
        $operationNames = [];

        foreach ($this->resourceMetadataCollectionFactory->create($resourceClass) as $resourceMetadata) {
            $hydraOperations = $this->getHydraOperationsFromResourceMetadata(
                $collection,
                $resourceMetadata,
                $hydraPrefix,
                $operationNames
            );

            $allHydraOperations = array_merge($allHydraOperations, $hydraOperations);
        }

        return $allHydraOperations;
    }

    /**
     * Gets Hydra operations from a single resource metadata.
     */
    private function getHydraOperationsFromResourceMetadata(bool $collection, ApiResource $resourceMetadata, string $hydraPrefix, array &$operationNames): array
    {
        $operations = [];
        $hydraOperations = $this->getHydraOperations(
            $collection,
            $resourceMetadata,
            $hydraPrefix
        );

        if (!empty($hydraOperations)) {
            foreach ($hydraOperations as $operation) {
                $operationName = $operation[$hydraPrefix.'method'];
                if (!\in_array($operationName, $operationNames, true)) {
                    $operationNames[] = $operationName;
                    $operations[] = $operation;
                }
            }
        }

        return $operations;
    }

    /**
     * Gets Hydra operations.
     */
    private function getHydraOperations(bool $collection, ApiResource $resourceMetadata, string $hydraPrefix = ContextBuilder::HYDRA_PREFIX): array
    {
        $hydraOperations = [];
        foreach ($resourceMetadata->getOperations() as $operation) {
            if (true === $operation->getHideHydraOperation()) {
                continue;
            }

            if (('POST' === $operation->getMethod() || $operation instanceof CollectionOperationInterface) !== $collection) {
                continue;
            }

            $hydraOperations[] = $this->getHydraOperation($operation, $operation->getShortName(), $hydraPrefix);
        }

        return $hydraOperations;
    }

    /**
     * Gets and populates if applicable a Hydra operation.
     */
    private function getHydraOperation(HttpOperation $operation, string $prefixedShortName, string $hydraPrefix = ContextBuilder::HYDRA_PREFIX): array
    {
        $method = $operation->getMethod() ?: 'GET';

        $hydraOperation = $operation->getHydraContext() ?? [];
        if ($operation->getDeprecationReason()) {
            $hydraOperation['owl:deprecated'] = true;
        }

        $shortName = $operation->getShortName();
        $inputMetadata = $operation->getInput() ?? [];
        $outputMetadata = $operation->getOutput() ?? [];

        $inputClass = \array_key_exists('class', $inputMetadata) ? $inputMetadata['class'] : false;
        $outputClass = \array_key_exists('class', $outputMetadata) ? $outputMetadata['class'] : false;

        if ('GET' === $method && $operation instanceof CollectionOperationInterface) {
            $hydraOperation += [
                '@type' => [$hydraPrefix.'Operation', 'schema:FindAction'],
                $hydraPrefix.'description' => "Retrieves the collection of $shortName resources.",
                'returns' => null === $outputClass ? 'owl:Nothing' : $hydraPrefix.'Collection',
            ];
        } elseif ('GET' === $method) {
            $hydraOperation += [
                '@type' => [$hydraPrefix.'Operation', 'schema:FindAction'],
                $hydraPrefix.'description' => "Retrieves a $shortName resource.",
                'returns' => null === $outputClass ? 'owl:Nothing' : $prefixedShortName,
            ];
        } elseif ('PATCH' === $method) {
            $hydraOperation += [
                '@type' => $hydraPrefix.'Operation',
                $hydraPrefix.'description' => "Updates the $shortName resource.",
                'returns' => null === $outputClass ? 'owl:Nothing' : $prefixedShortName,
                'expects' => null === $inputClass ? 'owl:Nothing' : $prefixedShortName,
            ];

            if (null !== $inputClass) {
                $possibleValue = [];
                foreach ($operation->getInputFormats() ?? [] as $mimeTypes) {
                    foreach ($mimeTypes as $mimeType) {
                        $possibleValue[] = $mimeType;
                    }
                }

                $hydraOperation['expectsHeader'] = [['headerName' => 'Content-Type', 'possibleValue' => $possibleValue]];
            }
        } elseif ('POST' === $method) {
            $hydraOperation += [
                '@type' => [$hydraPrefix.'Operation', 'schema:CreateAction'],
                $hydraPrefix.'description' => "Creates a $shortName resource.",
                'returns' => null === $outputClass ? 'owl:Nothing' : $prefixedShortName,
                'expects' => null === $inputClass ? 'owl:Nothing' : $prefixedShortName,
            ];
        } elseif ('PUT' === $method) {
            $hydraOperation += [
                '@type' => [$hydraPrefix.'Operation', 'schema:ReplaceAction'],
                $hydraPrefix.'description' => "Replaces the $shortName resource.",
                'returns' => null === $outputClass ? 'owl:Nothing' : $prefixedShortName,
                'expects' => null === $inputClass ? 'owl:Nothing' : $prefixedShortName,
            ];
        } elseif ('DELETE' === $method) {
            $hydraOperation += [
                '@type' => [$hydraPrefix.'Operation', 'schema:DeleteAction'],
                $hydraPrefix.'description' => "Deletes the $shortName resource.",
                'returns' => 'owl:Nothing',
            ];
        }

        $hydraOperation[$hydraPrefix.'method'] ??= $method;
        $hydraOperation[$hydraPrefix.'title'] ??= strtolower($method).$shortName.($operation instanceof CollectionOperationInterface ? 'Collection' : '');

        ksort($hydraOperation);

        return $hydraOperation;
    }
}
