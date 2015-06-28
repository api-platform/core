<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Doctrine\Orm\Extension;

use Doctrine\ORM\QueryBuilder;
use Dunglas\ApiBundle\Api\ResourceInterface;
use Dunglas\ApiBundle\Doctrine\Orm\Filter\FilterInterface;
use Dunglas\ApiBundle\Doctrine\Orm\QueryExtensionInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Apply filters on a resource query.
 *
 * @author Samuel ROZE <samuel.roze@gmail.com>
 */
class FilterExtension implements QueryExtensionInterface
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
