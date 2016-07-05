<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Bridge\Doctrine\MongoDB\Extension;

use Doctrine\ODM\MongoDB\Query\Builder;

/**
 * Interface of Doctrine MongoDB ODM query extensions for collection queries.
 *
 * @author Samuel ROZE <samuel.roze@gmail.com>
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
interface QueryCollectionExtensionInterface
{
    /**
     * @param Builder $queryBuilder
     * @param string  $resourceClass
     * @param string  $operationName
     */
    public function applyToCollection(Builder $queryBuilder, string $resourceClass, string $operationName = null);
}
