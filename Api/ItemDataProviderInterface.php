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

use Dunglas\ApiBundle\Exception\ResourceClassNotSupportedException;
use Dunglas\ApiBundle\Exception\InvalidArgumentException;

/**
 * Retrieves items from a persistence layer.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
interface ItemDataProviderInterface
{
    /**
     * Retrieves an item.
     *
     * @param string      $resourceClass
     * @param string|null $operationName
     * @param int|string  $id
     * @param bool        $fetchData
     *
     * @throws ResourceClassNotSupportedException
     * @throws InvalidArgumentException
     *
     * @return object|null
     */
    public function getItem(string $resourceClass, $id, string $operationName = null, bool $fetchData = false);
}
