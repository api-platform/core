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

namespace ApiPlatform\Laravel\Eloquent\State;

use ApiPlatform\Metadata\Exception\OperationNotFoundException;
use ApiPlatform\Metadata\GraphQl\Operation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @implements LinksHandlerInterface<Model>
 */
final class LinksHandler implements LinksHandlerInterface
{
    public function __construct(
        private readonly Application $application,
        private readonly ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory,
    ) {
    }

    public function handleLinks(Builder $builder, array $uriVariables, array $context): Builder
    {
        $operation = $context['operation'];

        if ($operation instanceof HttpOperation) {
            foreach (array_reverse($operation->getUriVariables() ?? []) as $uriVariable => $link) {
                $builder = $this->buildQuery($builder, $link, $uriVariables[$uriVariable]);
            }

            return $builder;
        }

        if (!($linkClass = $context['linkClass'] ?? false)) {
            return $builder;
        }

        $newLink = null;
        $linkedOperation = null;
        $linkProperty = $context['linkProperty'] ?? null;

        try {
            $resourceMetadataCollection = $this->resourceMetadataCollectionFactory->create($linkClass);
            $linkedOperation = $resourceMetadataCollection->getOperation($operation->getName());
        } catch (OperationNotFoundException) {
            // Instead, we'll look for the first Query available.
            foreach ($resourceMetadataCollection as $resourceMetadata) {
                foreach ($resourceMetadata->getGraphQlOperations() as $op) {
                    if ($op instanceof Query) {
                        $linkedOperation = $op;
                    }
                }
            }
        }

        if (!$linkedOperation instanceof Operation) {
            return $builder;
        }

        $resourceClass = $builder->getModel()::class;
        foreach ($linkedOperation->getLinks() ?? [] as $link) {
            if ($resourceClass === $link->getToClass() && $linkProperty === $link->getFromProperty()) {
                $newLink = $link;
                break;
            }
        }

        if (!$newLink) {
            return $builder;
        }

        return $this->buildQuery($builder, $newLink, $uriVariables[$newLink->getIdentifiers()[0]]);
    }

    /**
     * @param Builder<Model> $builder
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     *
     * @return Builder<Model> $builder
     */
    private function buildQuery(Builder $builder, Link $link, mixed $identifier): Builder
    {
        if ($to = $link->getToProperty()) {
            return $builder->where($builder->getModel()->{$to}()->getQualifiedForeignKeyName(), $identifier);
        }

        if ($from = $link->getFromProperty()) {
            $relation = $this->application->make($link->getFromClass());
            $relationQuery = $relation->{$from}();
            if (!method_exists($relationQuery, 'getQualifiedForeignKeyName') && method_exists($relationQuery, 'getQualifiedForeignPivotKeyName')) {
                return $builder->getModel()
                    ->join(
                        $relationQuery->getTable(), // @phpstan-ignore-line
                        $relationQuery->getQualifiedRelatedPivotKeyName(), // @phpstan-ignore-line
                        $builder->getModel()->getQualifiedKeyName()
                    )
                    ->where(
                        $relationQuery->getQualifiedForeignPivotKeyName(), // @phpstan-ignore-line
                        $identifier
                    )
                    ->select($builder->getModel()->getTable().'.*');
            }

            if (method_exists($relationQuery, 'dissociate')) {
                return $builder->getModel()
                       ->join(
                           $relationQuery->getParent()->getTable(), // @phpstan-ignore-line
                           $relationQuery->getParent()->getQualifiedKeyName(), // @phpstan-ignore-line
                           $identifier
                       );
            }

            return $builder->getModel()->where($relationQuery->getQualifiedForeignKeyName(), $identifier);
        }

        return $builder->where($builder->getModel()->qualifyColumn($link->getIdentifiers()[0]), $identifier);
    }
}
