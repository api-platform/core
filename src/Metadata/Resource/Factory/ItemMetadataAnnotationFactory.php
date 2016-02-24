<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Metadata\Resource\Factory;

use ApiPlatform\Core\Annotation\Resource;
use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\Metadata\Resource\ItemMetadata;
use ApiPlatform\Core\Metadata\Resource\Operation;
use ApiPlatform\Core\Metadata\Resource\PaginationMetadata;
use Doctrine\Common\Annotations\Reader;

/**
 * Parses Resource annotation and create an item metadata.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ItemMetadataAnnotationFactory implements ItemMetadataFactoryInterface
{
    private $reader;
    private $decorated;

    public function __construct(Reader $reader, ItemMetadataFactoryInterface $decorated = null)
    {
        $this->reader = $reader;
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass) : ItemMetadata
    {
        $parentItemMetadata = null;
        if ($this->decorated) {
            try {
                $parentItemMetadata = $this->decorated->create($resourceClass);
            } catch (ResourceClassNotFoundException $resourceNotFoundException) {
                // Ignore not found exception from decorated factories
            }
        }

        try {
            $reflectionClass = new \ReflectionClass($resourceClass);
        } catch (\ReflectionException $reflectionException) {
            return $this->handleNotFound($parentItemMetadata, $resourceClass);
        }

        $resourceAnnotation = $this->reader->getClassAnnotation($reflectionClass, Resource::class);
        if (null === $resourceAnnotation) {
            return $this->handleNotFound($parentItemMetadata, $resourceClass);
        }

        return $this->createMetadata($resourceAnnotation, $parentItemMetadata);
    }

    /**
     * Returns the metadata from the decorated factory if available or throws an exception.
     *
     * @param ItemMetadata|null $parentMetadata
     * @param string            $resourceClass
     *
     * @return ItemMetadata
     *
     * @throws ResourceClassNotFoundException
     */
    private function handleNotFound(ItemMetadata $parentMetadata = null, string $resourceClass) : ItemMetadata
    {
        if (null !== $parentMetadata) {
            return $parentMetadata;
        }

        throw new ResourceClassNotFoundException(sprintf('Resource "%s" not found.', $resourceClass));
    }

    private function createMetadata(Resource $annotation, ItemMetadata $parentItemMetadata = null) : ItemMetadata
    {
        if (!$parentItemMetadata) {
            return new ItemMetadata(
                $annotation->shortName,
                $annotation->description,
                $annotation->iri,
                $annotation->itemOperations,
                $annotation->collectionOperations,
                $annotation->attributes
            );
        }

        $itemMetadata = $parentItemMetadata;
        foreach (['shortName', 'description', 'iri', 'itemOperations', 'collectionOperations', 'attributes'] as $property) {
            $itemMetadata = $this->createWith($itemMetadata, $property, $annotation->$property);
        }

        $itemMetadata = $this->createWith($itemMetadata, 'collectionOperations', $this->createOperations($annotation->collectionOperations));
        $itemMetadata = $this->createWith($itemMetadata, 'itemOperations', $this->createOperations($annotation->itemOperations));

        return $itemMetadata;
    }

    /**
     * Creates operation and pagination metadata from annotations.
     *
     * @param array|null $operationAnnotations
     *
     * @return array|null
     */
    private function createOperations(array $operationAnnotations = null)
    {
        if (null === $operationAnnotations) {
            return;
        }

        $operations = [];
        foreach ($operationAnnotations as $operationName => $operationAnnotation) {
            if ($paginationAnnotation = $operationAnnotation->pagination) {
                $paginationMetadata = new PaginationMetadata(
                    $paginationAnnotation->enabled,
                    (float) $paginationAnnotation->itemsPerPage,
                    $paginationAnnotation->clientControlEnabled
                );
            } else {
                $paginationMetadata = null;
            }

            $operation = new Operation(
                $operationAnnotation->filters,
                $paginationMetadata,
                $operationAnnotation->attributes
            );

            $operations[$operationName] = $operation;
        }

        return $operations;
    }

    /**
     * Creates a new instance of metadata if the property is not already set.
     *
     * @param ItemMetadata $metadata
     * @param string       $property
     * @param mixed        $value
     *
     * @return ItemMetadata
     */
    private function createWith(ItemMetadata $metadata, string $property, $value) : ItemMetadata
    {
        $ucfirstedProperty = ucfirst($property);
        $getter = 'get'.$ucfirstedProperty;

        if (null !== $metadata->$getter()) {
            return $metadata;
        }

        $wither = 'with'.$ucfirstedProperty;

        return $metadata->$wither($value);
    }
}
