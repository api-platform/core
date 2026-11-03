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

final class StartSearchFilter implements FilterInterface
{
    use QueryPropertyTrait;

    /**
     * @param Builder<Model>       $builder
     * @param array<string, mixed> $context
     */
    public function apply(Builder $builder, mixed $values, Parameter $parameter, array $context = []): Builder
    {
        if (\is_array($values) && !array_is_list($values)) {
            return $builder;
        }

        $property = $this->getQueryProperty($parameter);
        $whereClause = $context['whereClause'] ?? 'where';

        if (!\is_array($values)) {
            return $builder->{$whereClause}($property, 'like', $values.'%');
        }

        return $builder->{$whereClause}(static function (Builder $group) use ($property, $values): void {
            foreach ($values as $value) {
                if (!\is_array($value)) {
                    $group->orWhere($property, 'like', $value.'%');
                }
            }
        });
    }
}
