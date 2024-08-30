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
 * Handles property defined with the {@see IsApiResource} concern.
 *
 * @author Kévin Dunglas <kevin@dunglas.dev>
 */
final class ConcernsPropertyNameCollectionMetadataFactory implements PropertyNameCollectionFactoryInterface
{
    public function __construct(
        private readonly ?PropertyNameCollectionFactoryInterface $decorated = null,
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
        if (!method_exists($resourceClass, 'apiResource')) {
            return $propertyNameCollection ?? new PropertyNameCollection();
        }

        $refl = new \ReflectionClass($resourceClass);
        $method = $refl->getMethod('apiResource');
        if (!$method->isPublic() || !$method->isStatic()) {
            return $propertyNameCollection ?? new PropertyNameCollection();
        }

        $metadataCollection = $method->invoke(null);
        if (!\is_array($metadataCollection)) {
            $metadataCollection = [$metadataCollection];
        }

        $properties = $propertyNameCollection ? array_flip(iterator_to_array($propertyNameCollection)) : [];

        foreach ($metadataCollection as $apiProperty) {
            if (!$apiProperty instanceof ApiProperty) {
                continue;
            }

            if (null !== $propertyName = $apiProperty->getProperty()) {
                $properties[$propertyName] = true;
            }
        }

        return new PropertyNameCollection(array_keys($properties));
    }
}
