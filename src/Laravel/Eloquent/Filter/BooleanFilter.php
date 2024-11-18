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

final class BooleanFilter implements FilterInterface
{
    use QueryPropertyTrait;

    /**
     * @param Builder<Model>       $builder
     * @param array<string, mixed> $context
     */
    public function apply(Builder $builder, mixed $values, Parameter $parameter, array $context = []): Builder
    {
        $booleanValues = [
            'true' => true,
            'false' => false,
            '1' => true,
            '0' => false,
        ];

        if (array_key_exists($values, $booleanValues)) {
            $values = $booleanValues[$values];
        }

        return $builder->{$context['whereClause'] ?? 'where'}($this->getQueryProperty($parameter), $values);
    }
}
