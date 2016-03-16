<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Bridge\Doctrine\MongoDB;

use ApiPlatform\Core\Api\PaginatorInterface;
use Doctrine\ODM\MongoDB\Cursor;

/**
 * Decorates the Doctrine ORM paginator.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class Paginator implements \IteratorAggregate, PaginatorInterface
{
    /**
     * @var Cursor
     */
    private $cursor;
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

    public function __construct(Cursor $cursor)
    {
        $this->cursor = $cursor;

        $info = $cursor->info();

        $this->firstResult = $info['skip'];
        $this->maxResults = $info['limit'];
        $this->totalItems = $cursor->count();
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
        return new \ArrayIterator($this->cursor->toArray());
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->getIterator());
    }
}
