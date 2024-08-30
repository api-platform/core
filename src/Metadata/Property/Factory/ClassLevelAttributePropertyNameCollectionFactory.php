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

namespace ApiPlatform\Metadata\Property\Factory;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Property\PropertyNameCollection;

/**
 * Gathers "virtual" properties created using the ApiProperty attribute at class level.
 */
final class ClassLevelAttributePropertyNameCollectionFactory implements PropertyNameCollectionFactoryInterface
{
    public function __construct(
        private readonly ?PropertyNameCollectionFactoryInterface $decorated = null,
    ) {
    }

    public function create(string $resourceClass, array $options = []): PropertyNameCollection
    {
        $parentPropertyNameCollection = $this->decorated?->create($resourceClass, $options);
        if (!class_exists($resourceClass)) {
            return $parentPropertyNameCollection ?? new PropertyNameCollection();
        }

        $properties = $parentPropertyNameCollection ? iterator_to_array($parentPropertyNameCollection) : [];

        $refl = new \ReflectionClass($resourceClass);
        $attributes = $refl->getAttributes(ApiProperty::class);
        foreach ($attributes as $attribute) {
            $instance = $attribute->newInstance();
            if ($property = $instance->getProperty()) {
                $properties[] = $property;
            }
        }

        return new PropertyNameCollection($properties);
    }
}
