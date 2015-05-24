<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Doctrine\Orm;

use Doctrine\ORM\Tools\Pagination\Paginator as DoctrineOrmPaginator;
use Dunglas\ApiBundle\Model\PaginatorInterface;

/**
 * Decorates the Doctrine ORM paginator.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class Paginator implements \IteratorAggregate, PaginatorInterface
{
    /**
     * @var DoctrineOrmPaginator
     */
    private $paginator;
    /**
     * @var \Doctrine\ORM\Query
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
    public function getCurrentPage()
    {
        return floor($this->firstResult / $this->maxResults) + 1.;
    }

    /**
     * {@inheritdoc}
     */
    public function getLastPage()
    {
        return ceil($this->totalItems / $this->maxResults) ?: 1.;
    }

    /**
     * {@inheritdoc}
     */
    public function getItemsPerPage()
    {
        return (float) $this->maxResults;
    }

    /**
     * {@inheritdoc}
     */
    public function getTotalItems()
    {
        return (float) $this->totalItems;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return $this->paginator->getIterator();
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->getIterator());
    }
}
