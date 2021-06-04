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

namespace ApiPlatform\Core\Bridge\Eloquent\Metadata\Property\Factory;

use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyNameCollection;
use Illuminate\Database\Eloquent\Model;

/**
 * Add the identifier to the property name collection from an Eloquent model.
 *
 * @experimental
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class EloquentPropertyNameCollectionFactory implements PropertyNameCollectionFactoryInterface
{
    private $decorated;

    public function __construct(?PropertyNameCollectionFactoryInterface $decorated = null)
    {
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass, array $options = []): PropertyNameCollection
    {
        $propertyNames = [];
        $propertyNameCollection = null;

        if ($this->decorated) {
            try {
                $propertyNameCollection = $this->decorated->create($resourceClass, $options);
            } catch (ResourceClassNotFoundException $resourceClassNotFoundException) {
                // Ignore not found exceptions from decorated factory
            }
        }

        if (!is_subclass_of($resourceClass, Model::class, true)) {
            if (null !== $propertyNameCollection) {
                return $propertyNameCollection;
            }

            throw new ResourceClassNotFoundException(sprintf('The resource class "%s" is not an Eloquent model.', $resourceClass));
        }

        /** @var Model $model */
        $model = new $resourceClass();

        $propertyNames[$model->getKeyName()] = $model->getKeyName();

        if (null !== $propertyNameCollection) {
            foreach ($propertyNameCollection as $propertyName) {
                $propertyNames[$propertyName] = $propertyName;
            }
        }

        return new PropertyNameCollection(array_values($propertyNames));
    }
}
