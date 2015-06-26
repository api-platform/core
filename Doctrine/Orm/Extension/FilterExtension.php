<?php

namespace Dunglas\ApiBundle\Doctrine\Orm\Extension;

use Doctrine\ORM\QueryBuilder;
use Dunglas\ApiBundle\Api\ResourceInterface;
use Dunglas\ApiBundle\Doctrine\Orm\Filter\FilterInterface;
use Dunglas\ApiBundle\Doctrine\Orm\QueryExtension;
use Symfony\Component\HttpFoundation\Request;

class FilterExtension implements QueryExtension
{
    /**
     * {@inheritdoc}
     */
    public function apply(ResourceInterface $resource, Request $request, QueryBuilder $queryBuilder)
    {
        foreach ($resource->getFilters() as $filter) {
            if ($filter instanceof FilterInterface) {
                $filter->apply($resource, $queryBuilder, $request);
            }
        }
    }
}
