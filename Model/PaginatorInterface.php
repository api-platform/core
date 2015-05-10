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

/**
 * Paginator Interface.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
interface PaginatorInterface extends \Traversable, \Countable
{
    /**
     * Gets the current page number.
     *
     * @return float
     */
    public function getCurrentPage();

    /**
     * Gets last page.
     *
     * @return float
     */
    public function getLastPage();

    /**
     * Gets the number of items by page.
     *
     * @return int
     */
    public function getItemsPerPage();

    /**
     * Gets the number of items in the whole collection.
     *
     * @return float
     */
    public function getTotalItems();
}
