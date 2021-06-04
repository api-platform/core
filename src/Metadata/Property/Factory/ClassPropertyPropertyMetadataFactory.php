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

use ApiPlatform\Core\Exception\PropertyNotFoundException;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;

/**
 * Creates a property metadata from apiProperties property.
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class ClassPropertyPropertyMetadataFactory implements PropertyMetadataFactoryInterface
{
    private $decorated;

    public function __construct(PropertyMetadataFactoryInterface $decorated = null)
    {
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass, string $property, array $options = []): PropertyMetadata
    {
        $parentPropertyMetadata = null;
        if ($this->decorated) {
            try {
                $parentPropertyMetadata = $this->decorated->create($resourceClass, $property, $options);
            } catch (PropertyNotFoundException $propertyNotFoundException) {
                // Ignore not found exception from decorated factories
            }
        }

        $properties = (new \ReflectionClass($resourceClass))->getDefaultProperties()['apiProperties'] ?? null;

        if (null === ($properties[$property] ?? null) || !\is_array($properties[$property])) {
            return $this->handleNotFound($parentPropertyMetadata, $resourceClass, $property);
        }

        $propertyMetadata = $properties[$property];

        $metadata = $parentPropertyMetadata;
        if (null === $metadata) {
            $metadata = new PropertyMetadata();
        }

        if (isset($propertyMetadata['identifier'])) {
            $metadata = $metadata->withIdentifier((bool) $propertyMetadata['identifier']);
        }

        return $metadata;
    }

    /**
     * Returns the metadata from the decorated factory if available or throws an exception.
     *
     * @throws PropertyNotFoundException
     */
    private function handleNotFound(?PropertyMetadata $parentPropertyMetadata, string $resourceClass, string $property): PropertyMetadata
    {
        if ($parentPropertyMetadata) {
            return $parentPropertyMetadata;
        }

        throw new PropertyNotFoundException(sprintf('Property "%s" of the resource class "%s" not found.', $property, $resourceClass));
    }
}
