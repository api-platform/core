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
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use Illuminate\Database\Eloquent\Model;

final class EloquentPropertyNameCollectionMetadataFactory implements PropertyNameCollectionFactoryInterface
{
    public function __construct(
        private readonly ModelMetadata $modelMetadata,
        private readonly ?PropertyNameCollectionFactoryInterface $decorated,
        private readonly ResourceClassResolverInterface $resourceClassResolver,
    ) {
    }

    /**
     * {@inheritdoc}
     *
     * @param class-string $resourceClass
     */
    public function create(string $resourceClass, array $options = []): PropertyNameCollection
    {
        $propertyNameCollection = $this->decorated?->create($resourceClass, $options);
        if (!class_exists($resourceClass) || !is_a($resourceClass, Model::class, true)) {
            return $propertyNameCollection ?? new PropertyNameCollection();
        }

        $refl = new \ReflectionClass($resourceClass);
        try {
            $model = $refl->newInstanceWithoutConstructor();
        } catch (\ReflectionException) {
            return $propertyNameCollection ?? new PropertyNameCollection();
        }

        $properties = $propertyNameCollection ? array_flip(iterator_to_array($propertyNameCollection)) : [];
        // When it's an Eloquent model we read attributes from database (@see ShowModelCommand)
        foreach ($this->modelMetadata->getAttributes($model) as $property) {
            if (!$property['primary'] && $property['hidden']) {
                continue;
            }

            $properties[$property['name']] = true;
        }

        foreach ($this->modelMetadata->getRelations($model) as $relation) {
            if (!$this->resourceClassResolver->isResourceClass($relation['related'])) {
                continue;
            }

            $properties[$relation['name']] = true;
        }

        return new PropertyNameCollection(array_keys($properties));
    }
}
