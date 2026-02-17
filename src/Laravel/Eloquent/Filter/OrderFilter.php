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
use ApiPlatform\OpenApi\Model\Parameter as OpenApiParameter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;

final class OrderFilter implements FilterInterface, JsonSchemaFilterInterface, OpenApiParameterFilterInterface
{
    use QueryPropertyTrait;

    /**
     * @param Builder<Model>       $builder
     * @param array<string, mixed> $context
     */
    public function apply(Builder $builder, mixed $values, Parameter $parameter, array $context = []): Builder
    {
        if (!\is_string($values)) {
            $properties = $parameter->getProperties() ?? [];

            foreach ($values as $key => $value) {
                if (!isset($properties[$key])) {
                    continue;
                }
                $builder = $builder->orderBy($properties[$key], $value);
            }

            return $builder;
        }

        $direction = strtoupper($values);
        if (!\in_array($direction, ['ASC', 'DESC'], true)) {
            return $builder;
        }

        $nestedInfo = $parameter->getExtraProperties()['nested_property_info'] ?? null;

        if (!$nestedInfo || 0 === \count($nestedInfo['relation_segments'])) {
            return $builder->orderBy($this->getQueryProperty($parameter), $direction);
        }

        $relationSegments = $nestedInfo['relation_segments'];
        $relationClasses = $nestedInfo['relation_classes'];
        $leafProperty = $nestedInfo['leaf_property'];

        $currentModel = $builder->getModel();
        foreach ($relationSegments as $i => $segment) {
            if (!method_exists($currentModel, $segment)) {
                return $builder;
            }

            $relation = $currentModel->{$segment}();
            $relatedTable = $relation->getRelated()->getTable();

            if ($relation instanceof BelongsTo) {
                $builder->leftJoin(
                    $relatedTable,
                    $currentModel->getTable().'.'.$relation->getForeignKeyName(),
                    '=',
                    $relatedTable.'.'.$relation->getOwnerKeyName()
                );
            } elseif ($relation instanceof HasOneOrMany) {
                $builder->leftJoin(
                    $relatedTable,
                    $currentModel->getTable().'.'.$relation->getLocalKeyName(),
                    '=',
                    $relatedTable.'.'.$relation->getForeignKeyName()
                );
            } else {
                return $builder;
            }

            $nextClass = $relationClasses[$i + 1] ?? null;
            /** @var Model $currentModel */
            $currentModel = $nextClass ? new $nextClass() : $relation->getRelated();
        }

        $builder->select($builder->getModel()->getTable().'.*');

        return $builder->orderBy($currentModel->getTable().'.'.$leafProperty, $direction);
    }

    /**
     * @return array<string, mixed>
     */
    public function getSchema(Parameter $parameter): array
    {
        return ['type' => 'string', 'enum' => ['asc', 'desc', 'ASC', 'DESC']];
    }

    /**
     * @return OpenApiParameter[]
     */
    public function getOpenApiParameters(Parameter $parameter): array
    {
        return [new OpenApiParameter(name: $parameter->getKey(), in: 'query')];
    }
}
