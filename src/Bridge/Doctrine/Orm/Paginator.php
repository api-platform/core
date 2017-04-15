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

namespace ApiPlatform\Core\Bridge\Doctrine\Orm;

use ApiPlatform\Core\DataProvider\PaginatorInterface;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrineOrmPaginator;

/**
 * Decorates the Doctrine ORM paginator.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class Paginator implements \IteratorAggregate, PaginatorInterface
{
    private $paginator;

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
        $query = $paginator->getQuery();
        $this->firstResult = $query->getFirstResult();
        $this->maxResults = $query->getMaxResults();
        $this->totalItems = count($paginator);
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentPage(): float
    {
        return floor($this->firstResult / $this->maxResults) + 1.;
    }

    /**
     * {@inheritdoc}
     */
    public function getLastPage(): float
    {
        return ceil($this->totalItems / $this->maxResults) ?: 1.;
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
    public function getTotalItems(): float
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
