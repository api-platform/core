<?php

namespace Dunglas\ApiBundle\Doctrine\Orm;

use Doctrine\ORM\QueryBuilder;
use Dunglas\ApiBundle\Api\ResourceInterface;
use Symfony\Component\HttpFoundation\Request;

interface QueryExtension
{
    /**
     * @param ResourceInterface $resource
     * @param Request           $request
     * @param QueryBuilder      $queryBuilder
     */
    public function apply(ResourceInterface $resource, Request $request, QueryBuilder $queryBuilder);
}
