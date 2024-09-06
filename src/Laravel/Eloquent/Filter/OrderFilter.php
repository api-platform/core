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

final class OrderFilter implements FilterInterface, HasSchemaFilterInterface, HasOpenApiParameterFilterInterface
{
    use QueryPropertyTrait;

    /**
     * @param Builder<Model>       $builder
     * @param array<string, mixed> $context
     */
    public function apply(Builder $builder, mixed $values, Parameter $parameter, array $context = []): Builder
    {
        if (!\is_string($values)) {
            $properties = $parameter->getExtraProperties()['_properties'] ?? [];

            foreach ($values as $key => $value) {
                if (!isset($properties[$key])) {
                    continue;
                }

                $builder = $builder->orderBy($properties[$key], $value);
            }

            return $builder;
        }

        return $builder->orderBy($this->getQueryProperty($parameter), $values);
    }

    /**
     * @return array<string, mixed>
     */
    public function getSchema(Parameter $parameter): array
    {
        if (str_contains($parameter->getKey(), ':property')) {
            $properties = [];
            foreach (array_keys($parameter->getExtraProperties()['_properties'] ?? []) as $property) {
                $properties[$property] = ['type' => 'string', 'enum' => ['asc', 'desc']];
            }

            return ['type' => 'object', 'properties' => $properties, 'required' => []];
        }

        return ['type' => 'string', 'enum' => ['asc', 'desc']];
    }

    public function getOpenApiParameter(Parameter $parameter): ?OpenApiParameter
    {
        if (str_contains($parameter->getKey(), ':property')) {
            return new OpenApiParameter(name: str_replace('[:property]', '', $parameter->getKey()), in: 'query', style: 'deepObject', explode: true);
        }

        return null;
    }
}
