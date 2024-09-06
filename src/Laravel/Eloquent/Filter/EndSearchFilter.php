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

final class EndSearchFilter implements FilterInterface
{
    use QueryPropertyTrait;

    /**
     * @param Builder<Model>       $builder
     * @param array<string, mixed> $context
     */
    public function apply(Builder $builder, mixed $values, Parameter $parameter, array $context = []): Builder
    {
        return $builder->{$context['whereClause'] ?? 'where'}($this->getQueryProperty($parameter), 'like', '%'.$values);
    }
}
