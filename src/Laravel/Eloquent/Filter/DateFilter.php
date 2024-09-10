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

use ApiPlatform\Metadata\JsonSchemaFilterInterface;
use ApiPlatform\Metadata\OpenApiParameterFilterInterface;
use ApiPlatform\Metadata\Parameter;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\OpenApi\Model\Parameter as OpenApiParameter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class DateFilter implements FilterInterface, JsonSchemaFilterInterface, OpenApiParameterFilterInterface
{
    use QueryPropertyTrait;

    /**
     * @param Builder<Model>       $builder
     * @param array<string, mixed> $context
     */
    public function apply(Builder $builder, mixed $values, Parameter $parameter, array $context = []): Builder
    {
        if (!\is_array($values)) {
            return $builder;
        }

        $operatorValue = [
            'eq' => '=',
            'gt' => '>',
            'lt' => '<',
            'gte' => '>=',
            'lte' => '<=',
        ];

        $values = array_intersect_key($values, $operatorValue);

        if (!$values) {
            return $builder;
        }

        if (true === ($parameter->getFilterContext()['include_nulls'] ?? false)) {
            foreach ($values as $key => $value) {
                $datetime = $this->getDateTime($value);
                if (null === $datetime) {
                    continue;
                }
                $builder->{$context['whereClause'] ?? 'where'}(function (Builder $query) use ($parameter, $datetime, $operatorValue, $key): void {
                    $query->whereDate($this->getQueryProperty($parameter), $operatorValue[$key], $datetime)
                        ->orWhereNull($this->getQueryProperty($parameter));
                });
            }

            return $builder;
        }

        foreach ($values as $key => $value) {
            $datetime = $this->getDateTime($value);
            if (null === $datetime) {
                continue;
            }
            $builder = $builder->{($context['whereClause'] ?? 'where').'Date'}($this->getQueryProperty($parameter), $operatorValue[$key], $datetime);
        }

        return $builder;
    }

    /**
     * @return array<string, mixed>
     */
    public function getSchema(Parameter $parameter): array
    {
        return ['type' => 'date'];
    }

    public function getOpenApiParameters(Parameter $parameter): OpenApiParameter|array|null
    {
        $in = $parameter instanceof QueryParameter ? 'query' : 'header';

        return [
            new OpenApiParameter(name: $parameter->getKey().'[eq]', in: $in),
            new OpenApiParameter(name: $parameter->getKey().'[gt]', in: $in),
            new OpenApiParameter(name: $parameter->getKey().'[lt]', in: $in),
            new OpenApiParameter(name: $parameter->getKey().'[gte]', in: $in),
            new OpenApiParameter(name: $parameter->getKey().'[lte]', in: $in),
        ];
    }

    public function getDateTime($value): ?\DateTimeImmutable
    {
        try {
            return new \DateTimeImmutable($value);
        } catch (\DateMalformedStringException|\Exception) {
            return null;
        }
    }
}
