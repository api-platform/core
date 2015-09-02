<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Bridge\Doctrine\Orm;

use Doctrine\ORM\Tools\Pagination\Paginator as DoctrineOrmPaginator;
use Doctrine\ORM\Query;
use Dunglas\ApiBundle\Api\PaginatorInterface;

/**
 * Decorates the Doctrine ORM paginator.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class Paginator implements \IteratorAggregate, PaginatorInterface
{
    /**
     * @var DoctrineOrmPaginator
     */
    private $paginator;

    /**
     * @var Query
     */
    private $query;

    /**
     * @var int
     */
    private $firstResult;

    /**
     * @var int
     */
    private $maxResults;

    /**
     * @var int
     */
    private $totalItems;

    /**
     * @var \Traversable
     */
    private $iterator;

    public function __construct(DoctrineOrmPaginator $paginator)
    {
        $this->paginator = $paginator;
        $this->query = $paginator->getQuery();
        $this->firstResult = $this->query->getFirstResult();
        $this->maxResults = $this->query->getMaxResults();
        $this->totalItems = count($paginator);
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentPage() : float
    {
        return floor($this->firstResult / $this->maxResults) + 1.;
    }

    /**
     * {@inheritdoc}
     */
    public function getLastPage() : float
    {
        return ceil($this->totalItems / $this->maxResults) ?: 1.;
    }

    /**
     * {@inheritdoc}
     */
    public function getItemsPerPage() : float
    {
        return (float) $this->maxResults;
    }

    /**
     * {@inheritdoc}
     */
    public function getTotalItems() : float
    {
        return (float) $this->totalItems;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        if (null === $this->iterator) {
            $this->iterator = $this->paginator->getIterator();
        }

        return $this->iterator;
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->getIterator());
    }
}
