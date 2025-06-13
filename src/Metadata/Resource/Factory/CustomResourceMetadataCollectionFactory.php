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

use ApiPlatform\Metadata\Extractor\ClosureExtractorInterface;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;

final class CustomResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    use OperationDefaultsTrait;

    public function __construct(
        private readonly ClosureExtractorInterface $resourceClosureExtractor,
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
            foreach ($this->resourceClosureExtractor->getClosures() as $closure) {
                $resource = $closure($resource);

                $operations = [];
                foreach ($resource->getOperations() as $operation) {
                    [$key, $operation] = $this->getOperationWithDefaults($resource, $operation);
                    $operations[$key] = $operation;
                }

                $resource = $resource->withOperations(new Operations($operations));
            }

            $newMetadataCollection[] = $resource;
        }

        return $newMetadataCollection;
    }
}
