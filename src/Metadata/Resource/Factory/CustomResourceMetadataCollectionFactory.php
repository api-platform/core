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
use Psr\Container\ContainerInterface;

final class CustomResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    public function __construct(
        private readonly ContainerInterface $resourceMutators,
        private readonly ?ResourceMetadataCollectionFactoryInterface $decorated = null,
    ) {
    }

    public function create(string $resourceClass): ResourceMetadataCollection
    {
        $resourceMetadataCollection = new ResourceMetadataCollection($resourceClass);
        if ($this->decorated) {
            $resourceMetadataCollection = $this->decorated->create($resourceClass);
        }

        $newMetadataCollection = new ResourceMetadataCollection($resourceClass);

        foreach ($resourceMetadataCollection as $resource) {
            foreach ($this->resourceMutators->get($resourceClass) as $mutators) {
                $resource = $mutators($resource);
            }

            $newMetadataCollection[] = $resource;
        }

        return $newMetadataCollection;
    }
}
