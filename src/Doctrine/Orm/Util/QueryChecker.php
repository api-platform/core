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

namespace ApiPlatform\Doctrine\Orm\Util;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Utility functions for working with Doctrine ORM query.
 *
 * @author Teoh Han Hui <teohhanhui@gmail.com>
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 *
 * @internal
 */
final class QueryChecker
{
    private function __construct()
    {
    }

    /**
     * Determines whether the QueryBuilder uses a HAVING clause.
     */
    public static function hasHavingClause(QueryBuilder $queryBuilder): bool
    {
        return null !== $queryBuilder->getDQLPart('having');
    }

    /**
     * Determines whether the QueryBuilder has any root entity with foreign key identifier.
     */
    public static function hasRootEntityWithForeignKeyIdentifier(QueryBuilder $queryBuilder, ManagerRegistry $managerRegistry): bool
    {
        foreach ($queryBuilder->getRootEntities() as $rootEntity) {
            /** @var ClassMetadata $rootMetadata */
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
     * Determines whether the QueryBuilder has any composite identifier.
     */
    public static function hasRootEntityWithCompositeIdentifier(QueryBuilder $queryBuilder, ManagerRegistry $managerRegistry): bool
    {
        foreach ($queryBuilder->getRootEntities() as $rootEntity) {
            /** @var ClassMetadata $rootMetadata */
            $rootMetadata = $managerRegistry
                ->getManagerForClass($rootEntity)
                ->getClassMetadata($rootEntity);

            if ($rootMetadata->isIdentifierComposite) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determines whether the QueryBuilder has a limit on the maximum number of results.
     */
    public static function hasMaxResults(QueryBuilder $queryBuilder): bool
    {
        return null !== $queryBuilder->getMaxResults();
    }

    /**
     * Determines whether the QueryBuilder has ORDER BY on a column from a fetch joined to-many association.
     */
    public static function hasOrderByOnFetchJoinedToManyAssociation(QueryBuilder $queryBuilder, ManagerRegistry $managerRegistry): bool
    {
        if (
            0 === \count($queryBuilder->getDQLPart('join'))
            || 0 === \count($orderByParts = $queryBuilder->getDQLPart('orderBy'))
        ) {
            return false;
        }

        $rootAliases = $queryBuilder->getRootAliases();

        $orderByAliases = [];

        foreach ($orderByParts as $orderBy) {
            foreach ($orderBy->getParts() as $part) {
                if (str_contains((string) $part, '.')) {
                    [$alias] = explode('.', (string) $part);

                    $orderByAliases[] = $alias;
                }
            }
        }

        $orderByAliases = array_diff($orderByAliases, $rootAliases);
        if (0 === \count($orderByAliases)) {
            return false;
        }

        $allAliases = $queryBuilder->getAllAliases();

        foreach ($orderByAliases as $orderByAlias) {
            $inToManyContext = false;

            foreach (QueryBuilderHelper::traverseJoins($orderByAlias, $queryBuilder, $managerRegistry) as $alias => [$metadata, $association]) {
                if ($inToManyContext && \in_array($alias, $allAliases, true)) {
                    return true;
                }

                if (null !== $association && $metadata->isCollectionValuedAssociation($association)) {
                    $inToManyContext = true;
                }
            }
        }

        return false;
    }

    /**
     * Determines whether the QueryBuilder already has a left join.
     */
    public static function hasLeftJoin(QueryBuilder $queryBuilder): bool
    {
        foreach ($queryBuilder->getDQLPart('join') as $joins) {
            foreach ($joins as $join) {
                if (Join::LEFT_JOIN === $join->getJoinType()) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Determines whether the QueryBuilder has a joined to-many association.
     */
    public static function hasJoinedToManyAssociation(QueryBuilder $queryBuilder, ManagerRegistry $managerRegistry): bool
    {
        if (
            0 === (is_countable($queryBuilder->getDQLPart('join')) ? \count($queryBuilder->getDQLPart('join')) : 0)
        ) {
            return false;
        }

        $joinAliases = array_diff($queryBuilder->getAllAliases(), $queryBuilder->getRootAliases());
        if (0 === \count($joinAliases)) {
            return false;
        }

        foreach ($joinAliases as $joinAlias) {
            foreach (QueryBuilderHelper::traverseJoins($joinAlias, $queryBuilder, $managerRegistry) as $alias => [$metadata, $association]) {
                if (null !== $association && $metadata->isCollectionValuedAssociation($association)) {
                    return true;
                }
            }
        }

        return false;
    }
}
