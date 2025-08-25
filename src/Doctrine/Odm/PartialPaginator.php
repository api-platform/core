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

namespace ApiPlatform\Doctrine\Odm;

use ApiPlatform\State\Pagination\HasNextPagePaginatorInterface;
use ApiPlatform\State\Pagination\PaginatorInterface;

/**
 * A paginator for partial pagination (without total items).
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class PartialPaginator implements \IteratorAggregate, PaginatorInterface, HasNextPagePaginatorInterface
{
    private readonly \ArrayIterator $iterator;
    private readonly int $count;
    private readonly bool $hasNextPage;

    /**
     * @param \Traversable<mixed> $items        The items for the current page
     * @param float               $currentPage  The current page number
     * @param float               $itemsPerPage The number of items per page
     */
    public function __construct(\Traversable $items, private readonly float $currentPage, private readonly float $itemsPerPage)
    {
        $data = iterator_to_array($items);

        if (0. < $this->itemsPerPage && \count($data) > $this->itemsPerPage) {
            $this->hasNextPage = true;
            $this->iterator = new \ArrayIterator(\array_slice($data, 0, (int) $this->itemsPerPage));
        } else {
            $this->hasNextPage = false;
            $this->iterator = new \ArrayIterator($data);
        }

        $this->count = \count($this->iterator);
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentPage(): float
    {
        return $this->currentPage;
    }

    /**
     * In a partial pagination, we never know the last page.
     *
     * {@inheritdoc}
     */
    public function getLastPage(): float
    {
        return $this->hasNextPage ? $this->currentPage + 1 : $this->currentPage;
    }

    /**
     * {@inheritdoc}
     */
    public function getItemsPerPage(): float
    {
        return $this->itemsPerPage;
    }

    /**
     * In a partial pagination, we don't know the total number of items.
     *
     * {@inheritdoc}
     */
    public function getTotalItems(): float
    {
        return 0.0;
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return $this->count;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): \Traversable
    {
        return $this->iterator;
    }

    /**
     * {@inheritdoc}
     */
    public function hasNextPage(): bool
    {
        return $this->hasNextPage;
    }
}
