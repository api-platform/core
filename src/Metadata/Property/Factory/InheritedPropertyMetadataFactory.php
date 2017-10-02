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

use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;

/**
 * Get property metadata from eventual child inherited properties.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class InheritedPropertyMetadataFactory implements PropertyMetadataFactoryInterface
{
    private $resourceNameCollection;
    private $decorated;

    public function __construct(ResourceNameCollectionFactoryInterface $resourceNameCollection, PropertyMetadataFactoryInterface $decorated = null)
    {
        $this->resourceNameCollection = $resourceNameCollection;
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass, string $property, array $options = []): PropertyMetadata
    {
        $propertyMetadata = $this->decorated->create($resourceClass, $property, $options);

        foreach ($this->resourceNameCollection->create() as $knownResourceClass) {
            if ($resourceClass === $knownResourceClass) {
                continue;
            }

            if (is_subclass_of($knownResourceClass, $resourceClass)) {
                $propertyMetadata = $this->create($knownResourceClass, $property, $options);

                return $propertyMetadata->withChildInherited($knownResourceClass);
            }
        }

        return $propertyMetadata;
    }
}
