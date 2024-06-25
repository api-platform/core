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

use ApiPlatform\JsonSchema\Metadata\Property\Factory\SchemaPropertyMetadataFactory;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use Illuminate\Database\Eloquent\Model;

final class EloquentAttributePropertyMetadataFactory implements PropertyMetadataFactoryInterface
{
    public function __construct(private readonly ?PropertyMetadataFactoryInterface $decorated = null)
    {
    }

    public function create(string $resourceClass, string $property, array $options = []): ApiProperty
    {
        if (!class_exists($resourceClass)) {
            return $this->decorated?->create($resourceClass, $property, $options) ?? new ApiProperty();
        }

        $refl = new \ReflectionClass($resourceClass);
        $model = $refl->newInstanceWithoutConstructor();

        $propertyMetadata = $this->decorated?->create($resourceClass, $property, $options);
        if (!$model instanceof Model) {
            return $propertyMetadata ?? new ApiProperty();
        }

        try {
            $method = $refl->getMethod($property);

            if ($attributes = $method->getAttributes(ApiProperty::class)) {
                return $this->createMetadata($attributes[0]->newInstance(), $propertyMetadata);
            }
        } catch (\ReflectionException) {
        }

        $attributes = $refl->getAttributes(ApiProperty::class);
        foreach ($attributes as $attribute) {
            $instance = $attribute->newInstance();
            if ($instance->getProperty() === $property) {
                return $this->createMetadata($instance, $propertyMetadata);
            }
        }

        return $propertyMetadata;
    }

    private function createMetadata(ApiProperty $attribute, ?ApiProperty $propertyMetadata = null): ApiProperty
    {
        if (null === $propertyMetadata) {
            return $this->handleUserDefinedSchema($attribute);
        }

        foreach (get_class_methods(ApiProperty::class) as $method) {
            if (preg_match('/^(?:get|is)(.*)/', (string) $method, $matches) && null !== $val = $attribute->{$method}()) {
                $propertyMetadata = $propertyMetadata->{"with{$matches[1]}"}($val);
            }
        }

        return $this->handleUserDefinedSchema($propertyMetadata);
    }

    private function handleUserDefinedSchema(ApiProperty $propertyMetadata): ApiProperty
    {
        // can't know later if the schema has been defined by the user or by API Platform
        // store extra key to make this difference
        if (null !== $propertyMetadata->getSchema()) {
            $extraProperties = $propertyMetadata->getExtraProperties() ?? [];
            $propertyMetadata = $propertyMetadata->withExtraProperties([SchemaPropertyMetadataFactory::JSON_SCHEMA_USER_DEFINED => true] + $extraProperties);
        }

        return $propertyMetadata;
    }
}
