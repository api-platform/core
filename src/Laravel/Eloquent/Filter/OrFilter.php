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

use ApiPlatform\Metadata\HasOpenApiParameterFilterInterface;
use ApiPlatform\Metadata\HasSchemaFilterInterface;
use ApiPlatform\Metadata\Parameter;
use ApiPlatform\OpenApi\Model\Parameter as OpenApiParameter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final readonly class OrFilter implements FilterInterface, HasSchemaFilterInterface, HasOpenApiParameterFilterInterface
{
    public function __construct(private FilterInterface $filter)
    {
    }

    /**
     * @param Builder<Model>       $builder
     * @param array<string, mixed> $context
     */
    public function apply(Builder $builder, mixed $values, Parameter $parameter, array $context = []): Builder
    {
        return $builder->where(function ($builder) use ($values, $parameter, $context): void {
            foreach ($values as $value) {
                $this->filter->apply($builder, $value, $parameter, ['whereClause' => 'orWhere'] + $context);
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function getSchema(Parameter $parameter): array
    {
        $schema = $this->filter instanceof HasSchemaFilterInterface ? $this->filter->getSchema($parameter) : ['type' => 'string'];

        return ['type' => 'array', 'items' => $schema];
    }

    public function getOpenApiParameter(Parameter $parameter): ?OpenApiParameter
    {
        return new OpenApiParameter(name: $parameter->getKey().'[]', in: 'query', style: 'deepObject', explode: true);
    }
}
