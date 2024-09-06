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

use ApiPlatform\Metadata\Exception\ResourceClassNotFoundException;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;

/**
 * Creates a resource metadata from {@see ApiResource} annotations.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class AttributesResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    use MetadataCollectionFactoryTrait;

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass): ResourceMetadataCollection
    {
        $resourceMetadataCollection = $this->decorated?->create($resourceClass) ?? new ResourceMetadataCollection($resourceClass);

        try {
            $reflectionClass = new \ReflectionClass($resourceClass);
        } catch (\ReflectionException) {
            throw new ResourceClassNotFoundException(\sprintf('Resource "%s" not found.', $resourceClass));
        }

        $metadataCollection = [];
        foreach ($reflectionClass->getAttributes() as $attribute) {
            $name = $attribute->getName();
            if ($this->isResourceMetadata($name)) {
                $metadataCollection[] = $attribute->newInstance();
            }
        }

        $resultCollection = new ResourceMetadataCollection($resourceClass);
        foreach ($this->buildResourceOperations($metadataCollection, $resourceClass, iterator_to_array($resourceMetadataCollection)) as $resource) {
            $resultCollection[] = $resource;
        }

        return $resultCollection;
    }
}
