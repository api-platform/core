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
use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\GraphQl\Operation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
            /** @var Model $relatedInstance */
            $relatedInstance = $this->application->make($link->getFromClass());

            $identifierField = $link->getIdentifiers()[0];

            if ($identifierField !== $relatedInstance->getKeyName()) {
                $relatedInstance = $relatedInstance
                    ->newQuery()
                    ->where($identifierField, $identifier)
                    ->first();
            } else {
                $relatedInstance->setAttribute($identifierField, $identifier);
                $relatedInstance->exists = true;
            }

            if (!$relatedInstance) {
                throw new NotFoundHttpException('Not Found');
            }

            /** @var Relation<Model, Model, mixed> $relation */
            $relation = $relatedInstance->{$from}();

            if ($relation instanceof MorphTo) {
                throw new RuntimeException('Cannot query directly from a MorphTo relationship.');
            }

            if ($relation instanceof BelongsTo) {
                return $builder->getModel()
                    ->join(
                        $relation->getParent()->getTable(),
                        $relation->getParent()->getQualifiedKeyName(),
                        $identifier
                    );
            }

            if ($relation instanceof HasOneOrMany || $relation instanceof BelongsToMany) {
                return $relation->getQuery();
            }

            if (method_exists($relation, 'getQualifiedForeignKeyName')) {
                return $relation->getQuery()->where(
                    $relation->getQualifiedForeignKeyName(),
                    $identifier
                );
            }

            throw new RuntimeException(\sprintf('Unhandled or unknown relationship type: %s for property %s on %s', $relation::class, $from, $relatedInstance::class));
        }

        return $builder->where(
            $builder->getModel()->qualifyColumn($link->getIdentifiers()[0]),
            $identifier
        );
    }
}
