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
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Query\Expr\OrderBy;
use Doctrine\ORM\QueryBuilder;

/**
 * Utility functions for working with Doctrine ORM query.
 *
 * @author Teoh Han Hui <teohhanhui@gmail.com>
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
abstract class QueryNameGenerator
{
    /**
     * Generates a unique alias for DQL join.
     *
     * @param string $association
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
     *
     * @return string
     */
    public static function generateParameterName($name)
    {
        return sprintf('%s_%s', $name, uniqid());
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
                $alias = self::getJoinAlias($join);
                $relationship = self::getJoinRelationship($join);

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
