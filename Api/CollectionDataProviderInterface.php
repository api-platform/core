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
use Dunglas\ApiBundle\Exception\ResourceClassNotSupportedException;

/**
 * Retrieves items from a persistence layer.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
interface CollectionDataProviderInterface
{
    /**
     * Retrieves a collection.
     *
     * @param string      $resourceClass
     * @param string|null $operationName
     *
     * @throws ResourceClassNotSupportedException
     * @throws InvalidArgumentException
     *
     * @return array|PaginatorInterface|\Traversable
     */
    public function getCollection(string $resourceClass, string $operationName = null);
}
