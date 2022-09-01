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
 * The \Countable implementation should return the number of items on the
 * current page, as an integer.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @template T of object
 *
 * @extends PartialPaginatorInterface<T>
 */
interface PaginatorInterface extends PartialPaginatorInterface
{
    /**
     * Gets last page.
     */
    public function getLastPage(): float;

    /**
     * Gets the number of items in the whole collection.
     */
    public function getTotalItems(): float;
}
