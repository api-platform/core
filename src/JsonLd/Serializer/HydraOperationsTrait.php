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

namespace ApiPlatform\JsonLd\Serializer;

use ApiPlatform\JsonLd\ContextBuilder;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Error;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\NotExposed;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\ResourceAccessCheckerInterface;
use ApiPlatform\Metadata\Util\UriTemplateHelper;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @internal
 */
trait HydraOperationsTrait
{
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
     * Gets the Hydra operations exposed by a representation, given the operation identifying it: the ones referenced
     * by its hydraOperations or, when enabled by default, all the operations sharing its IRI, filtered
     * by their security.
     */
    private function getExposedHydraOperations(HttpOperation $operation, ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory, ?ResourceAccessCheckerInterface $resourceAccessChecker, mixed $object, array $context, string $hydraPrefix): array
    {
        $hydraOperations = $operation->getHydraOperations();
        if (false === $hydraOperations || (null === $hydraOperations && !($context['hydra_operations'] ?? true)) || null === $resourceClass = $operation->getClass()) {
            return [];
        }

        $resourceMetadataCollection = $resourceMetadataCollectionFactory->create($resourceClass);
        $candidates = [];

        if (null === $hydraOperations) {
            $iri = static fn (HttpOperation $httpOperation): ?string => null === ($uriTemplate = $httpOperation->getUriTemplate()) ? null : ($httpOperation->getRoutePrefix() ?? '').UriTemplateHelper::withoutFormatSuffix($uriTemplate);
            $operationIri = $iri($operation);
            foreach ($resourceMetadataCollection as $resourceMetadata) {
                foreach ($resourceMetadata->getOperations() ?? [] as $candidate) {
                    if (null !== $operationIri && $operationIri === $iri($candidate) && !$candidate instanceof NotExposed && !$candidate instanceof Error) {
                        $candidates[$candidate->getMethod()] ??= [$candidate, null];
                    }
                }
            }
        } else {
            foreach ($hydraOperations as $hydraOperation) {
                if (null !== $hydraOperation->getName() && ($candidate = $resourceMetadataCollection->getOperation($hydraOperation->getName())) instanceof HttpOperation) {
                    $candidates[] = [$candidate, $hydraOperation->getSecurity()];
                }
            }
        }

        $exposedOperations = [];
        foreach ($candidates as [$candidate, $security]) {
            $security ??= $candidate->getSecurity() ?? $candidate->getPolicy();
            try {
                $granted = null === $security || $resourceAccessChecker?->isGranted($candidate->getClass(), $security, ['object' => $object, 'previous_object' => $object, 'request' => $context['request'] ?? null] + ($context['uri_variables'] ?? []));
            } catch (\Exception) {
                $granted = false;
            }

            if ($granted) {
                $exposedOperations[] = $this->getHydraOperation($candidate, $candidate->getShortName(), $hydraPrefix);
            }
        }

        return $exposedOperations;
    }

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
