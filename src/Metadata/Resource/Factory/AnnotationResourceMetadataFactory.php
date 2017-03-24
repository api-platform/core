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

use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use Doctrine\Common\Annotations\Reader;

/**
 * Creates a resource metadata from {@see ApiResource} annotations.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class AnnotationResourceMetadataFactory implements ResourceMetadataFactoryInterface
{
    private $reader;
    private $decorated;

    public function __construct(Reader $reader, ResourceMetadataFactoryInterface $decorated = null)
    {
        $this->reader = $reader;
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

        try {
            $reflectionClass = new \ReflectionClass($resourceClass);
        } catch (\ReflectionException $reflectionException) {
            return $this->handleNotFound($parentResourceMetadata, $resourceClass);
        }

        $resourceAnnotation = $this->reader->getClassAnnotation($reflectionClass, ApiResource::class);
        if (null === $resourceAnnotation) {
            return $this->handleNotFound($parentResourceMetadata, $resourceClass);
        }

        return $this->createMetadata($resourceAnnotation, $parentResourceMetadata);
    }

    /**
     * Returns the metadata from the decorated factory if available or throws an exception.
     *
     * @param ResourceMetadata|null $parentPropertyMetadata
     * @param string                $resourceClass
     *
     * @throws ResourceClassNotFoundException
     *
     * @return ResourceMetadata
     */
    private function handleNotFound(ResourceMetadata $parentPropertyMetadata = null, string $resourceClass): ResourceMetadata
    {
        if (null !== $parentPropertyMetadata) {
            return $parentPropertyMetadata;
        }

        throw new ResourceClassNotFoundException(sprintf('Resource "%s" not found.', $resourceClass));
    }

    private function createMetadata(ApiResource $annotation, ResourceMetadata $parentResourceMetadata = null): ResourceMetadata
    {
        if (!$parentResourceMetadata) {
            return new ResourceMetadata(
                $annotation->shortName,
                $annotation->description,
                $annotation->iri,
                $annotation->itemOperations,
                $annotation->collectionOperations,
                $annotation->attributes
            );
        }

        $resourceMetadata = $parentResourceMetadata;
        foreach (['shortName', 'description', 'iri', 'itemOperations', 'collectionOperations', 'attributes'] as $property) {
            $resourceMetadata = $this->createWith($resourceMetadata, $property, $annotation->$property);
        }

        return $resourceMetadata;
    }

    /**
     * Creates a new instance of metadata if the property is not already set.
     *
     * @param ResourceMetadata $resourceMetadata
     * @param string           $property
     * @param mixed            $value
     *
     * @return ResourceMetadata
     */
    private function createWith(ResourceMetadata $resourceMetadata, string $property, $value): ResourceMetadata
    {
        $getter = 'get'.ucfirst($property);

        if (null !== $resourceMetadata->$getter()) {
            return $resourceMetadata;
        }

        $wither = 'with'.ucfirst($property);

        return $resourceMetadata->$wither($value);
    }
}
