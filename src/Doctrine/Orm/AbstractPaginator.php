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

namespace ApiPlatform\Doctrine\Orm;

use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\State\Pagination\PartialPaginatorInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;

abstract class AbstractPaginator implements \IteratorAggregate, PartialPaginatorInterface
{
    protected DoctrinePaginator $paginator;
    protected array|\Traversable $iterator;
    protected ?int $firstResult;
    protected ?int $maxResults;

    /**
     * @throws InvalidArgumentException
     */
    public function __construct(DoctrinePaginator $paginator)
    {
        $query = $paginator->getQuery();

        if (null === ($firstResult = $query->getFirstResult()) || $firstResult < 0 || null === $maxResults = $query->getMaxResults()) { // @phpstan-ignore-line
            throw new InvalidArgumentException(sprintf('"%1$s::setFirstResult()" or/and "%1$s::setMaxResults()" was/were not applied to the query.', Query::class));
        }

        $this->paginator = $paginator;
        $this->firstResult = $firstResult;
        $this->maxResults = $maxResults;
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentPage(): float
    {
        if (0 >= $this->maxResults) {
            return 1.;
        }

        return floor($this->firstResult / $this->maxResults) + 1.;
    }

    /**
     * {@inheritdoc}
     */
    public function getItemsPerPage(): float
    {
        return (float) $this->maxResults;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): \Traversable
    {
        return $this->iterator ?? $this->iterator = $this->paginator->getIterator();
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return iterator_count($this->getIterator());
    }
}
