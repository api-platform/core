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
    public function __construct(private ModelMetadata $modelMetadata, private PropertyNameCollectionFactoryInterface $decorated, private ResourceClassResolverInterface $resourceClassResolver)
    {
    }

    /**
     * {@inheritdoc}
     *
     * @param class-string $resourceClass
     */
    public function create(string $resourceClass, array $options = []): PropertyNameCollection
    {
        if (!class_exists($resourceClass)) {
            return $this->decorated->create($resourceClass, $options);
        }

        try {
            $refl = new \ReflectionClass($resourceClass);
            $model = $refl->newInstanceWithoutConstructor();
        } catch (\ReflectionException) {
            return $this->decorated->create($resourceClass, $options);
        }

        if (!$model instanceof Model) {
            return $this->decorated->create($resourceClass, $options);
        }

        $properties = [];
        // When it's an Eloquent model we read attributes from database (@see ShowModelCommand)
        foreach ($this->modelMetadata->getAttributes($model) as $property) {
            if (!$property['primary'] && $property['hidden']) {
                continue;
            }

            $properties[] = $property['name'];
        }

        foreach ($this->modelMetadata->getRelations($model) as $relation) {
            if (!$this->resourceClassResolver->isResourceClass($relation['related'])) {
                continue;
            }

            $properties[] = $relation['name'];
        }

        return new PropertyNameCollection($properties);
    }
}
