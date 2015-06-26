<?php

namespace Dunglas\ApiBundle\Doctrine\Orm;

use Doctrine\ORM\QueryBuilder;
use Dunglas\ApiBundle\Api\ResourceInterface;
use Symfony\Component\HttpFoundation\Request;

interface QueryResultExtension extends QueryExtension
{
    /**
     * @param ResourceInterface $resource
     * @param Request $request
     * @return bool
     */
    public function supportsResult(ResourceInterface $resource, Request $request);

    /**
     * @param QueryBuilder $queryBuilder
     *
     * @return mixed
     */
    public function getResult(QueryBuilder $queryBuilder);
}
