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

namespace ApiPlatform\Core\Metadata\Resource\Factory;

use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\Metadata\Extractor\ExtractorInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;

/**
 * Creates resource's metadata using an extractor.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class ExtractorResourceMetadataFactory implements ResourceMetadataFactoryInterface
{
    private $extractor;
    private $decorated;

    public function __construct(ExtractorInterface $extractor, ResourceMetadataFactoryInterface $decorated = null)
    {
        $this->extractor = $extractor;
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass): ResourceMetadata
    {
        $parentResourceMetadata = null;
        if ($this->decorated) {
            try {
                $parentResourceMetadata = $this->decorated->create($resourceClass);
            } catch (ResourceClassNotFoundException $resourceNotFoundException) {
                // Ignore not found exception from decorated factories
            }
        }

        if (!(class_exists($resourceClass) || interface_exists($resourceClass)) || !$resource = $this->extractor->getResources()[$resourceClass] ?? false) {
            return $this->handleNotFound($parentResourceMetadata, $resourceClass);
        }

        return $this->update($parentResourceMetadata ?: new ResourceMetadata(), $resource);
    }

    /**
     * Returns the metadata from the decorated factory if available or throws an exception.
     *
     * @throws ResourceClassNotFoundException
     */
    private function handleNotFound(?ResourceMetadata $parentPropertyMetadata, string $resourceClass): ResourceMetadata
    {
        if (null !== $parentPropertyMetadata) {
            return $parentPropertyMetadata;
        }

        throw new ResourceClassNotFoundException(sprintf('Resource "%s" not found.', $resourceClass));
    }

    /**
     * Update resource metadata if new config is given.
     */
    private function update(ResourceMetadata $resourceMetadata, array $metadata): ResourceMetadata
    {
        $attributes = isset($metadata['attributes']) ? $metadata['attributes'] : [];

        foreach (['shortName', 'description', 'iri', 'itemOperations', 'collectionOperations', 'subresourceOperations', 'graphql', 'attributes'] as $property) {
            $propertyMetadata = $metadata[$property];
            $parentPropertyMetadata = $resourceMetadata->{'get'.ucfirst($property)}();

            if (null === $propertyMetadata) {
                continue;
            }

            // If metadata already defined before, consider merge new and old configs.
            if (null !== $parentPropertyMetadata) {
                // "merge" attribute must set to true to allow merging.
                if (!isset($attributes['merge']) || $attributes['merge'] === false) {
                    continue;
                }

                if (\is_array($parentPropertyMetadata) && \is_array($propertyMetadata)) {
                    $propertyMetadata = array_merge($parentPropertyMetadata, $propertyMetadata);
                }
            }

            $resourceMetadata = $resourceMetadata->{'with'.ucfirst($property)}($propertyMetadata);
        }

        return $resourceMetadata;
    }
}
