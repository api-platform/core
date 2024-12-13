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

namespace ApiPlatform\Laravel\Eloquent\Metadata\Factory\Resource;

use ApiPlatform\Laravel\Eloquent\State\CollectionProvider;
use ApiPlatform\Laravel\Eloquent\State\ItemProvider;
use ApiPlatform\Laravel\Eloquent\State\PersistProcessor;
use ApiPlatform\Laravel\Eloquent\State\RemoveProcessor;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\GraphQl\DeleteMutation;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use ApiPlatform\Metadata\GraphQl\Subscription;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

final class EloquentResourceCollectionMetadataFactory implements ResourceMetadataCollectionFactoryInterface
{
    private const POLICY_METHODS = [
        Put::class => 'update',
        Post::class => 'create',
        Get::class => 'view',
        GetCollection::class => 'viewAny',
        Delete::class => 'delete',
        Patch::class => 'update',

        Query::class => 'view',
        QueryCollection::class => 'viewAny',
        Mutation::class => 'update',
        DeleteMutation::class => 'delete',
        Subscription::class => 'viewAny',
    ];

    public function __construct(
        private readonly ResourceMetadataCollectionFactoryInterface $decorated,
    ) {
    }

    /**
     * @param class-string $resourceClass
     */
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

        foreach ($resourceMetadataCollection as $i => $resourceMetadata) {
            $operations = $resourceMetadata->getOperations();
            foreach ($operations ?? [] as $operationName => $operation) {
                if (!$operation->getProvider()) {
                    $operation = $operation->withProvider($operation instanceof CollectionOperationInterface ? CollectionProvider::class : ItemProvider::class);
                }

                if (!$operation->getPolicy() && ($policy = Gate::getPolicyFor($model))) {
                    $policyMethod = self::POLICY_METHODS[$operation::class] ?? null;
                    if ($operation instanceof Put && $operation->getAllowCreate()) {
                        $policyMethod = self::POLICY_METHODS[Post::class];
                    }

                    if ($policyMethod && method_exists($policy, $policyMethod)) {
                        $operation = $operation->withPolicy($policyMethod);
                    }
                }

                if (!$operation->getProcessor()) {
                    $operation = $operation->withProcessor($operation instanceof DeleteOperationInterface ? RemoveProcessor::class : PersistProcessor::class);
                }

                $operations->add($operationName, $operation);
            }

            $resourceMetadataCollection[$i] = $resourceMetadata->withOperations($operations);

            $graphQlOperations = $resourceMetadata->getGraphQlOperations();
            foreach ($graphQlOperations ?? [] as $operationName => $graphQlOperation) {
                if (!$graphQlOperation->getPolicy() && ($policy = Gate::getPolicyFor($model))) {
                    if (($policyMethod = self::POLICY_METHODS[$graphQlOperation::class] ?? null) && method_exists($policy, $policyMethod)) {
                        $graphQlOperation = $graphQlOperation->withPolicy($policyMethod);
                    }
                }

                if (!$graphQlOperation->getProvider()) {
                    $graphQlOperation = $graphQlOperation->withProvider($graphQlOperation instanceof CollectionOperationInterface ? CollectionProvider::class : ItemProvider::class);
                }

                if (!$graphQlOperation->getProcessor()) {
                    $graphQlOperation = $graphQlOperation->withProcessor($graphQlOperation instanceof DeleteOperationInterface ? RemoveProcessor::class : PersistProcessor::class);
                }

                $graphQlOperations[$operationName] = $graphQlOperation;
            }

            if ($graphQlOperations) {
                $resourceMetadata = $resourceMetadata->withGraphQlOperations($graphQlOperations);
            }

            $resourceMetadataCollection[$i] = $resourceMetadata;
        }

        return $resourceMetadataCollection;
    }
}
