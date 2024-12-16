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
        if (!class_exists($resourceClass) || !is_a($resourceClass, Model::class, true)) {
            return $this->decorated?->create($resourceClass, $options) ?? new PropertyNameCollection();
        }

        try {
            $refl = new \ReflectionClass($resourceClass);
            if ($refl->isAbstract()) {
                return $this->decorated?->create($resourceClass, $options) ?? new PropertyNameCollection();
            }

            $model = $refl->newInstanceWithoutConstructor();
        } catch (\ReflectionException) {
            return $this->decorated?->create($resourceClass, $options) ?? new PropertyNameCollection();
        }

        /**
         * @var array<string, true> $properties
         */
        $properties = [];

        // When it's an Eloquent model we read attributes from database (@see ShowModelCommand)
        foreach ($this->modelMetadata->getAttributes($model) as $property) {
            if (!($property['primary'] ?? null) && $property['hidden']) {
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

        return new PropertyNameCollection(
            array_keys($properties)
        );
    }
}
