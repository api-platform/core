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

namespace ApiPlatform\Doctrine\Odm;

use ApiPlatform\State\Pagination\PartialPaginatorInterface;

abstract class AbstractPaginator implements \IteratorAggregate, PartialPaginatorInterface
{
    protected int $firstResult;
    protected int $maxResults;
    protected \ArrayIterator $iterator;
    protected int $count;

    public function __construct(array $result)
    {
        $this->firstResult = $result['__api_first_result__'];
        $this->maxResults = $result['__api_max_results__'];
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
        return $this->iterator;
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return $this->count;
    }
}
