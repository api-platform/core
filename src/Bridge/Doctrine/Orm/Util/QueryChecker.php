<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ApiPlatform\Core\Bridge\Doctrine\Orm\Util;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

/**
 * Utility functions for working with Doctrine ORM query.
 *
 * @author Teoh Han Hui <teohhanhui@gmail.com>
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
final class QueryChecker
{
    private function __construct()
    {
    }

    /**
     * Determines whether the query builder uses a HAVING clause.
     */
    public static function hasHavingClause(QueryBuilder $queryBuilder): bool
    {
        return !empty($queryBuilder->getDQLPart('having'));
    }

    /**
     * Determines whether the query builder has any root entity with foreign key identifier.
     */
    public static function hasRootEntityWithForeignKeyIdentifier(QueryBuilder $queryBuilder, ManagerRegistry $managerRegistry): bool
    {
        return self::hasRootEntityWithIdentifier($queryBuilder, $managerRegistry, true);
    }

    /**
     * Determines whether the query builder has any composite identifier.
     */
    public static function hasRootEntityWithCompositeIdentifier(QueryBuilder $queryBuilder, ManagerRegistry $managerRegistry): bool
    {
        return self::hasRootEntityWithIdentifier($queryBuilder, $managerRegistry, false);
    }

    /**
     * Detects if the root entity has the given identifier.
     */
    private static function hasRootEntityWithIdentifier(QueryBuilder $queryBuilder, ManagerRegistry $managerRegistry, bool $isForeign): bool
    {
        foreach ($queryBuilder->getRootEntities() as $rootEntity) {
            $rootMetadata = $managerRegistry
                ->getManagerForClass($rootEntity)
                ->getClassMetadata($rootEntity);

            if ($rootMetadata instanceof ClassMetadata && ($isForeign ? $rootMetadata->containsForeignIdentifier : $rootMetadata->isIdentifierComposite)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determines whether the query builder has the maximum number of results specified.
     */
    public static function hasMaxResults(QueryBuilder $queryBuilder): bool
    {
        return null !== $queryBuilder->getMaxResults();
    }

    /**
     * Determines whether the query builder has ORDER BY on entity joined through
     * to-many association.
     */
    public static function hasOrderByOnToManyJoin(QueryBuilder $queryBuilder, ManagerRegistry $managerRegistry): bool
    {
        if (
            empty($orderByParts = $queryBuilder->getDQLPart('orderBy')) ||
            empty($joinParts = $queryBuilder->getDQLPart('join'))
        ) {
            return false;
        }

        $orderByAliases = [];
        foreach ($orderByParts as $orderBy) {
            $parts = $orderBy->getParts();

            foreach ($parts as $part) {
                $pos = strpos($part, '.');
                if (false !== $pos) {
                    $orderByAliases[substr($part, 0, $pos)] = true;
                }
            }
        }

        if (!$orderByAliases) {
            return false;
        }

        foreach ($joinParts as $joins) {
            foreach ($joins as $join) {
                $alias = $join->getAlias();

                if (!isset($orderByAliases[$alias])) {
                    continue;
                }
                $relationship = $join->getJoin();

                if (false !== strpos($relationship, '.')) {
                    /*
                     * We select the parent alias because it may differ from the origin alias given above
                     * @see https://github.com/api-platform/core/issues/1313
                     */
                    list($relationAlias, $association) = explode('.', $relationship);
                    $metadata = QueryJoinParser::getClassMetadataFromJoinAlias($relationAlias, $queryBuilder, $managerRegistry);
                    if ($metadata->isCollectionValuedAssociation($association)) {
                        return true;
                    }
                } else {
                    $parentMetadata = $managerRegistry->getManagerForClass($relationship)->getClassMetadata($relationship);

                    foreach ($queryBuilder->getRootEntities() as $rootEntity) {
                        $rootMetadata = $managerRegistry
                            ->getManagerForClass($rootEntity)
                            ->getClassMetadata($rootEntity);

                        if (!$rootMetadata instanceof ClassMetadata) {
                            continue;
                        }

                        foreach ($rootMetadata->getAssociationsByTargetClass($relationship) as $association => $mapping) {
                            if ($parentMetadata->isCollectionValuedAssociation($association)) {
                                return true;
                            }
                        }
                    }
                }
            }
        }

        return false;
    }

    /**
     * Determines whether the query builder already has a left join.
     */
    public static function hasLeftJoin(QueryBuilder $queryBuilder): bool
    {
        foreach ($queryBuilder->getDQLPart('join') as $dqlParts) {
            foreach ($dqlParts as $dqlPart) {
                if (Join::LEFT_JOIN === $dqlPart->getJoinType()) {
                    return true;
                }
            }
        }

        return false;
    }
}
