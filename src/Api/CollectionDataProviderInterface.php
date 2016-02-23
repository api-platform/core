<?php

/*
 * This file is part of the API Platform Builder package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Api;

use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Exception\ResourceClassNotSupportedException;

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
