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

use Dunglas\JsonLdApiBundle\JsonLd\Resource;

/**
 * Manipulates data.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
interface ManagerInterface
{
    /**
     * Sets resource.
     *
     * @param Resource $resource
     */
    public function setResource(Resource $resource);

    /**
     * Retrieves an item.
     *
     * @param int $id
     *
     * @return object
     */
    public function getItem($id);

    /**
     * Retrieves a collection.
     *
     * @param int         $page
     * @param array       $filters
     * @param int|null    $byPage
     * @param string|null $order
     *
     * @return PaginatorInterface
     */
    public function getCollection($page, array $filters, $itemsPerPage = null, $order = null);
}
