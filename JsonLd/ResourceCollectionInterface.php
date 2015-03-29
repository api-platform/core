<?php

/*
 * This file is part of the DunglasJsonLdApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\JsonLdApiBundle\JsonLd;

/**
 * A collection of {@see ResourceInterface} classes.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
interface ResourceCollectionInterface extends \Traversable
{
    /**
     * Adds a {@see ResourceInterface} to the collection.
     *
     * @param ResourceInterface $resource
     *
     * @throws \InvalidArgumentException
     */
    public function add(ResourceInterface $resource);

    /**
     * Gets the {@see ResourceInterface} instance associated with the given entity class or null if not found.
     *
     * @param string $entityClass
     *
     * @return ResourceInterface|null
     */
    public function getResourceForEntity($entityClass);

    /**
     * Gets the {@see ResourceInterface} instance associated with the given short name or null if not found.
     *
     * @param string $shortName
     *
     * @return ResourceInterface|null
     */
    public function getResourceForShortName($shortName);

    /**
     * Gets the URI of a collection.
     *
     * @param ResourceInterface $resource
     *
     * @return string
     */
    public function getCollectionUri(ResourceInterface $resource);

    /**
     * Gets the URI of an item.
     *
     * @param object      $object
     * @param string|null $entityClass
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    public function getItemUri($object, $entityClass = null);

    /**
     * Gets an item from an URI.
     *
     * @param string $uri
     *
     * @return object
     *
     * @throws \InvalidArgumentException
     */
    public function getItemFromUri($uri);
}
