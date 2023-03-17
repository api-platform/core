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
 * @template T of object
 *
 * @implements PartialPaginatorInterface<T>
 * @implements \IteratorAggregate<T>
 */
final class TraversablePartialPaginator implements \IteratorAggregate, PartialPaginatorInterface
{
    /**
     * @param \Traversable<T> $traversable
     */
    public function __construct(private readonly \Traversable $traversable, private readonly float $currentPage, private readonly float $itemsPerPage)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentPage(): float
    {
        return $this->currentPage;
    }

    /**
     * {@inheritdoc}
     */
    public function getItemsPerPage(): float
    {
        return $this->itemsPerPage;
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return iterator_count($this->getIterator());
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): \Traversable
    {
        return $this->traversable;
    }
}
