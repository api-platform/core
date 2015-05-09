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

use Doctrine\ORM\QueryBuilder;
use Dunglas\ApiBundle\Api\Filter\FilterInterface as BaseFilterInterface;
use Dunglas\ApiBundle\Api\ResourceInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Doctrine ORM filter interface.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
interface FilterInterface extends BaseFilterInterface
{
    /**
     * Applies the filter.
     *
     * @param ResourceInterface $resource
     * @param QueryBuilder      $queryBuilder
     * @param Request           $request
     */
    public function apply(ResourceInterface $resource, QueryBuilder $queryBuilder, Request $request);
}
