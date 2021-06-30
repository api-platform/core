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

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class NormalizerResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    private readonly CamelCaseToSnakeCaseNameConverter $camelCaseToSnakeCaseNameConverter;

    public function __construct(private readonly ?ResourceMetadataCollectionFactoryInterface $decorated = null)
    {
        $this->camelCaseToSnakeCaseNameConverter = new CamelCaseToSnakeCaseNameConverter();
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

        return $this->normalizeResources($resourceMetadataCollection);
    }

    private function normalizeResources(ResourceMetadataCollection $resources): ResourceMetadataCollection
    {
        foreach ($resources as $i => $resource) {
            if ($resource->getOperations()) {
                $resources[$i] = $resource = $resource->withOperations($this->normalizeOperations($resource->getOperations()));
            }
            if ($resource->getGraphQlOperations()) {
                $resources[$i] = $resource->withGraphQlOperations($this->normalizeOperations($resource->getGraphQlOperations()));
            }
        }

        return $this->normalizeOperations($resources);
    }

    /**
     * @template T of ResourceMetadataCollection|Operations|Operation[]
     *
     * @param T $operations
     *
     * @return T
     */
    private function normalizeOperations(ResourceMetadataCollection|Operations|array $operations): ResourceMetadataCollection|Operations|array
    {
        $newOperations = $operations;
        if ($operations instanceof Operations) {
            $newOperations = [];
        }

        foreach ($operations as $i => $operation) {
            if ($operation->getTranslation()) {
                $translation = [];
                foreach ($operation->getTranslation() as $translationKey => $translationItem) {
                    $translation[$this->camelCaseToSnakeCaseNameConverter->normalize($translationKey)] = $translationItem;
                }
                $operation = $operation->withTranslation($translation);
            }

            $newOperations[$i] = $operation;
        }

        if ($operations instanceof Operations) {
            return new Operations($newOperations);
        }

        return $newOperations;
    }
}
