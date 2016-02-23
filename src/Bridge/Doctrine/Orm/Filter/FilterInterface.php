<?php

/*
 * This file is part of the API Platform Builder package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Bridge\Doctrine\Orm\Filter;

use ApiPlatform\Core\Api\FilterInterface as BaseFilterInterface;
use Doctrine\ORM\QueryBuilder;

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
     * @param QueryBuilder $queryBuilder
     * @param string       $resourceClass
     * @param string|null  $operationName
     */
    public function apply(QueryBuilder $queryBuilder, string $resourceClass, string $operationName = null);
}
