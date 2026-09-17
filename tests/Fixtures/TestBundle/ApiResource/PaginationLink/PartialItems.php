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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\PaginationLink;

use ApiPlatform\State\Pagination\PartialPaginatorInterface;

/**
 * @implements PartialPaginatorInterface<PaginationLinkResource>
 */
final class PartialItems implements \IteratorAggregate, PartialPaginatorInterface
{
    /**
     * @param list<PaginationLinkResource> $items
     */
    public function __construct(
        private readonly array $items,
        private readonly float $currentPage,
        private readonly float $itemsPerPage,
    ) {
    }

    public function count(): int
    {
        return \count($this->items);
    }

    public function getCurrentPage(): float
    {
        return $this->currentPage;
    }

    public function getItemsPerPage(): float
    {
        return $this->itemsPerPage;
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->items);
    }
}
