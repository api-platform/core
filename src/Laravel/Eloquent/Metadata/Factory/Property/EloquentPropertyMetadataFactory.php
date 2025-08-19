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
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\TypeIdentifier;

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
            $propertyMetadata = $propertyMetadata->withIdentifier(true);
        }

        foreach ($this->modelMetadata->getAttributes($model) as $p) {
            if ($p['name'] !== $property) {
                continue;
            }

            // see https://laravel.com/docs/11.x/eloquent-mutators#attribute-casting
            $builtinType = $p['cast'] ?? $p['type'];
            $type = match ($builtinType) {
                'integer' => Type::int(),
                'double', 'real' => Type::float(),
                'boolean', 'bool' => Type::bool(),
                'datetime', 'date', 'timestamp' => Type::object(\DateTime::class),
                'immutable_datetime', 'immutable_date' => Type::object(\DateTimeImmutable::class),
                'collection', 'encrypted:collection' => Type::collection(Type::object(Collection::class)),
                'encrypted:array' => Type::builtin(TypeIdentifier::ARRAY),
                'encrypted:object' => Type::object(),
                default => \in_array($builtinType, TypeIdentifier::values(), true) ? Type::builtin($builtinType) : Type::string(),
            };

            if ($p['nullable']) {
                $type = Type::nullable($type);
            }

            $propertyMetadata = $propertyMetadata
                ->withNativeType($type);

            return $propertyMetadata;
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

            $type = Type::object($relation['related']);
            if ($collection) {
                $type = Type::iterable($type);
            }

            return $propertyMetadata
                ->withNativeType($type)
                ->withExtraProperties(['eloquent_relation' => $relation] + $propertyMetadata->getExtraProperties());
        }

        return $propertyMetadata;
    }
}
