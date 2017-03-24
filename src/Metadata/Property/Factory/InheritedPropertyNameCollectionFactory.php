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

namespace ApiPlatform\Core\Metadata\Property\Factory;

use ApiPlatform\Core\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;

/**
 * Creates a property name collection from eventual child inherited properties.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class InheritedPropertyNameCollectionFactory implements PropertyNameCollectionFactoryInterface
{
    private $resourceNameCollection;
    private $decorated;

    public function __construct(ResourceNameCollectionFactoryInterface $resourceNameCollection, PropertyNameCollectionFactoryInterface $decorated = null)
    {
        $this->resourceNameCollection = $resourceNameCollection;
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass, array $options = []): PropertyNameCollection
    {
        $propertyNames = [];

        // Inherited from parent
        foreach ($this->decorated->create($resourceClass, $options) as $propertyName) {
            $propertyNames[$propertyName] = true;
        }

        foreach ($this->resourceNameCollection->create() as $knownResourceClass) {
            if ($resourceClass === $knownResourceClass) {
                continue;
            }

            if (is_subclass_of($knownResourceClass, $resourceClass)) {
                foreach ($this->create($knownResourceClass) as $propertyName) {
                    $propertyNames[$propertyName] = true;
                }
            }
        }

        return new PropertyNameCollection(array_keys($propertyNames));
    }
}
