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
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Query\Expr\OrderBy;
use Doctrine\ORM\QueryBuilder;

/**
 * Utility functions for working with Doctrine ORM query.
 *
 * @author Teoh Han Hui <teohhanhui@gmail.com>
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
final class QueryJoinParser
{
    private function __construct()
    {
    }

    /**
     * Gets the class metadata from a given join alias.
     */
    public static function getClassMetadataFromJoinAlias(string $alias, QueryBuilder $queryBuilder, ManagerRegistry $managerRegistry): ClassMetadata
    {
        $rootEntities = $queryBuilder->getRootEntities();
        $rootAliases = $queryBuilder->getRootAliases();

        $joinParts = $queryBuilder->getDQLPart('join');

        $aliasMap = [];
        $targetAlias = $alias;

        foreach ($joinParts as $rootAlias => $joins) {
            $aliasMap[$rootAlias] = 'root';

            foreach ($joins as $join) {
                $alias = $join->getAlias();
                $relationship = $join->getJoin();

                $pos = strpos($relationship, '.');

                if (false !== $pos) {
                    $aliasMap[$alias] = [
                        'parentAlias' => substr($relationship, 0, $pos),
                        'association' => substr($relationship, $pos + 1),
                    ];
                }
            }
        }

        $associationStack = [];
        $rootAlias = null;

        while (null === $rootAlias) {
            $mapping = $aliasMap[$targetAlias];

            if ('root' === $mapping) {
                $rootAlias = $targetAlias;
            } else {
                $associationStack[] = $mapping['association'];
                $targetAlias = $mapping['parentAlias'];
            }
        }

        $rootEntity = $rootEntities[array_search($rootAlias, $rootAliases, true)];

        $rootMetadata = $managerRegistry
            ->getManagerForClass($rootEntity)
            ->getClassMetadata($rootEntity);

        $metadata = $rootMetadata;

        while (null !== ($association = array_pop($associationStack))) {
            $associationClass = $metadata->getAssociationTargetClass($association);

            $metadata = $managerRegistry
                ->getManagerForClass($associationClass)
                ->getClassMetadata($associationClass);
        }

        return $metadata;
    }

    /**
     * Gets the relationship from a Join expression.
     */
    public static function getJoinRelationship(Join $join): string
    {
        @trigger_error(sprintf('The use of "%s::getJoinRelationship()" is deprecated since 2.3 and will be removed in 3.0. Use "%s::getJoin()" directly instead.', __CLASS__, Join::class), E_USER_DEPRECATED);

        return $join->getJoin();
    }

    /**
     * Gets the alias from a Join expression.
     */
    public static function getJoinAlias(Join $join): string
    {
        @trigger_error(sprintf('The use of "%s::getJoinAlias()" is deprecated since 2.3 and will be removed in 3.0. Use "%s::getAlias()" directly instead.', __CLASS__, Join::class), E_USER_DEPRECATED);

        return $join->getAlias();
    }

    /**
     * Gets the parts from an OrderBy expression.
     *
     * @return string[]
     */
    public static function getOrderByParts(OrderBy $orderBy): array
    {
        @trigger_error(sprintf('The use of "%s::getOrderByParts()" is deprecated since 2.3 and will be removed in 3.0. Use "%s::getParts()" directly instead.', __CLASS__, OrderBy::class), E_USER_DEPRECATED);

        return $orderBy->getParts();
    }
}
