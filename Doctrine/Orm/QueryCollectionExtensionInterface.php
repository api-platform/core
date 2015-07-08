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
use Dunglas\ApiBundle\Api\ResourceInterface;

/**
 * Interface of Doctrine ORM query extensions for collection queries.
 *
 * @author Samuel ROZE <samuel.roze@gmail.com>
 */
interface QueryCollectionExtensionInterface
{
    /**
     * @param ResourceInterface $resource
     * @param QueryBuilder      $queryBuilder
     */
    public function applyToCollection(ResourceInterface $resource, QueryBuilder $queryBuilder);
}
