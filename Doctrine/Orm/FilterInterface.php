<?php

/*
 * This file is part of the DunglasJsonLdApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\JsonLdApiBundle\Doctrine\Orm;

use Doctrine\ORM\QueryBuilder;
use Dunglas\JsonLdApiBundle\Api\Filter\FilterInterface as BaseFilterInterface;
use Dunglas\JsonLdApiBundle\Api\ResourceInterface;

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
     * @param string            $value
     */
    public function apply(ResourceInterface $resource, QueryBuilder $queryBuilder, $value);
}
