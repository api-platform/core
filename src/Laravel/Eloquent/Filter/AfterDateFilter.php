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

namespace ApiPlatform\Laravel\Eloquent\Filter;

use ApiPlatform\Metadata\Parameter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class AfterDateFilter implements FilterInterface
{
    /**
     * @param Builder<Model>       $builder
     * @param array<string, mixed> $context
     */
    public function apply(Builder $builder, mixed $values, Parameter $parameter, array $context = []): Builder
    {
        if (!\is_string($values)) {
            return $builder;
        }

        $datetime = new \DateTimeImmutable($values);

        if (null === $parameter->getFilterContext() || 'exclude_null' === $parameter->getFilterContext()['nulls_comparison']) {
            return $builder->whereDate($parameter->getProperty(), '>=', $datetime);
        }

        if ('include_null' === $parameter->getFilterContext()['nulls_comparison']) {
            return $builder->where(function(Builder $query) use ($parameter, $datetime) {
                $query->whereDate($parameter->getProperty(), '>=', $datetime)
                    ->orWhereNull($parameter->getProperty());
            });
        }


        return $builder;
    }
}
