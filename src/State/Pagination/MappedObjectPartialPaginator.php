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

final class MappedObjectPartialPaginator implements \IteratorAggregate, PartialPaginatorInterface
{
    public function __construct(
        private readonly iterable $entities,
        private readonly ObjectMapperInterface $mapper,
        private readonly string $resourceClass,
        private readonly float $currentPage = 1.0,
        private readonly float $itemsPerPage = 30.0,
        private readonly int $count = 0,
    ) {
    }

    public function count(): int
    {
        return $this->count;
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
