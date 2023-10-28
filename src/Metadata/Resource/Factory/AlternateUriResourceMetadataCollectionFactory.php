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

use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;

/**
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class AlternateUriResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
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
            if (0 === $i) {
                continue;
            }

            $resource = $resource->withExtraProperties($resource->getExtraProperties() + ['is_alternate_resource_metadata' => true]);
            $operations = $resource->getOperations();
            foreach ($operations as $key => $operation) {
                $operations->add($key, $operation->withExtraProperties($operation->getExtraProperties() + ['is_alternate_resource_metadata' => true]));
            }

            $resourceMetadataCollection[$i] = $resource->withOperations($operations);
        }

        return $resourceMetadataCollection;
    }
}
