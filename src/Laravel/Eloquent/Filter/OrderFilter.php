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

final class OrderFilter implements FilterInterface
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
        // TODO: orderByRaw -> add protection against SQL injection

        if (null === $parameter->getFilterContext() || 'nulls_always_first' === $parameter->getFilterContext()['nulls_comparison']) {
            $values = explode('.', $values);

            return $builder->orderBy($values[0], $values[1]);
        }

        if ('nulls_always_last' === $parameter->getFilterContext()['nulls_comparison']) {
            $values = explode('.', $values);

            return $builder->orderByRaw("$values[0] $values[1] NULLS LAST");
        }

        if ('nulls_smallest' === $parameter->getFilterContext()['nulls_comparison']) {
            $values = explode('.', $values);

            if ('asc' === strtolower($values[1])) {
                return $builder->orderByRaw("$values[0] $values[1] NULLS FIRST");
            }

            return $builder->orderByRaw("$values[0] $values[1] NULLS LAST");
        }

        if ('nulls_largest' === $parameter->getFilterContext()['nulls_comparison']) {
            $values = explode('.', $values);

            if ('asc' === strtolower($values[1])) {
                return $builder->orderByRaw("$values[0] $values[1] NULLS LAST");
            }

            return $builder->orderByRaw("$values[0] $values[1] NULLS FIRST");
        }

        return $builder;
    }
}
