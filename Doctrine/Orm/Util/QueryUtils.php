<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Doctrine\Orm\Util;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Query\Expr\OrderBy;
use Doctrine\ORM\QueryBuilder;

/**
 * Utility functions for working with Doctrine ORM query.
 *
 * @author Teoh Han Hui <teohhanhui@gmail.com>
 */
abstract class QueryUtils
{
    /**
     * Generates a unique alias for DQL join.
     *
     * @param string $association
     * @param string $namespace
     *
     * @return string
     */
    public static function generateJoinAlias($association)
    {
        return sprintf('%s_%s', $association, uniqid());
    }

    /**
     * Generates a unique parameter name for DQL query.
     *
     * @param string $name
     * @param string $namespace
     *
     * @return string
     */
    public static function generateParameterName($name)
    {
        return sprintf('%s_%s', $name, uniqid());
    }

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
                ->getClassMetadata($rootEntity)
            ;

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
            $parts = QueryBuilderUtil::getOrderByParts($orderBy);

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
                    $alias = QueryBuilderUtil::getJoinAlias($join);

                    if (isset($orderByAliases[$alias])) {
                        $relationship = QueryBuilderUtil::getJoinRelationship($join);

                        $relationshipParts = explode('.', $relationship);
                        $parentAlias = $relationshipParts[0];
                        $association = $relationshipParts[1];

                        $parentMetadata = QueryBuilderUtil::getClassMetadataFromJoinAlias($parentAlias, $queryBuilder, $managerRegistry);

                        if ($parentMetadata->isCollectionValuedAssociation($association)) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }

    /**
     * Gets the class metadata from a given join alias.
     *
     * @param string          $alias
     * @param QueryBuilder    $queryBuilder
     * @param ManagerRegistry $managerRegistry
     *
     * @return ClassMetadata
     */
    public static function getClassMetadataFromJoinAlias(
        $alias,
        QueryBuilder $queryBuilder,
        ManagerRegistry $managerRegistry
    ) {
        $rootEntities = $queryBuilder->getRootEntities();
        $rootAliases = $queryBuilder->getRootAliases();

        $joinParts = $queryBuilder->getDQLPart('join');

        $aliasMap = [];
        $targetAlias = $alias;

        foreach ($joinParts as $rootAlias => $joins) {
            $aliasMap[$rootAlias] = 'root';

            foreach ($joins as $join) {
                $alias = QueryBuilderUtil::getJoinAlias($join);
                $relationship = QueryBuilderUtil::getJoinRelationship($join);

                $pos = strpos($relationship, '.');

                $aliasMap[$alias] = [
                    'parentAlias' => substr($relationship, 0, $pos),
                    'association' => substr($relationship, $pos + 1),
                ];
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

        $rootEntity = $rootEntities[array_search($rootAlias, $rootAliases)];

        $rootMetadata = $managerRegistry
            ->getManagerForClass($rootEntity)
            ->getClassMetadata($rootEntity)
        ;

        $metadata = $rootMetadata;

        while (null !== ($association = array_pop($associationStack))) {
            $associationClass = $metadata->getAssociationTargetClass($association);

            $metadata = $managerRegistry
                ->getManagerForClass($associationClass)
                ->getClassMetadata($associationClass)
            ;
        }

        return $metadata;
    }

    /**
     * Gets the relationship from a Join expression.
     *
     * @param Join $join
     *
     * @return string
     */
    public static function getJoinRelationship(Join $join)
    {
        static $relationshipProperty = null;
        static $initialized = false;

        if (!$initialized && !method_exists(Join::class, 'getJoin')) {
            $relationshipProperty = new \ReflectionProperty(Join::class, '_join');
            $relationshipProperty->setAccessible(true);

            $initialized = true;
        }

        return (null === $relationshipProperty) ? $join->getJoin() : $relationshipProperty->getValue($join);
    }

    /**
     * Gets the alias from a Join expression.
     *
     * @param Join $join
     *
     * @return string
     */
    public static function getJoinAlias(Join $join)
    {
        static $aliasProperty = null;
        static $initialized = false;

        if (!$initialized && !method_exists(Join::class, 'getAlias')) {
            $aliasProperty = new \ReflectionProperty(Join::class, '_alias');
            $aliasProperty->setAccessible(true);

            $initialized = true;
        }

        return (null === $aliasProperty) ? $join->getAlias() : $aliasProperty->getValue($join);
    }

    /**
     * Gets the parts from an OrderBy expression.
     *
     * @param OrderBy $orderBy
     *
     * @return string[]
     */
    public static function getOrderByParts(OrderBy $orderBy)
    {
        static $partsProperty = null;
        static $initialized = false;

        if (!$initialized && !method_exists(OrderBy::class, 'getParts')) {
            $partsProperty = new \ReflectionProperty(OrderBy::class, '_parts');
            $partsProperty->setAccessible(true);

            $initialized = true;
        }

        return (null === $partsProperty) ? $orderBy->getParts() : $partsProperty->getValue($orderBy);
    }
}
