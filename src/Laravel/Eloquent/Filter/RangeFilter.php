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

use ApiPlatform\Metadata\OpenApiParameterFilterInterface;
use ApiPlatform\Metadata\JsonSchemaFilterInterface;
use ApiPlatform\Metadata\Parameter;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\OpenApi\Model\Parameter as OpenApiParameter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class RangeFilter implements FilterInterface, JsonSchemaFilterInterface, OpenApiParameterFilterInterface
{
    use QueryPropertyTrait;

    /**
     * @param Builder<Model>       $builder
     * @param array<string, mixed> $context
     */
    public function apply(Builder $builder, mixed $values, Parameter $parameter, array $context = []): Builder
    {
        $operatorValue = [
            'lt' => '<',
            'gt' => '>',
            'lte' => '<=',
            'gte' => '>=',
        ];

        foreach ($values as $key => $value) {
            $builder = $builder->{$context['whereClause'] ?? 'where'}($this->getQueryProperty($parameter), $operatorValue[$key], $value);
        }

        return $builder;
    }

    public function getSchema(Parameter $parameter): array
    {
        return ['type' => 'number'];
    }

    public function getOpenApiParameters(Parameter $parameter): OpenApiParameter|array|null
    {
        $in = $parameter instanceof QueryParameter ? 'query' : 'header';

        return [
            new OpenApiParameter(name: $parameter->getKey().'[gt]', in: $in),
            new OpenApiParameter(name: $parameter->getKey().'[lt]', in: $in),
            new OpenApiParameter(name: $parameter->getKey().'[gte]', in: $in),
            new OpenApiParameter(name: $parameter->getKey().'[lte]', in: $in),
        ];
    }
}
