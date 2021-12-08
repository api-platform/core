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

use ApiPlatform\Exception\InvalidArgumentException;
use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;

final class CursorPaginator extends AbstractPaginator
{
    protected $shouldFetchJoin;
    protected $query;

    public function __construct(DoctrinePaginator $paginator)
    {
        try {
            parent::__construct($paginator);
        } catch (InvalidArgumentException $argumentException) {
        }
        $query = $paginator->getQuery();

        if (null === $maxResults = $query->getMaxResults()) {
            throw new InvalidArgumentException(sprintf('"%1$s::setMaxResults()" was not applied to the query.', Query::class));
        }
        $this->shouldFetchJoin = $paginator->getFetchJoinCollection();
        $this->maxResults = $maxResults;
        $this->query = $query;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->query->getResult());
    }

    public function getCurrentPage(): float
    {
        return 0.;
    }
}
