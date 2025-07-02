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

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Mutator\OperationMutatorCollectionInterface;
use ApiPlatform\Metadata\Mutator\ResourceMutatorCollectionInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;

final class MutatorResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    public function __construct(
        private readonly ResourceMutatorCollectionInterface $resourceMutators,
        private readonly OperationMutatorCollectionInterface $operationMutators,
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
            $resource = $this->mutateResource($resource, $resourceClass);
            $operations = $this->mutateOperations($resource->getOperations() ?? new Operations());
            $resource = $resource->withOperations($operations);

            $newMetadataCollection[] = $resource;
        }

        return $newMetadataCollection;
    }

    private function mutateResource(ApiResource $resource, string $resourceClass): ApiResource
    {
        foreach ($this->resourceMutators->get($resourceClass) as $mutator) {
            $resource = $mutator($resource);
        }

        return $resource;
    }

    private function mutateOperations(Operations $operations): Operations
    {
        $newOperations = new Operations();

        /** @var Operation $operation */
        foreach ($operations as $key => $operation) {
            foreach ($this->operationMutators->get($key) as $mutator) {
                $operation = $mutator($operation);
            }

            $newOperations->add($key, $operation);
        }

        return $newOperations;
    }
}
