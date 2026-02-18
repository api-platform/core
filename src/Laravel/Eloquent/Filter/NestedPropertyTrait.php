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

/**
 * @internal
 */
trait NestedPropertyTrait
{
    /**
     * Applies a filter condition supporting nested properties via relationships.
     *
     * @param Builder<\Illuminate\Database\Eloquent\Model> $builder
     * @param callable                                     $condition Callback receiving ($query, $property) to apply the actual filter condition
     *
     * @return Builder<\Illuminate\Database\Eloquent\Model>
     */
    private function addNestedParameterJoins(
        Builder $builder,
        Parameter $parameter,
        callable $condition,
        string $whereClause = 'where',
    ): Builder {
        $nestedInfo = $parameter->getExtraProperties()['nested_property_info'] ?? null;

        if (!$nestedInfo) {
            // No nested property, use simple where clause
            $property = $this->getQueryProperty($parameter);

            return $condition($builder, $property, $whereClause);
        }

        // Handle nested property using whereHas
        // For Laravel Eloquent, use the original relation names (camelCase method names)
        // not the converted names (snake_case database columns)
        $relationSegments = $nestedInfo['relation_segments'];
        $leafProperty = $nestedInfo['leaf_property'];

        if (0 === \count($relationSegments)) {
            // Edge case: no relations, just a property
            return $condition($builder, $leafProperty, $whereClause);
        }

        // Build nested whereHas callbacks from innermost to outermost
        // For product.name: whereHas('product', fn($q) => $q->where('name', ...))
        // For product.variations.code: whereHas('product', fn($q) => $q->whereHas('variations', fn($q2) => $q2->where('code', ...)))

        $callback = static function ($query) use ($leafProperty, $condition, $whereClause) {
            return $condition($query, $leafProperty, $whereClause);
        };

        // Build the chain from the end to the beginning
        for ($i = \count($relationSegments) - 1; $i > 0; --$i) {
            $relation = $relationSegments[$i];
            $previousCallback = $callback;
            $callback = static function ($query) use ($relation, $previousCallback) {
                return $query->whereHas($relation, $previousCallback);
            };
        }

        // Apply the outermost whereHas
        return $builder->whereHas($relationSegments[0], $callback);
    }
}
