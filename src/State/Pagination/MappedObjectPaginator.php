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

use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final class MappedObjectPaginator implements \IteratorAggregate, PaginatorInterface
{
    public function __construct(
        private readonly iterable $entities,
        private readonly ObjectMapperInterface $mapper,
        private readonly string $resourceClass,
        private readonly float $totalItems = 0.0,
        private readonly float $currentPage = 1.0,
        private readonly float $lastPage = 1.0,
        private readonly float $itemsPerPage = 30.0,
    ) {
    }

    public function count(): int
    {
        return (int) $this->totalItems;
    }

    public function getLastPage(): float
    {
        return $this->lastPage;
    }

    public function getTotalItems(): float
    {
        return $this->totalItems;
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
        foreach ($this->entities as $entity) {
            yield $this->mapper->map($entity, $this->resourceClass);
        }
    }
}
