<?php

/*
 * This file is part of the DunglasJsonLdApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\JsonLdApiBundle\Model;

use Dunglas\JsonLdApiBundle\JsonLd\ResourceInterface;

/**
 * Data provider interface.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
interface DataProviderInterface
{
    /**
     * Initializes resource.
     *
     * @param ResourceInterface $resource
     */
    public function initResource(ResourceInterface $resource);

    /**
     * Retrieves an item.
     *
     * @param int  $id
     * @param bool $fetchData
     *
     * @return object
     */
    public function getItem($id, $fetchData = false);

    /**
     * Retrieves a collection.
     *
     * @param int         $page
     * @param array       $filters
     * @param int|null    $itemsPerPage
     * @param array|null  $order
     *
     * @return PaginatorInterface
     */
    public function getCollection($page, array $filters, $itemsPerPage = null, array $order = null);
}
