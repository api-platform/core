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

namespace ApiPlatform\Laravel\Metadata\Property;

use ApiPlatform\Laravel\Eloquent\Metadata\ModelMetadata;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Exception\PropertyNotFoundException;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Symfony\Component\PropertyInfo\Type;

/**
 * Use Doctrine metadata to populate the identifier property.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class EloquentPropertyMetadataFactory implements PropertyMetadataFactoryInterface
{
    public function __construct(private ModelMetadata $modelMetadata, private readonly ?PropertyMetadataFactoryInterface $decorated = null)
    {
    }

    /**
     * {@inheritdoc}
     *
     * @param class-string $resourceClass
     */
    public function create(string $resourceClass, string $property, array $options = []): ApiProperty
    {
        try {
            $refl = new \ReflectionClass($resourceClass);
            $model = $refl->newInstanceWithoutConstructor();
        } catch (\ReflectionException) {
            return $this->decorated?->create($resourceClass, $property, $options) ?? new ApiProperty();
        }

        if (!$model instanceof Model) {
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

            $builtinType = $p['cast'] ?? $p['type'];
            if ('datetime' === $builtinType) {
                $type = new Type(Type::BUILTIN_TYPE_OBJECT, $p['nullable'], \DateTimeImmutable::class);
            } else {
                if (\in_array($builtinType, Type::$builtinTypes, true)) {
                    $type = new Type($builtinType, $p['nullable']);
                } else {
                    $type = new Type(Type::BUILTIN_TYPE_STRING, $p['nullable']);
                }
            }

            $propertyMetadata = $propertyMetadata
                ->withBuiltinTypes([$type])
                ->withWritable($propertyMetadata->isWritable() ?? true)
                ->withReadable($propertyMetadata->isReadable() ?? false === $p['hidden']);

            return $propertyMetadata;
        }

        foreach ($this->modelMetadata->getRelations($model) as $relation) {
            if ($relation['name'] !== $property) {
                continue;
            }

            $collection = false;
            if (HasMany::class === $relation['type']) {
                $collection = true;
            }

            $type = new Type($collection ? Type::BUILTIN_TYPE_ITERABLE : Type::BUILTIN_TYPE_OBJECT, false, $relation['related'], $collection, collectionValueType: new Type(Type::BUILTIN_TYPE_OBJECT, false, $relation['related']));
            $propertyMetadata = $propertyMetadata
                ->withBuiltinTypes([$type])
                ->withWritable($propertyMetadata->isWritable() ?? true)
                ->withReadable($propertyMetadata->isReadable() ?? true)
                ->withExtraProperties(['eloquent_relation' => $relation] + $propertyMetadata->getExtraProperties());

            return $propertyMetadata;
        }

        return $propertyMetadata;
    }
}
