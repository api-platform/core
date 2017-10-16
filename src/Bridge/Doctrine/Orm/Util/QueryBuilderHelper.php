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

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

/**
 * @author Vincent Chalamon <vincent@les-tilleuls.coop>
 *
 * @internal
 */
final class QueryBuilderHelper
{
    private function __construct()
    {
    }

    /**
     * Adds a join to the queryBuilder if none exists.
     *
     * @param QueryBuilder                $queryBuilder
     * @param QueryNameGeneratorInterface $queryNameGenerator
     * @param string                      $alias
     * @param string                      $association        the association field
     * @param string|null                 $joinType           the join type (left join / inner join)
     * @param string|null                 $conditionType
     * @param string|null                 $condition
     *
     * @return string the new association alias
     */
    public static function addJoinOnce(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $alias, string $association, $joinType = null, $conditionType = null, $condition = null): string
    {
        $join = self::getExistingJoin($queryBuilder, $alias, $association);

        if (null === $join) {
            $associationAlias = $queryNameGenerator->generateJoinAlias($association);
            $query = sprintf('%s.%s', $alias, $association);

            if (Join::LEFT_JOIN === $joinType || true === QueryChecker::hasLeftJoin($queryBuilder)) {
                $queryBuilder->leftJoin($query, $associationAlias, $conditionType, $condition);
            } else {
                $queryBuilder->innerJoin($query, $associationAlias, $conditionType, $condition);
            }
        } else {
            $associationAlias = $join->getAlias();
        }

        return $associationAlias;
    }

    /**
     * Get the existing join from queryBuilder DQL parts.
     *
     * @param QueryBuilder $queryBuilder
     * @param string       $alias
     * @param string       $association  the association field
     *
     * @return Join|null
     */
    private static function getExistingJoin(QueryBuilder $queryBuilder, string $alias, string $association)
    {
        $parts = $queryBuilder->getDQLPart('join');

        if (!isset($parts['o'])) {
            return null;
        }

        foreach ($parts['o'] as $join) {
            /** @var Join $join */
            if (sprintf('%s.%s', $alias, $association) === $join->getJoin()) {
                return $join;
            }
        }

        return null;
    }
}
