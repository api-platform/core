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

use ApiPlatform\Metadata\HasSchemaFilterInterface;
use ApiPlatform\Metadata\Parameter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class DateFilter implements FilterInterface, HasSchemaFilterInterface
{
    use QueryPropertyTrait;

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

        return $builder->{($context['whereClause'] ?? 'where').'Date'}($this->getQueryProperty($parameter), $datetime);
    }

    /**
     * @return array<string, mixed>
     */
    public function getSchema(Parameter $parameter): array
    {
        return ['type' => 'date'];
    }
}
