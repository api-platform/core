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

use ApiPlatform\Metadata\IsApiResource;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;

/**
 * Creates a resource metadata from {@see IsApiResource} concerns.
 *
 * @author Kévin Dunglas <kevin@dunglas.dev>
 */
final class ConcernsResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    use MetadataCollectionFactoryTrait;

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass): ResourceMetadataCollection
    {
        $resourceMetadataCollection = $this->decorated?->create($resourceClass) ?? new ResourceMetadataCollection(
            $resourceClass
        );

        if (!method_exists($resourceClass, 'apiResource')) {
            return $resourceMetadataCollection;
        }

        $metadataCollection = $resourceClass::apiResource();
        if (!\is_array($metadataCollection)) {
            $metadataCollection = [$metadataCollection];
        }

        foreach ($this->buildResourceOperations($metadataCollection, $resourceClass) as $resource) {
            $resourceMetadataCollection[] = $resource;
        }

        return $resourceMetadataCollection;
    }
}
