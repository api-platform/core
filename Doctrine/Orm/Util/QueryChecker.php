<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Doctrine\Orm\Util;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

/**
 * Utility functions for working with Doctrine ORM query.
 *
 * @author Teoh Han Hui <teohhanhui@gmail.com>
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
abstract class QueryChecker
{
    /**
     * Determines whether the query builder uses a HAVING clause.
     *
     * @param QueryBuilder $queryBuilder
     *
     * @return bool
     */
    public static function hasHavingClause(QueryBuilder $queryBuilder)
    {
        return !empty($queryBuilder->getDQLPart('having'));
    }

    /**
     * Determines whether the query builder has any root entity with foreign key identifier.
     *
     * @param QueryBuilder    $queryBuilder
     * @param ManagerRegistry $managerRegistry
     *
     * @return bool
     */
    public static function hasRootEntityWithForeignKeyIdentifier(QueryBuilder $queryBuilder, ManagerRegistry $managerRegistry)
    {
        foreach ($queryBuilder->getRootEntities() as $rootEntity) {
            $rootMetadata = $managerRegistry
                ->getManagerForClass($rootEntity)
                ->getClassMetadata($rootEntity);

            if ($rootMetadata->containsForeignIdentifier) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determines whether the query builder has the maximum number of results specified.
     *
     * @param QueryBuilder $queryBuilder
     *
     * @return bool
     */
    public static function hasMaxResults(QueryBuilder $queryBuilder)
    {
        return null !== $queryBuilder->getMaxResults();
    }

    /**
     * Determines whether the query builder has ORDER BY on entity joined through
     * to-many association.
     *
     * @param QueryBuilder    $queryBuilder
     * @param ManagerRegistry $managerRegistry
     *
     * @return bool
     */
    public static function hasOrderByOnToManyJoin(QueryBuilder $queryBuilder, ManagerRegistry $managerRegistry)
    {
        if (
            empty($orderByParts = $queryBuilder->getDQLPart('orderBy'))
            || empty($joinParts = $queryBuilder->getDQLPart('join'))
        ) {
            return false;
        }

        $orderByAliases = [];
        foreach ($orderByParts as $orderBy) {
            $parts = QueryNameGenerator::getOrderByParts($orderBy);

            foreach ($parts as $part) {
                if (false !== ($pos = strpos($part, '.'))) {
                    $alias = substr($part, 0, $pos);

                    $orderByAliases[$alias] = true;
                }
            }
        }

        if (!empty($orderByAliases)) {
            foreach ($joinParts as $rootAlias => $joins) {
                foreach ($joins as $join) {
                    $alias = QueryNameGenerator::getJoinAlias($join);

                    if (isset($orderByAliases[$alias])) {
                        $relationship = QueryNameGenerator::getJoinRelationship($join);

                        $relationshipParts = explode('.', $relationship);
                        $parentAlias = $relationshipParts[0];
                        $association = $relationshipParts[1];

                        $parentMetadata = QueryNameGenerator::getClassMetadataFromJoinAlias($parentAlias, $queryBuilder, $managerRegistry);

                        if ($parentMetadata->isCollectionValuedAssociation($association)) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }
}
