<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Api;

use Dunglas\ApiBundle\Exception\InvalidArgumentException;

/**
 * A collection of {@see ResourceInterface} classes.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
interface ResourceCollectionInterface extends \Traversable
{
    /**
     * Initializes the {@see ResourceInterface} collection.
     *
     * @param ResourceInterface[] $resources
     *
     * @throws InvalidArgumentException
     */
    public function init(array $resources);

    /**
     * Gets the {@see ResourceInterface} instance associated with the given entity class or null if not found.
     *
     * @param string|object $entityClass
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
}
