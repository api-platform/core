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

use ApiPlatform\Metadata\HttpOperation;
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
    ) {
    }

    public function handleLinks(Builder $builder, array $uriVariables, array $context): Builder
    {
        $operation = $context['operation'];

        if ($operation instanceof HttpOperation) {
            foreach (array_reverse($operation->getUriVariables() ?? []) as $uriVariable => $link) {
                $identifier = $uriVariables[$uriVariable];

                if ($to = $link->getToProperty()) {
                    $builder = $builder->where($builder->getModel()->{$to}()->getQualifiedForeignKeyName(), $identifier);

                    continue;
                }

                if ($from = $link->getFromProperty()) {
                    $relation = $this->application->make($link->getFromClass());
                    $builder = $builder->getModel()->where($relation->{$from}()->getQualifiedForeignKeyName(), $identifier);

                    continue;
                }

                $builder->where($builder->getModel()->qualifyColumn($link->getIdentifiers()[0]), $identifier);
            }

            return $builder;
        }

        return $builder;
    }
}
