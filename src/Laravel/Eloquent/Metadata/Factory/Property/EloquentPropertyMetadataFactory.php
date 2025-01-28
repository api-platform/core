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

namespace ApiPlatform\Laravel\Eloquent\Metadata\Factory\Property;

use ApiPlatform\Laravel\Eloquent\Metadata\ModelMetadata;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Exception\PropertyNotFoundException;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Collection;
use Symfony\Component\PropertyInfo\Type;

/**
 * Uses Eloquent metadata to populate the identifier property.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class EloquentPropertyMetadataFactory implements PropertyMetadataFactoryInterface
{
    public function __construct(
        private readonly ModelMetadata $modelMetadata,
        private readonly ?PropertyMetadataFactoryInterface $decorated = null,
    ) {
    }

    /**
     * {@inheritdoc}
     *
     * @param class-string $resourceClass
     */
    public function create(string $resourceClass, string $property, array $options = []): ApiProperty
    {
        if (!is_a($resourceClass, Model::class, true)) {
            return $this->decorated?->create($resourceClass, $property, $options) ?? new ApiProperty();
        }

        try {
            $refl = new \ReflectionClass($resourceClass);
            $model = $refl->newInstanceWithoutConstructor();
        } catch (\ReflectionException) {
            return $this->decorated?->create($resourceClass, $property, $options) ?? new ApiProperty();
        }

        try {
            $propertyMetadata = $this->decorated?->create($resourceClass, $property, $options) ?? new ApiProperty();
        } catch (PropertyNotFoundException) {
            $propertyMetadata = new ApiProperty();
        }

        if ($model->getKeyName() === $property) {
            $propertyMetadata = $propertyMetadata->withIdentifier(true)->withWritable($propertyMetadata->isWritable() ?? false);
        }

        foreach ($this->modelMetadata->getAttributes($model) as $p) {
            if ($p['name'] !== $property) {
                continue;
            }

            // see https://laravel.com/docs/11.x/eloquent-mutators#attribute-casting
            $builtinType = $p['cast'] ?? $p['type'];
            $type = match ($builtinType) {
                'integer' => new Type(Type::BUILTIN_TYPE_INT, $p['nullable']),
                'double', 'real' => new Type(Type::BUILTIN_TYPE_FLOAT, $p['nullable']),
                'boolean', 'bool' => new Type(Type::BUILTIN_TYPE_BOOL, $p['nullable']),
                'datetime', 'date', 'timestamp' => new Type(Type::BUILTIN_TYPE_OBJECT, $p['nullable'], \DateTime::class),
                'immutable_datetime', 'immutable_date' => new Type(Type::BUILTIN_TYPE_OBJECT, $p['nullable'], \DateTimeImmutable::class),
                'collection', 'encrypted:collection' => new Type(Type::BUILTIN_TYPE_ITERABLE, $p['nullable'], Collection::class, true),
                'encrypted:array' => new Type(Type::BUILTIN_TYPE_ARRAY, $p['nullable']),
                'encrypted:object' => new Type(Type::BUILTIN_TYPE_OBJECT, $p['nullable']),
                default => new Type(\in_array($builtinType, Type::$builtinTypes, true) ? $builtinType : Type::BUILTIN_TYPE_STRING, $p['nullable'] ?? true),
            };

            return $propertyMetadata
                ->withBuiltinTypes([$type])
                ->withWritable($propertyMetadata->isWritable() ?? true === $p['fillable'])
                ->withReadable($propertyMetadata->isReadable() ?? false === $p['hidden']);
        }

        foreach ($this->modelMetadata->getRelations($model) as $relation) {
            if ($relation['name'] !== $property) {
                continue;
            }

            $collection = match ($relation['type']) {
                HasMany::class,
                HasManyThrough::class,
                BelongsToMany::class,
                MorphMany::class,
                MorphToMany::class => true,
                default => false,
            };

            $type = new Type($collection ? Type::BUILTIN_TYPE_ITERABLE : Type::BUILTIN_TYPE_OBJECT, false, $relation['related'], $collection, collectionValueType: new Type(Type::BUILTIN_TYPE_OBJECT, false, $relation['related']));

            return $propertyMetadata
                ->withBuiltinTypes([$type])
                ->withWritable($propertyMetadata->isWritable() ?? true)
                ->withReadable($propertyMetadata->isReadable() ?? true)
                ->withExtraProperties(['eloquent_relation' => $relation] + $propertyMetadata->getExtraProperties());
        }

        return $propertyMetadata;
    }
}
