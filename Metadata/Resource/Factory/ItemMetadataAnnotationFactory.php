<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Metadata\Resource\Factory;

use Doctrine\Common\Annotations\Reader;
use Dunglas\ApiBundle\Annotation\Resource;
use Dunglas\ApiBundle\Exception\ResourceClassNotFoundException;
use Dunglas\ApiBundle\Metadata\Resource\ItemMetadata;
use Dunglas\ApiBundle\Metadata\Resource\ItemMetadataInterface;
use Dunglas\ApiBundle\Metadata\Resource\Operation;
use Dunglas\ApiBundle\Metadata\Resource\PaginationMetadata;

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
    public function create(string $resourceClass) : ItemMetadataInterface
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
     * @param ItemMetadataInterface|null $parentMetadata
     * @param string                     $resourceClass
     *
     * @return ItemMetadataInterface
     *
     * @throws ResourceClassNotFoundException
     */
    private function handleNotFound(ItemMetadataInterface $parentMetadata = null, string $resourceClass) : ItemMetadataInterface
    {
        if (null !== $parentMetadata) {
            return $parentMetadata;
        }

        throw new ResourceClassNotFoundException(sprintf('Resource "%s" not found.', $resourceClass));
    }

    private function createMetadata(Resource $annotation, ItemMetadataInterface $parentItemMetadata = null) : ItemMetadataInterface
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
     * @param ItemMetadataInterface $metadata
     * @param string                $property
     * @param mixed                 $value
     *
     * @return ItemMetadataInterface
     */
    private function createWith(ItemMetadataInterface $metadata, string $property, $value) : ItemMetadataInterface
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
