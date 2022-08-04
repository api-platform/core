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

namespace ApiPlatform\State\Pagination;

/**
 * Partial Paginator Interface.
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 *
 * @template T of object
 *
 * @extends \Traversable<T>
 */
interface PartialPaginatorInterface extends \Traversable, \Countable
{
    /**
     * Gets the current page number.
     */
    public function getCurrentPage(): float;

    /**
     * Gets the number of items by page.
     */
    public function getItemsPerPage(): float;
}
