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

use ApiPlatform\Doctrine\Orm\Extension\DoctrinePaginatorFactory;
use ApiPlatform\State\Pagination\HasNextPagePaginatorInterface;
use ApiPlatform\State\Pagination\PaginatorInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;

/**
 * Decorates the Doctrine ORM paginator.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class Paginator extends AbstractPaginator implements PaginatorInterface, QueryAwareInterface, HasNextPagePaginatorInterface
{
    private ?int $totalItems = null;
    private ?DoctrinePaginatorFactory $doctrinePaginatorFactory = null;

    /**
     * {@inheritdoc}
     */
    public function getLastPage(): float
    {
        if (0 >= $this->maxResults) {
            return 1.;
        }

        return ceil($this->getTotalItems() / $this->maxResults) ?: 1.;
    }

    /**
     * {@inheritdoc}
     */
    public function getTotalItems(): float
    {
        return (float) ($this->totalItems ?? $this->totalItems = \count($this->paginator));
    }

    /**
     * {@inheritdoc}
     */
    public function getQuery(): Query
    {
        return $this->paginator->getQuery();
    }

    /**
     * {@inheritdoc}
     */
    public function hasNextPage(): bool
    {
        if (isset($this->totalItems)) {
            return $this->totalItems > ($this->firstResult + $this->maxResults);
        }

        $cloneQuery = clone $this->paginator->getQuery();

        $cloneQuery->setParameters(clone $this->paginator->getQuery()->getParameters());
        $cloneQuery->setCacheable(false);

        foreach ($this->paginator->getQuery()->getHints() as $name => $value) {
            $cloneQuery->setHint($name, $value);
        }

        $cloneQuery
            ->setFirstResult($this->paginator->getQuery()->getFirstResult() + $this->paginator->getQuery()->getMaxResults())
            ->setMaxResults(1);

        if (null !== $this->doctrinePaginatorFactory) {
            $fakePaginator = $this->doctrinePaginatorFactory->getPaginator($cloneQuery, $this->paginator->getFetchJoinCollection());
        } else {
            $fakePaginator = new DoctrinePaginator($cloneQuery, $this->paginator->getFetchJoinCollection());
        }

        return iterator_count($fakePaginator->getIterator()) > 0;
    }

    public function setDoctrinePaginatorFactory(?DoctrinePaginatorFactory $doctrinePaginatorFactory = null): void
    {
        $this->doctrinePaginatorFactory = $doctrinePaginatorFactory;
    }
}
