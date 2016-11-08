<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Metadata\Property\Factory;

use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Exception\PropertyNotFoundException;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;

/**
 * Common base class to load properties's metadata from files.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @internal
 */
abstract class AbstractFilePropertyMetadataFactory implements PropertyMetadataFactoryInterface
{
    protected $decorated;

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

        if (
            !property_exists($resourceClass, $property) ||
            empty($propertyMetadata = $this->getMetadata($resourceClass, $property))
        ) {
            return $this->handleNotFound($parentPropertyMetadata, $resourceClass, $property);
        }

        if ($parentPropertyMetadata) {
            return $this->update($parentPropertyMetadata, $propertyMetadata);
        }

        return new PropertyMetadata(
            null,
            $propertyMetadata['description'],
            $propertyMetadata['readable'],
            $propertyMetadata['writable'],
            $propertyMetadata['readableLink'],
            $propertyMetadata['writableLink'],
            $propertyMetadata['required'],
            $propertyMetadata['identifier'],
            $propertyMetadata['iri'],
            null,
            $propertyMetadata['attributes']
        );
    }

    /**
     * Extracts metadata.
     *
     * @param string $resourceClass
     * @param string $propertyName
     *
     * @throws InvalidArgumentException
     *
     * @return array
     */
    abstract protected function getMetadata(string $resourceClass, string $propertyName): array;

    /**
     * Returns the metadata from the decorated factory if available or throws an exception.
     *
     * @param PropertyMetadata|null $parentPropertyMetadata
     * @param string                $resourceClass
     * @param string                $property
     *
     * @throws PropertyNotFoundException
     *
     * @return PropertyMetadata
     */
    private function handleNotFound(PropertyMetadata $parentPropertyMetadata = null, string $resourceClass, string $property): PropertyMetadata
    {
        if ($parentPropertyMetadata) {
            return $parentPropertyMetadata;
        }

        throw new PropertyNotFoundException(sprintf('Property "%s" of the resource class "%s" not found.', $property, $resourceClass));
    }

    /**
     * Creates a new instance of metadata if the property is not already set.
     *
     * @param PropertyMetadata $propertyMetadata
     * @param array            $metadata
     *
     * @return PropertyMetadata
     */
    private function update(PropertyMetadata $propertyMetadata, array $metadata): PropertyMetadata
    {
        $metadataAccessors = [
            'description' => 'get',
            'readable' => 'is',
            'writable' => 'is',
            'writableLink' => 'is',
            'readableLink' => 'is',
            'required' => 'is',
            'identifier' => 'is',
            'iri' => 'get',
            'attributes' => 'get',
        ];

        foreach ($metadataAccessors as $metadataKey => $accessorPrefix) {
            if (null === $metadata[$metadataKey] || null !== $propertyMetadata->{$accessorPrefix.ucfirst($metadataKey)}()) {
                continue;
            }

            $propertyMetadata = $propertyMetadata->{'with'.ucfirst($metadataKey)}($metadata[$metadataKey]);
        }

        return $propertyMetadata;
    }
}
