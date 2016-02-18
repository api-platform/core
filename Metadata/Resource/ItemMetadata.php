<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Metadata\Resource;

/**
 * Resource item metadata.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ItemMetadata implements ItemMetadataInterface
{
    private $shortName;
    private $description;
    private $iri;
    private $itemOperations;
    private $collectionOperations;
    private $attributes;

    public function __construct(string $shortName = null, string $description = null, string $iri = null, array $itemOperations = null, array $collectionOperations = null, array $attributes = [])
    {
        $this->shortName = $shortName;
        $this->description = $description;
        $this->iri = $iri;
        $this->itemOperations = $itemOperations;
        $this->collectionOperations = $collectionOperations;
        $this->attributes = $attributes;
    }

    /**
     * {@inheritdoc}
     */
    public function getShortName()
    {
        return $this->shortName;
    }

    /**
     * {@inheritdoc}
     */
    public function withShortName(string $shortName) : ItemMetadataInterface
    {
        $metadata = clone $this;
        $metadata->shortName = $shortName;

        return $metadata;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * {@inheritdoc}
     */
    public function withDescription(string $description) : ItemMetadataInterface
    {
        $metadata = clone $this;
        $metadata->description = $description;

        return $metadata;
    }

    /**
     * {@inheritdoc}
     */
    public function getIri()
    {
        return $this->iri;
    }

    /**
     * {@inheritdoc}
     */
    public function withIri(string $iri) : ItemMetadataInterface
    {
        $metadata = clone $this;
        $metadata->iri = $iri;

        return $metadata;
    }

    /**
     * {@inheritdoc}
     */
    public function getItemOperations()
    {
        return $this->itemOperations;
    }

    /**
     * {@inheritdoc}
     */
    public function withItemOperations(array $itemOperations) : ItemMetadataInterface
    {
        $metadata = clone $this;
        $metadata->itemOperations = $itemOperations;

        return $metadata;
    }

    /**
     * {@inheritdoc}
     */
    public function getCollectionOperations()
    {
        return $this->collectionOperations;
    }

    /**
     * {@inheritdoc}
     */
    public function withCollectionOperations(array $collectionOperations) : ItemMetadataInterface
    {
        $metadata = clone $this;
        $metadata->collectionOperations = $collectionOperations;

        return $metadata;
    }

    /**
     * {@inheritdoc}
     */
    public function getCollectionOperationAttribute(string $operationName, string $key, $defaultValue = null, bool $resourceFallback = false)
    {
        return $this->getOperationAttribute($this->collectionOperations, $operationName, $key, $defaultValue, $resourceFallback);
    }

    /**
     * {@inheritdoc}
     */
    public function getItemOperationAttribute(string $operationName, string $key, $defaultValue = null, bool $resourceFallback = false)
    {
        return $this->getOperationAttribute($this->itemOperations, $operationName, $key, $defaultValue, $resourceFallback);
    }

    /**
     * Gets an operation attribute, optionally fallback to a resource attribute.
     *
     * @param array  $operations
     * @param string $operationName
     * @param string $key
     * @param mixed  $defaultValue
     * @param bool   $resourceFallback
     *
     * @return mixed
     */
    private function getOperationAttribute(array $operations, string $operationName, string $key, $defaultValue = null, bool $resourceFallback = false)
    {
        if (isset($operations[$operationName][$key])) {
            return $operations[$operationName][$key];
        }

        if ($resourceFallback && isset($this->attributes[$key])) {
            return $this->attributes[$key];
        }

        return $defaultValue;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributes() : array
    {
        return $this->attributes;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttribute(string $key, $defaultValue = null)
    {
        if (isset($this->attributes[$key])) {
            return $this->attributes[$key];
        }

        return $defaultValue;
    }

    /**
     * {@inheritdoc}
     */
    public function withAttributes(array $attributes) : ItemMetadataInterface
    {
        $metadata = clone $this;
        $metadata->attributes = $attributes;

        return $metadata;
    }
}
