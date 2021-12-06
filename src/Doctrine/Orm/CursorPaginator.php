<?php

namespace ApiPlatform\Doctrine\Orm;

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
