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

    private const OPERATOR_VALUE = [
        'eq' => '=',
        'gt' => '>',
        'lt' => '<',
        'gte' => '>=',
        'lte' => '<=',
    ];

    /**
     * @param Builder<Model>       $builder
     * @param array<string, mixed> $context
     */
    public function apply(Builder $builder, mixed $values, Parameter $parameter, array $context = []): Builder
    {
        if (!\is_array($values)) {
            return $builder;
        }

        $values = array_intersect_key($values, self::OPERATOR_VALUE);

        if (!$values) {
            return $builder;
        }

        if (true === ($parameter->getFilterContext()['include_nulls'] ?? false)) {
            foreach ($values as $key => $value) {
                $datetime = $this->getDateTime($value);
                if (null === $datetime) {
                    continue;
                }
                $builder->{$context['whereClause'] ?? 'where'}(function (Builder $query) use ($parameter, $datetime, $key): void {
                    $queryProperty = $this->getQueryProperty($parameter);
                    $query->whereDate($queryProperty, self::OPERATOR_VALUE[$key], $datetime)
                        ->orWhereNull($queryProperty);
                });
            }

            return $builder;
        }

        foreach ($values as $key => $value) {
            $datetime = $this->getDateTime($value);
            if (null === $datetime) {
                continue;
            }
            $builder = $builder->{($context['whereClause'] ?? 'where').'Date'}($this->getQueryProperty($parameter), self::OPERATOR_VALUE[$key], $datetime);
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

    /**
     * @return OpenApiParameter[]
     */
    public function getOpenApiParameters(Parameter $parameter): array
    {
        $in = $parameter instanceof QueryParameter ? 'query' : 'header';
        $key = $parameter->getKey();

        return [
            new OpenApiParameter(name: $key.'[eq]', in: $in),
            new OpenApiParameter(name: $key.'[gt]', in: $in),
            new OpenApiParameter(name: $key.'[lt]', in: $in),
            new OpenApiParameter(name: $key.'[gte]', in: $in),
            new OpenApiParameter(name: $key.'[lte]', in: $in),
        ];
    }

    private function getDateTime(string $value): ?\DateTimeImmutable
    {
        try {
            return new \DateTimeImmutable($value);
        } catch (\DateMalformedStringException|\Exception) {
            return null;
        }
    }
}
