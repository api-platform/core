<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Bridge\Doctrine\Orm\Extension;

use Doctrine\ORM\QueryBuilder;

/**
 * Interface of Doctrine ORM query extensions for collection queries.
 *
 * @author Samuel ROZE <samuel.roze@gmail.com>
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
interface QueryCollectionExtensionInterface
{
    /**
     * @param QueryBuilder $queryBuilder
     * @param string       $resourceClass
     * @param string       $operationName
     */
    public function applyToCollection(QueryBuilder $queryBuilder, string $resourceClass, string $operationName = null);
}
