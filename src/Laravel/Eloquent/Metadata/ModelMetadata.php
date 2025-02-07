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
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Inspired from Illuminate\Database\Console\ShowModelCommand.
 *
 * @internal
 */
final class ModelMetadata
{
    /**
     * @var array<class-string, Collection<string, mixed>>
     */
    private $attributesLocalCache = [];

    /**
     * @var array<class-string, Collection<int, mixed>>
     */
    private $relationsLocalCache = [];

    /**
     * The methods that can be called in a model to indicate a relation.
     *
     * @var string[]
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
     * Gets the column attributes for the given model.
     *
     * @return Collection<string, mixed>
     */
    public function getAttributes(Model $model): Collection
    {
        if (isset($this->attributesLocalCache[$model::class])) {
            return $this->attributesLocalCache[$model::class];
        }

        $connection = $model->getConnection();
        $schema = $connection->getSchemaBuilder();
        $table = $model->getTable();
        $columns = $schema->getColumns($table);
        $indexes = $schema->getIndexes($table);
        $relations = $this->getRelations($model);

        return $this->attributesLocalCache[$model::class] = collect($columns)
            ->reject(
                fn ($column) => $relations->contains(
                    fn ($relation) => $relation['foreign_key'] === $column['name']
                )
            )
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
                'primary' => $this->isColumnPrimaryKey($indexes, $column['name']),
            ])
            ->merge($this->getVirtualAttributes($model, $columns));
    }

    /**
     * @param array<int, array{columns: string[]}> $indexes
     */
    private function isColumnPrimaryKey(array $indexes, string $column): bool
    {
        foreach ($indexes as $index) {
            if (\in_array($column, $index['columns'], true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the virtual (non-column) attributes for the given model.
     *
     * @param array<string, mixed> $columns
     *
     * @return Collection<int, mixed>
     */
    private function getVirtualAttributes(Model $model, array $columns): Collection
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
     * Gets the relations from the given model.
     *
     * @return Collection<int, mixed>
     */
    public function getRelations(Model $model): Collection
    {
        if (isset($this->relationsLocalCache[$model::class])) {
            return $this->relationsLocalCache[$model::class];
        }

        return $this->relationsLocalCache[$model::class] = collect(get_class_methods($model))
            ->map(fn ($method) => new \ReflectionMethod($model, $method))
            ->reject(
                fn (\ReflectionMethod $method) => $method->isStatic()
                    || $method->isAbstract()
                    || Model::class === $method->getDeclaringClass()->getName()
                    || $method->getNumberOfParameters() > 0
                    || $this->attributeIsHidden($method->getName(), $model)
            )
            ->filter(function (\ReflectionMethod $method) {
                if ($method->getReturnType() instanceof \ReflectionNamedType
                    && is_subclass_of($method->getReturnType()->getName(), Relation::class)) {
                    return true;
                }

                if (false === $method->getFileName()) {
                    return false;
                }

                $file = new \SplFileObject($method->getFileName());
                $file->seek($method->getStartLine() - 1);
                $code = '';
                while ($file->key() < $method->getEndLine()) {
                    $current = $file->current();
                    if (\is_string($current)) {
                        $code .= trim($current);
                    }

                    $file->next();
                }

                return collect(self::RELATION_METHODS)
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
                    'foreign_key' => method_exists($relation, 'getForeignKeyName') ? $relation->getForeignKeyName() : null,
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * Gets the cast type for the given column.
     */
    private function getCastType(string $column, Model $model): ?string
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
     * Gets the model casts, including any date casts.
     *
     * @return Collection<string, mixed>
     */
    private function getCastsWithDates(Model $model): Collection
    {
        return collect($model->getDates())
            ->filter()
            ->flip()
            ->map(fn () => 'datetime')
            ->merge($model->getCasts());
    }

    /**
     * Gets the default value for the given column.
     *
     * @param array<string, mixed>&array{name: string, default: string} $column
     */
    private function getColumnDefault(array $column, Model $model): mixed
    {
        $attributeDefault = $model->getAttributes()[$column['name']] ?? null;

        return match (true) {
            $attributeDefault instanceof \BackedEnum => $attributeDefault->value,
            $attributeDefault instanceof \UnitEnum => $attributeDefault->name,
            default => $attributeDefault ?? $column['default'],
        };
    }

    /**
     * Determines if the given attribute is hidden.
     */
    private function attributeIsHidden(string $attribute, Model $model): bool
    {
        if ($visible = $model->getVisible()) {
            return !\in_array($attribute, $visible, true);
        }

        if ($hidden = $model->getHidden()) {
            return \in_array($attribute, $hidden, true);
        }

        return false;
    }

    /**
     * Determines if the given attribute is unique.
     *
     * @param array<int, array{columns: string[], unique: bool}> $indexes
     */
    private function columnIsUnique(string $column, array $indexes): bool
    {
        return collect($indexes)->contains(
            fn ($index) => 1 === \count($index['columns']) && $index['columns'][0] === $column && $index['unique']
        );
    }
}
