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
use ApiPlatform\Metadata\HydraOperation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\ResourceAccessCheckerInterface;

/**
 * Generates Hydra operations for JSON-LD responses.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @property ResourceMetadataCollectionFactoryInterface|null $resourceMetadataCollectionFactory
 * @property ResourceAccessCheckerInterface|null             $resourceAccessChecker
 */
trait HydraOperationsTrait
{
    /**
     * Gets Hydra operations from all HydraOperation attributes.
     */
    private function getHydraOperationsFromAttributes(string $resourceClass, bool $collection, ?object $object, array $context, string $hydraPrefix = ContextBuilder::HYDRA_PREFIX): array
    {
        $allHydraOperations = [];
        $operationNames = [];

        foreach ($this->resourceMetadataCollectionFactory->create($resourceClass) as $resourceMetadata) {
            $hydraOperations = $this->getHydraOperationsFromAttributesForResource(
                $collection,
                $resourceMetadata,
                $hydraPrefix,
                $resourceClass,
                $object,
                $context,
                $operationNames
            );

            $allHydraOperations = array_merge($allHydraOperations, $hydraOperations);
        }

        return $allHydraOperations;
    }

    /**
     * Gets Hydra operations from a single resource metadata.
     */
    private function getHydraOperationsFromAttributesForResource(bool $collection, ApiResource $resourceMetadata, string $hydraPrefix, string $resourceClass, ?object $object, array $context, array &$operationNames): array
    {
        $operations = [];

        foreach ($resourceMetadata->getHydraOperations() ?? [] as $hydraOperation) {
            if ($hydraOperation->getCollection() !== $collection) {
                continue;
            }

            $method = $hydraOperation->getMethod();
            if (\in_array($method, $operationNames, true)) {
                continue;
            }

            if (!$this->isHydraOperationGranted($hydraOperation, $resourceClass, $object, $context)) {
                continue;
            }

            $operationNames[] = $method;
            $operations[] = $this->normalizeHydraOperationAttribute($hydraOperation, $resourceMetadata->getShortName(), $hydraPrefix);
        }

        return $operations;
    }

    private function isHydraOperationGranted(HydraOperation $hydraOperation, string $resourceClass, ?object $object, array $context): bool
    {
        if (null === $expression = $hydraOperation->getSecurity()) {
            return true;
        }

        if (null === $this->resourceAccessChecker) {
            return false;
        }

        $extraVariables = ['object' => $object];
        if (isset($context['request'])) {
            $extraVariables['request'] = $context['request'];
        }

        return $this->resourceAccessChecker->isGranted($resourceClass, $expression, $extraVariables);
    }

    /**
     * Normalizes a HydraOperation attribute into a JSON-LD array.
     */
    private function normalizeHydraOperationAttribute(HydraOperation $hydraOperation, ?string $shortName, string $hydraPrefix = ContextBuilder::HYDRA_PREFIX): array
    {
        $method = $hydraOperation->getMethod();
        $output = $hydraOperation->getExtraProperties();

        $output['@type'] = $hydraOperation->getTypes() ?? $this->defaultHydraOperationTypes($method, $hydraPrefix);

        if (null !== ($description = $hydraOperation->getDescription())) {
            $output[$hydraPrefix.'description'] = $description;
        }

        if (null !== ($expects = $hydraOperation->getExpects())) {
            $output['expects'] = $expects;
        } elseif (\in_array($method, ['POST', 'PUT', 'PATCH'], true) && null !== $shortName) {
            $output['expects'] = $shortName;
        }

        if (null !== ($returns = $hydraOperation->getReturns())) {
            $output['returns'] = $returns;
        } elseif ('DELETE' === $method) {
            $output['returns'] = 'owl:Nothing';
        } elseif (null !== $shortName) {
            $output['returns'] = $shortName;
        }

        $output[$hydraPrefix.'method'] = $method;
        $output[$hydraPrefix.'title'] = $hydraOperation->getTitle()
            ?? $this->defaultHydraOperationTitle($method, $shortName, $hydraOperation->getCollection() && 'GET' === $method);

        if (null === $output[$hydraPrefix.'title']) {
            unset($output[$hydraPrefix.'title']);
        }

        ksort($output);

        return $output;
    }

    private function defaultHydraOperationTypes(string $method, string $hydraPrefix): array|string
    {
        return match ($method) {
            'GET' => [$hydraPrefix.'Operation', 'schema:FindAction'],
            'POST' => [$hydraPrefix.'Operation', 'schema:CreateAction'],
            'PUT' => [$hydraPrefix.'Operation', 'schema:ReplaceAction'],
            'DELETE' => [$hydraPrefix.'Operation', 'schema:DeleteAction'],
            default => $hydraPrefix.'Operation',
        };
    }

    private function defaultHydraOperationTitle(string $method, ?string $shortName, bool $isCollection): ?string
    {
        if (null === $shortName) {
            return null;
        }

        return strtolower($method).$shortName.($isCollection ? 'Collection' : '');
    }

    /**
     * Gets Hydra operations.
     */
    private function getHydraOperations(bool $collection, ApiResource $resourceMetadata, string $hydraPrefix = ContextBuilder::HYDRA_PREFIX): array
    {
        $hydraOperations = [];
        foreach ($resourceMetadata->getOperations() ?? [] as $operation) {
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

        $isCollection = $operation instanceof CollectionOperationInterface;

        $hydraOperation += ['@type' => 'PATCH' === $method ? $hydraPrefix.'Operation' : $this->defaultHydraOperationTypes($method, $hydraPrefix)];

        if ('GET' === $method && $isCollection) {
            $hydraOperation += [
                $hydraPrefix.'description' => "Retrieves the collection of $shortName resources.",
                'returns' => null === $outputClass ? 'owl:Nothing' : $hydraPrefix.'Collection',
            ];
        } elseif ('GET' === $method) {
            $hydraOperation += [
                $hydraPrefix.'description' => "Retrieves a $shortName resource.",
                'returns' => null === $outputClass ? 'owl:Nothing' : $prefixedShortName,
            ];
        } elseif ('PATCH' === $method) {
            $hydraOperation += [
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
                $hydraPrefix.'description' => "Creates a $shortName resource.",
                'returns' => null === $outputClass ? 'owl:Nothing' : $prefixedShortName,
                'expects' => null === $inputClass ? 'owl:Nothing' : $prefixedShortName,
            ];
        } elseif ('PUT' === $method) {
            $hydraOperation += [
                $hydraPrefix.'description' => "Replaces the $shortName resource.",
                'returns' => null === $outputClass ? 'owl:Nothing' : $prefixedShortName,
                'expects' => null === $inputClass ? 'owl:Nothing' : $prefixedShortName,
            ];
        } elseif ('DELETE' === $method) {
            $hydraOperation += [
                $hydraPrefix.'description' => "Deletes the $shortName resource.",
                'returns' => 'owl:Nothing',
            ];
        }

        $hydraOperation[$hydraPrefix.'method'] ??= $method;
        $hydraOperation[$hydraPrefix.'title'] ??= $this->defaultHydraOperationTitle($method, $shortName, $isCollection);

        ksort($hydraOperation);

        return $hydraOperation;
    }
}
