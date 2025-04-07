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

namespace ApiPlatform\Laravel\Eloquent;

use ApiPlatform\State\Pagination\HasNextPagePaginatorInterface;
use ApiPlatform\State\Pagination\PaginatorInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use IteratorAggregate;

/**
 * @implements IteratorAggregate<mixed,object>
 * @implements PaginatorInterface<object>
 */
final class Paginator implements PaginatorInterface, HasNextPagePaginatorInterface, \IteratorAggregate
{
    /**
     * @param LengthAwarePaginator<int, object> $paginator
     */
    public function __construct(
        private readonly LengthAwarePaginator $paginator,
    ) {
    }

    public function count(): int
    {
        return $this->paginator->count(); // @phpstan-ignore-line
    }

    public function getLastPage(): float
    {
        return $this->paginator->lastPage();
    }

    public function getTotalItems(): float
    {
        return $this->paginator->total();
    }

    public function getCurrentPage(): float
    {
        return $this->paginator->currentPage();
    }

    public function getItemsPerPage(): float
    {
        return $this->paginator->perPage();
    }

    public function getIterator(): \Traversable
    {
        return $this->paginator->getIterator();
    }

    public function hasNextPage(): bool
    {
        return $this->paginator->hasMorePages();
    }
}
