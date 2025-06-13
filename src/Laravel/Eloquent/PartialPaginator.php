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

use ApiPlatform\State\Pagination\PartialPaginatorInterface;
use Illuminate\Pagination\AbstractPaginator;
use IteratorAggregate;

/**
 * @implements IteratorAggregate<mixed,object>
 * @implements PartialPaginatorInterface<object>
 */
final class PartialPaginator implements PartialPaginatorInterface, \IteratorAggregate
{
    /**
     * @param AbstractPaginator<int, object> $paginator
     */
    public function __construct(
        private readonly AbstractPaginator $paginator,
    ) {
    }

    public function count(): int
    {
        return $this->paginator->count(); // @phpstan-ignore-line
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
}
