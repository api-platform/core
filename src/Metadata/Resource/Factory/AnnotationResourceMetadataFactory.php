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
use ApiPlatform\Core\Metadata\Resource\OperationCollectionMetadata;
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
    private $defaults;

    public function __construct(Reader $reader, ResourceMetadataFactoryInterface $decorated = null, array $defaults = [])
    {
        $this->reader = $reader;
        $this->decorated = $decorated;
        $this->defaults = $defaults + ['attributes' => []];
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass): ResourceMetadata
    {
        $resourceMetadata = null;
        if ($this->decorated) {
            try {
                $resourceMetadata = $this->decorated->create($resourceClass);
            } catch (ResourceClassNotFoundException $resourceNotFoundException) {
                // Ignore not found exception from decorated factories
            }
        }

        try {
            $reflectionClass = new \ReflectionClass($resourceClass);
        } catch (\ReflectionException $reflectionException) {
            return $this->handleNotFound($resourceMetadata, $resourceClass);
        }

        if (!$resourceMetadata) {
            $resourceMetadata = new ResourceMetadata([]);
        }

        foreach ($this->reader->getClassAnnotations($reflectionClass) as $resourceAnnotation) {
            if ($resourceAnnotation instanceof ApiResource) {
                $resourceMetadata[$resourceAnnotation->path] = $this->createMetadata(
                    $resourceAnnotation,
                    $resourceMetadata[$resourceAnnotation->path] ?? null
                );
            }
        }

        // todo Does empty work with ArrayAccess and IteratorAggregate?
        if (empty($resourceMetadata)) {
            return $this->handleNotFound($resourceMetadata, $resourceClass);
        }

        return $resourceMetadata;
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

    private function createMetadata(ApiResource $annotation, OperationCollectionMetadata $operationCollectionMetadata = null): OperationCollectionMetadata
    {
        $attributes = (null === $annotation->attributes && [] === $this->defaults['attributes']) ? null : (array) $annotation->attributes + $this->defaults['attributes'];

        if (!$operationCollectionMetadata) {
            return new OperationCollectionMetadata(
                $annotation->path,
                $annotation->shortName,
                $annotation->description ?? $this->defaults['description'] ?? null,
                $annotation->iri ?? $this->defaults['iri'] ?? null,
                $annotation->itemOperations ?? $this->defaults['item_operations'] ?? null,
                $annotation->collectionOperations ?? $this->defaults['collection_operations'] ?? null,
                $attributes,
                $annotation->subresourceOperations,
                $annotation->graphql ?? $this->defaults['graphql'] ?? null
            );
        }

        foreach (['path', 'shortName', 'description', 'iri', 'itemOperations', 'collectionOperations', 'subresourceOperations', 'graphql', 'attributes'] as $property) {
            $operationCollectionMetadata = $this->createWith($operationCollectionMetadata, $property, $annotation->{$property});
        }

        return $operationCollectionMetadata;
    }

    /**
     * Creates a new instance of metadata if the property is not already set.
     */
    private function createWith(OperationCollectionMetadata $resourceMetadata, string $property, $value): OperationCollectionMetadata
    {
        $upperProperty = ucfirst($property);
        $getter = "get$upperProperty";

        if (null !== $resourceMetadata->{$getter}()) {
            return $resourceMetadata;
        }

        if (null === $value) {
            return $resourceMetadata;
        }

        $wither = "with$upperProperty";

        return $resourceMetadata->{$wither}($value);
    }
}
