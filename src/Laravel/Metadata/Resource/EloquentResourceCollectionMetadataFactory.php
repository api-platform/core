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

namespace ApiPlatform\Laravel\Metadata\Resource;

use ApiPlatform\Laravel\Eloquent\State\CollectionProvider;
use ApiPlatform\Laravel\Eloquent\State\ItemProvider;
use ApiPlatform\Laravel\Eloquent\State\PersistProcessor;
use ApiPlatform\Laravel\Eloquent\State\RemoveProcessor;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use Illuminate\Database\Eloquent\Model;

class EloquentResourceCollectionMetadataFactory implements ResourceMetadataCollectionFactoryInterface
{
    private ResourceMetadataCollectionFactoryInterface $decorated;

    public function __construct(ResourceMetadataCollectionFactoryInterface $decorated)
    {
        $this->decorated = $decorated;
    }

    public function create(string $resourceClass): ResourceMetadataCollection
    {
        $resourceMetadataCollection = $this->decorated->create($resourceClass);

        try {
            $refl = new \ReflectionClass($resourceClass);
            $model = $refl->newInstanceWithoutConstructor();
        } catch (\ReflectionException) {
            return $this->decorated->create($resourceClass);
        }

        if (!$model instanceof Model) {
            return $resourceMetadataCollection;
        }

        foreach ($resourceMetadataCollection as $i => $resourceMetatada) {
            $operations = $resourceMetatada->getOperations();
            foreach ($operations as $operationName => $operation) {
                if (!$operation->getProvider()) {
                    $operation = $operation->withProvider($operation instanceof CollectionOperationInterface ? CollectionProvider::class : ItemProvider::class);
                }

                if (!$operation->getProcessor()) {
                    $operation = $operation->withProcessor($operation instanceof DeleteOperationInterface ? RemoveProcessor::class : PersistProcessor::class);
                }

                $operations->add($operationName, $operation);
            }

            $resourceMetadataCollection[$i] = $resourceMetatada->withOperations($operations);
        }

        return $resourceMetadataCollection;
    }
}
