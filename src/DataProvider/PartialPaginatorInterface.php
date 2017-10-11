<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ApiPlatform\Core\DataProvider;

/**
 * Partial Paginator Interface.
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
interface PartialPaginatorInterface extends \Traversable, \Countable
{
    /**
     * Gets the current page number.
     *
     * @return float
     */
    public function getCurrentPage(): float;

    /**
     * Gets the number of items by page.
     *
     * @return float
     */
    public function getItemsPerPage(): float;
}
