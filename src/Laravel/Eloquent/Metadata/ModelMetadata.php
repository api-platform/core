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
use Illuminate\Support\Str;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

/**
 * Inspired from Illuminate\Database\Console\ShowModelCommand.
 *
 * @internal
 */
final class ModelMetadata
{
    /**
     * @var array<class-string, array<string, mixed>>
     */
    private $attributesLocalCache = [];

    /**
     * @var array<class-string, array<string, mixed>>
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

    public function __construct(private NameConverterInterface $relationNameConverter = new CamelCaseToSnakeCaseNameConverter())
    {
    }

    /**
     * Gets the column attributes for the given model.
     *
     * @return array<string, mixed>
     */
    public function getAttributes(Model $model): array
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

        $foreignKeys = array_flip(array_column($relations, 'foreign_key'));
        $attributes = [];

        foreach ($columns as $column) {
            if (isset($foreignKeys[$column['name']])) {
                continue;
            }

            $attributes[$column['name']] = [
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
            ];
        }

        return $this->attributesLocalCache[$model::class] = array_merge($attributes, $this->getVirtualAttributes($model, $columns));
    }

    /**
     * @param array<int, array{columns: string[], primary?: bool}> $indexes
     */
    private function isColumnPrimaryKey(array $indexes, string $column): bool
    {
        foreach ($indexes as $index) {
            if (\in_array($column, $index['columns'], true) && (true === ($index['primary'] ?? false))) {
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
     * @return array<string, mixed>
     */
    private function getVirtualAttributes(Model $model, array $columns): array
    {
        $class = new \ReflectionClass($model);
        $virtualAttributes = [];

        $columnNames = array_flip(array_column($columns, 'name'));

        foreach ($class->getMethods() as $method) {
            if (
                $method->isStatic()
                || $method->isAbstract()
                // Skips methods from the base Eloquent Model class
                || Model::class === $method->getDeclaringClass()->getName()
            ) {
                continue;
            }

            $methodName = $method->getName();
            $name = null;
            $cast = null;

            if (1 === preg_match('/^get(.+)Attribute$/', $methodName, $matches)) {
                $name = Str::snake($matches[1]);
                $cast = 'accessor';
            } elseif ($model->hasAttributeMutator($methodName)) {
                $name = Str::snake($methodName);
                $cast = 'attribute';
            }

            // If the method is not a virtual attribute, or if it conflicts with a real column, skip it.
            if (null === $name || isset($columnNames[$name])) {
                continue;
            }

            $virtualAttributes[$name] = [
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
            ];
        }

        return $virtualAttributes;
    }

    /**
     * Gets the relations from the given model.
     *
     * @return array<string, mixed>
     */
    public function getRelations(Model $model): array
    {
        if (isset($this->relationsLocalCache[$model::class])) {
            return $this->relationsLocalCache[$model::class];
        }

        $relations = [];
        $class = new \ReflectionClass($model);

        foreach ($class->getMethods() as $method) {
            if (
                $method->isStatic()
                || $method->isAbstract()
                || $method->getNumberOfParameters() > 0
                || Model::class === $method->getDeclaringClass()->getName()
                || $this->attributeIsHidden($method->getName(), $model)
            ) {
                continue;
            }

            $isRelation = false;
            if ($method->getReturnType() instanceof \ReflectionNamedType && is_subclass_of($method->getReturnType()->getName(), Relation::class)) {
                $isRelation = true;
            } elseif (false !== $method->getFileName()) {
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

                foreach (self::RELATION_METHODS as $relationMethod) {
                    if (str_contains($code, '$this->'.$relationMethod.'(')) {
                        $isRelation = true;
                        break;
                    }
                }
            }

            if (!$isRelation) {
                continue;
            }

            $relation = $method->invoke($model);
            if (!$relation instanceof Relation) {
                continue;
            }

            $relationName = $this->relationNameConverter->normalize($method->getName());
            $relations[$relationName] = [
                'name' => $relationName,
                'method_name' => $method->getName(),
                'type' => $relation::class,
                'related' => \get_class($relation->getRelated()),
                'foreign_key' => method_exists($relation, 'getForeignKeyName') ? $relation->getForeignKeyName() : null,
            ];
        }

        return $this->relationsLocalCache[$model::class] = $relations;
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

        return $this->getCastsWithDates($model)[$column] ?? null;
    }

    /**
     * Gets the model casts, including any date casts.
     *
     * @return array<string, mixed>
     */
    private function getCastsWithDates(Model $model): array
    {
        $dateCasts = [];

        foreach ($model->getDates() as $date) {
            if (!empty($date)) {
                $dateCasts[$date] = 'datetime';
            }
        }

        return array_merge($dateCasts, $model->getCasts());
    }

    /**
     * Gets the default value for the given column.
     *
     * @param array<string, mixed> $column
     *
     * @phpstan-param array<string, mixed> $column
     *
     * @psalm-param array{name: string, default: string, ...<string, mixed>} $column
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
