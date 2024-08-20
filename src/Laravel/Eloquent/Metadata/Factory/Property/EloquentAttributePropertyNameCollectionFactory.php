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

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Property\PropertyNameCollection;

final class EloquentAttributePropertyNameCollectionFactory implements PropertyNameCollectionFactoryInterface
{
    public function __construct(
        private readonly ?PropertyNameCollectionFactoryInterface $decorated = null,
    ) {
    }

    public function create(string $resourceClass, array $options = []): PropertyNameCollection
    {
        $properties = $this->decorated ? iterator_to_array($this->decorated->create($resourceClass, $options)) : [];

        if (!class_exists($resourceClass)) {
            return new PropertyNameCollection($properties);
        }

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
