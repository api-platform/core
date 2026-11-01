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

final class EqualsFilter implements FilterInterface
{
    use NestedPropertyTrait;
    use QueryPropertyTrait;

    /**
     * @param Builder<Model>       $builder
     * @param array<string, mixed> $context
     */
    public function apply(Builder $builder, mixed $values, Parameter $parameter, array $context = []): Builder
    {
        if (\is_array($values)) {
            $values = array_values(array_filter($values, static fn (mixed $value): bool => \is_scalar($value) || $value instanceof \DateTimeInterface));

            if (!$values) {
                return $builder;
            }

            return $this->addNestedParameterJoins(
                $builder,
                $parameter,
                static fn (Builder $query, string $property, string $whereClause) => $query->{$whereClause.'In'}($property, $values),
                $context['whereClause'] ?? 'where'
            );
        }

        return $this->addNestedParameterJoins(
            $builder,
            $parameter,
            static fn (Builder $query, string $property, string $whereClause) => $query->{$whereClause}($property, $values),
            $context['whereClause'] ?? 'where'
        );
    }
}
