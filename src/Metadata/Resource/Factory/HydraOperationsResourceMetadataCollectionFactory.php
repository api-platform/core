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

use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\HydraOperation;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\Util\UriTemplateHelper;

/**
 * Resolves the {@see HydraOperation} references to the operations declared on the resource.
 */
final class HydraOperationsResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    public function __construct(private readonly ?ResourceMetadataCollectionFactoryInterface $decorated = null)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass): ResourceMetadataCollection
    {
        $resourceMetadataCollection = new ResourceMetadataCollection($resourceClass);
        if ($this->decorated) {
            $resourceMetadataCollection = $this->decorated->create($resourceClass);
        }

        foreach ($resourceMetadataCollection as $i => $resource) {
            if (null === $operations = $resource->getOperations()) {
                continue;
            }

            foreach ($operations as $operationName => $operation) {
                if (!\is_array($hydraOperations = $operation->getHydraOperations())) {
                    continue;
                }

                foreach ($hydraOperations as $key => $hydraOperation) {
                    $hydraOperations[$key] = $this->resolve($resourceMetadataCollection, $hydraOperation, $operation);
                }

                $operations->add($operationName, $operation->withHydraOperations($hydraOperations));
            }

            $resourceMetadataCollection[$i] = $resource->withOperations($operations);
        }

        return $resourceMetadataCollection;
    }

    /**
     * Finds the referenced operation by name, or by method and URI template regardless of the format suffix.
     */
    private function resolve(ResourceMetadataCollection $resourceMetadataCollection, HydraOperation $hydraOperation, HttpOperation $operation): HydraOperation
    {
        $name = $hydraOperation->getName();
        $method = null === $hydraOperation->getMethod() ? null : strtoupper($hydraOperation->getMethod());
        // Without a name, the reference targets the URI template of the operation declaring it by default
        $uriTemplate = $hydraOperation->getUriTemplate() ?? (null === $name ? $operation->getUriTemplate() : null);
        $match = $fallback = null;

        if (null !== $name || null !== $method) {
            foreach ($resourceMetadataCollection as $resource) {
                foreach ($resource->getOperations() ?? [] as $candidate) {
                    if ((null !== $name && $candidate->getName() !== $name) || (null !== $method && $candidate->getMethod() !== $method)) {
                        continue;
                    }

                    if (null === $uriTemplate || $candidate->getUriTemplate() === $uriTemplate) {
                        $match = $candidate;
                        break 2;
                    }

                    if (null === $fallback && null !== ($candidateUriTemplate = $candidate->getUriTemplate()) && UriTemplateHelper::withoutFormatSuffix($candidateUriTemplate) === UriTemplateHelper::withoutFormatSuffix($uriTemplate)) {
                        $fallback = $candidate;
                    }
                }
            }
        }

        $match ??= $fallback;
        if (null === $match) {
            throw new RuntimeException(\sprintf('The Hydra operation "%s" referenced by the operation "%s" is not declared on the resource "%s".', $name ?? trim($method.' '.$uriTemplate), $operation->getName(), $operation->getClass()));
        }

        return new HydraOperation(
            method: $match->getMethod(),
            uriTemplate: $match->getUriTemplate(),
            name: $match->getName(),
            security: $hydraOperation->getSecurity(),
        );
    }
}
