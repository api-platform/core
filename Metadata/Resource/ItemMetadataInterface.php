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
 * @author Samuel Roze <samuel.roze@gmail.com>
 */
interface ItemMetadataInterface
{
    /**
     * Gets the short name.
     *
     * @return string|null
     */
    public function getShortName();

    /**
     * Returns a new instance with the given short name.
     *
     * @param string $shortName
     *
     * @return self
     */
    public function withShortName(string $shortName) : self;

    /**
     * Gets the description.
     *
     * @return string|null
     */
    public function getDescription();

    /**
     * Returns a new instance with the given description.
     *
     * @param string $description
     *
     * @return self
     */
    public function withDescription(string $description) : self;

    /**
     * Gets the associated IRI.
     *
     * @return string|null
     */
    public function getIri();

    /**
     * Returns a new instance with the given IRI.
     *
     * @param string $iri
     *
     * @return self
     */
    public function withIri(string $iri) : self;

    /**
     * Gets item operations.
     *
     * @return array|null
     */
    public function getItemOperations();

    /**
     * Returns a new instance with the given item operations.
     *
     * @param array $itemOperations
     *
     * @return self
     */
    public function withItemOperations(array $itemOperations) : self;

    /**
     * Gets collection operations.
     *
     * @return array|null
     */
    public function getCollectionOperations();

    /**
     * Returns a new instance with the given collection operations.
     *
     * @param array $collectionOperations
     *
     * @return self
     */
    public function withCollectionOperations(array $collectionOperations) : self;

    /**
     * Gets a collection operation attribute, optionally fallback to a resource attribute.
     *
     * @param string $operationName
     * @param string $key
     * @param mixed  $defaultValue
     * @param bool   $resourceFallback
     *
     * @return mixed
     */
    public function getCollectionOperationAttribute(string $operationName, string $key, $defaultValue = null, bool $resourceFallback = false);

    /**
     * Gets an item operation attribute, optionally fallback to a resource attribute.
     *
     * @param string $operationName
     * @param string $key
     * @param mixed  $defaultValue
     * @param bool   $resourceFallback
     *
     * @return mixed
     */
    public function getItemOperationAttribute(string $operationName, string $key, $defaultValue = null, bool $resourceFallback = false);

    /**
     * Gets attributes.
     *
     * @return array
     */
    public function getAttributes() : array;

    /**
     * Gets an attribute.
     *
     * @param string $key
     * @param mixed  $defaultValue
     *
     * @return mixed
     */
    public function getAttribute(string $key, $defaultValue = null);

    /**
     * Returns a new instance with the given attribute.
     *
     * @param array $attributes
     *
     * @return self
     */
    public function withAttributes(array $attributes) : self;
}
