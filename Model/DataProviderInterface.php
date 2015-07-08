<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Model;

use Dunglas\ApiBundle\Api\ResourceInterface;

/**
 * Data provider interface.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
interface DataProviderInterface
{
    /**
     * Retrieves an item.
     *
     * @param ResourceInterface $resource
     * @param int|string        $id
     * @param bool              $fetchData
     *
     * @return object|null
     */
    public function getItem(ResourceInterface $resource, $id, $fetchData = false);

    /**
     * Retrieves a collection.
     *
     * @param ResourceInterface $resource
     *
     * @return array|PaginatorInterface|\Traversable
     */
    public function getCollection(ResourceInterface $resource);

    /**
     * Does this DataProvider supports the given resource.
     *
     * @param ResourceInterface $resource
     *
     * @return bool
     */
    public function supports(ResourceInterface $resource);
}
