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

namespace ApiPlatform\Laravel\Eloquent\Metadata;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

/**
 * Inspired from Illuminate\Database\Console\ShowModelCommand.
 *
 * @internal
 */
final class ModelMetadata
{
    /**
     * The methods that can be called in a model to indicate a relation.
     *
     * @var array
     */
    public const RELATION_METHODS = [
        'hasMany',
        'hasManyThrough',
        'hasOneThrough',
        'belongsToMany',
        'hasOne',
        'belongsTo',
        'morphOne',
        'morphTo',
        'morphMany',
        'morphToMany',
        'morphedByMany',
    ];

    /**
     * Get the first policy associated with this model.
     *
     * @param Model $model
     *
     * @return string
     */
    public function getPolicy($model)
    {
        $policy = Gate::getPolicyFor($model::class);

        return $policy ? $policy::class : null;
    }

    /**
     * Get the column attributes for the given model.
     *
     * @param Model $model
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAttributes($model)
    {
        $connection = $model->getConnection();
        $schema = $connection->getSchemaBuilder();
        $table = $model->getTable();
        $columns = $schema->getColumns($table);
        $indexes = $schema->getIndexes($table);

        return collect($columns)
            ->map(fn ($column) => [
                'name' => $column['name'],
                'type' => $column['type'],
                'increments' => $column['auto_increment'],
                'nullable' => $column['nullable'],
                'default' => $this->getColumnDefault($column, $model),
                'unique' => $this->columnIsUnique($column['name'], $indexes),
                'fillable' => $model->isFillable($column['name']),
                'hidden' => $this->attributeIsHidden($column['name'], $model),
                'appended' => null,
                'cast' => $this->getCastType($column['name'], $model),
            ])
            ->merge($this->getVirtualAttributes($model, $columns));
    }

    /**
     * Get the virtual (non-column) attributes for the given model.
     *
     * @param Model $model
     * @param array $columns
     *
     * @return \Illuminate\Support\Collection
     */
    public function getVirtualAttributes($model, $columns)
    {
        $class = new \ReflectionClass($model);

        return collect($class->getMethods())
            ->reject(
                fn (\ReflectionMethod $method) => $method->isStatic()
                    || $method->isAbstract()
                    || Model::class === $method->getDeclaringClass()->getName()
            )
            ->mapWithKeys(function (\ReflectionMethod $method) use ($model) {
                if (1 === preg_match('/^get(.+)Attribute$/', $method->getName(), $matches)) {
                    return [Str::snake($matches[1]) => 'accessor'];
                }
                if ($model->hasAttributeMutator($method->getName())) {
                    return [Str::snake($method->getName()) => 'attribute'];
                }

                return [];
            })
            ->reject(fn ($cast, $name) => collect($columns)->contains('name', $name))
            ->map(fn ($cast, $name) => [
                'name' => $name,
                'type' => null,
                'increments' => false,
                'nullable' => null,
                'default' => null,
                'unique' => null,
                'fillable' => $model->isFillable($name),
                'hidden' => $this->attributeIsHidden($name, $model),
                'appended' => $model->hasAppended($name),
                'cast' => $cast,
            ])
            ->values();
    }

    /**
     * Get the relations from the given model.
     *
     * @param Model $model
     *
     * @return \Illuminate\Support\Collection
     */
    public function getRelations($model)
    {
        return collect(get_class_methods($model))
            ->map(fn ($method) => new \ReflectionMethod($model, $method))
            ->reject(
                fn (\ReflectionMethod $method) => $method->isStatic()
                    || $method->isAbstract()
                    || Model::class === $method->getDeclaringClass()->getName()
                    || $method->getNumberOfParameters() > 0
            )
            ->filter(function (\ReflectionMethod $method) {
                if ($method->getReturnType() instanceof \ReflectionNamedType
                    && is_subclass_of($method->getReturnType()->getName(), Relation::class)) {
                    return true;
                }

                $file = new \SplFileObject($method->getFileName());
                $file->seek($method->getStartLine() - 1);
                $code = '';
                while ($file->key() < $method->getEndLine()) {
                    $code .= trim($file->current());
                    $file->next();
                }

                return collect(static::RELATION_METHODS)
                    ->contains(fn ($relationMethod) => str_contains($code, '$this->'.$relationMethod.'('));
            })
            ->map(function (\ReflectionMethod $method) use ($model) {
                $relation = $method->invoke($model);

                if (!$relation instanceof Relation) {
                    return null;
                }

                return [
                    'name' => $method->getName(),
                    'type' => $relation::class,
                    'related' => \get_class($relation->getRelated()),
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * Get the Events that the model dispatches.
     *
     * @param Model $model
     *
     * @return \Illuminate\Support\Collection
     */
    public function getEvents($model)
    {
        return collect($model->dispatchesEvents())
            ->map(fn (string $class, string $event) => [
                'event' => $event,
                'class' => $class,
            ])->values();
    }

    /**
     * Get the cast type for the given column.
     *
     * @param string $column
     * @param Model  $model
     *
     * @return string|null
     */
    private function getCastType($column, $model)
    {
        if ($model->hasGetMutator($column) || $model->hasSetMutator($column)) {
            return 'accessor';
        }

        if ($model->hasAttributeMutator($column)) {
            return 'attribute';
        }

        return $this->getCastsWithDates($model)->get($column) ?? null;
    }

    /**
     * Get the model casts, including any date casts.
     *
     * @param Model $model
     *
     * @return \Illuminate\Support\Collection
     */
    private function getCastsWithDates($model)
    {
        return collect($model->getDates())
            ->filter()
            ->flip()
            ->map(fn () => 'datetime')
            ->merge($model->getCasts());
    }

    /**
     * Get the default value for the given column.
     *
     * @param array $column
     * @param Model $model
     *
     * @return mixed|null
     */
    private function getColumnDefault($column, $model)
    {
        $attributeDefault = $model->getAttributes()[$column['name']] ?? null;

        return match (true) {
            $attributeDefault instanceof \BackedEnum => $attributeDefault->value,
            $attributeDefault instanceof \UnitEnum => $attributeDefault->name,
            default => $attributeDefault ?? $column['default'],
        };
    }

    /**
     * Determine if the given attribute is hidden.
     *
     * @param string $attribute
     * @param Model  $model
     *
     * @return bool
     */
    private function attributeIsHidden($attribute, $model)
    {
        if (\count($model->getHidden()) > 0) {
            return \in_array($attribute, $model->getHidden(), true);
        }

        if (\count($model->getVisible()) > 0) {
            return !\in_array($attribute, $model->getVisible(), true);
        }

        return false;
    }

    /**
     * Determine if the given attribute is unique.
     *
     * @param string $column
     * @param array  $indexes
     *
     * @return bool
     */
    private function columnIsUnique($column, $indexes)
    {
        return collect($indexes)->contains(
            fn ($index) => 1 === \count($index['columns']) && $index['columns'][0] === $column && $index['unique']
        );
    }
}
